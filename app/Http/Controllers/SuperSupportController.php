<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Tenant;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Super-admin queue for support tickets filed by tenants. All tickets are
 * visible here regardless of which barangay raised them, which is why the
 * underlying table lives on the central connection rather than inside
 * individual tenant databases.
 */
class SuperSupportController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $tenantId = $request->integer('tenant_id');

        $tickets = SupportTicket::query()
            ->with('tenant:id,name,slug')
            ->when($status !== '' && in_array($status, SupportTicket::STATUSES, true),
                fn ($q) => $q->where('status', $status))
            ->when($tenantId > 0, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderByRaw("CASE status
                WHEN 'open' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'awaiting_tenant' THEN 3
                WHEN 'resolved' THEN 4
                WHEN 'closed' THEN 5
                ELSE 99 END")
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SupportTicket $t) => [
                'id'               => $t->id,
                'subject'          => $t->subject,
                'category'         => $t->category,
                'priority'         => $t->priority,
                'status'           => $t->status,
                'opened_by_name'   => $t->opened_by_name,
                'opened_by_email'  => $t->opened_by_email,
                'tenant'           => [
                    'id'   => $t->tenant?->id,
                    'name' => $t->tenant?->name,
                    'slug' => $t->tenant?->slug,
                ],
                'created_at'       => $t->created_at?->toDateTimeString(),
                'last_activity_at' => $t->last_activity_at?->toDateTimeString(),
            ]);

        $counts = SupportTicket::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return Inertia::render('Super/Support', [
            'tickets'    => $tickets,
            'statuses'   => SupportTicket::STATUSES,
            'filters'    => [
                'status'    => $status ?: null,
                'tenant_id' => $tenantId > 0 ? $tenantId : null,
            ],
            'counts'     => $counts,
            'tenants'    => Tenant::query()->orderBy('name')->get(['id', 'name'])->toArray(),
        ]);
    }

    public function show(int $ticket): Response|RedirectResponse
    {
        $model = SupportTicket::query()
            ->with(['tenant:id,name,slug', 'messages', 'closedBy:id,name,email'])
            ->find($ticket);

        if (!$model) {
            return redirect()
                ->route('super.support.index')
                ->with('error', 'Support ticket not found.');
        }

        return Inertia::render('Super/SupportShow', [
            'ticket' => [
                'id'               => $model->id,
                'subject'          => $model->subject,
                'category'         => $model->category,
                'priority'         => $model->priority,
                'status'           => $model->status,
                'opened_by_name'   => $model->opened_by_name,
                'opened_by_email'  => $model->opened_by_email,
                'tenant'           => [
                    'id'   => $model->tenant?->id,
                    'name' => $model->tenant?->name,
                    'slug' => $model->tenant?->slug,
                ],
                'closure_note'     => $model->closure_note,
                'closed_at'        => $model->closed_at?->toDateTimeString(),
                'closed_by'        => $model->closedBy ? [
                    'name'  => $model->closedBy->name,
                    'email' => $model->closedBy->email,
                ] : null,
                'created_at'       => $model->created_at?->toDateTimeString(),
                'last_activity_at' => $model->last_activity_at?->toDateTimeString(),
                'is_closed'        => $model->isClosed(),
                'messages'         => $model->messages->map(fn (SupportTicketMessage $m) => [
                    'id'           => $m->id,
                    'author_scope' => $m->author_scope,
                    'author_name'  => $m->author_name,
                    'author_email' => $m->author_email,
                    'body'         => $m->body,
                    'created_at'   => $m->created_at?->toDateTimeString(),
                ])->all(),
            ],
            'statuses' => SupportTicket::STATUSES,
        ]);
    }

    public function reply(Request $request, int $ticket): RedirectResponse
    {
        $user  = $request->user();
        $model = SupportTicket::query()->find($ticket);

        if (!$model) {
            return redirect()
                ->route('super.support.index')
                ->with('error', 'Support ticket not found.');
        }

        if ($model->isClosed()) {
            return redirect()
                ->route('super.support.show', $model->id)
                ->with('error', 'This ticket is closed. Reopen it before replying.');
        }

        $validated = $request->validate([
            'body'        => ['required', 'string', 'min:2', 'max:5000'],
            'next_status' => ['nullable', Rule::in(SupportTicket::STATUSES)],
        ]);

        DB::connection('central')->transaction(function () use ($model, $user, $validated) {
            SupportTicketMessage::create([
                'support_ticket_id' => $model->id,
                'author_scope'      => SupportTicketMessage::SCOPE_SUPER_ADMIN,
                'author_user_id'    => $user?->id,
                'author_name'       => $user?->name,
                'author_email'      => $user?->email,
                'body'              => $validated['body'],
            ]);

            // Default behaviour: if the admin replies while a ticket is
            // "open", bump it to "in_progress" so it stops showing up as
            // un-triaged. An explicit next_status wins over the default.
            $next = $validated['next_status']
                ?? ($model->status === SupportTicket::STATUS_OPEN
                    ? SupportTicket::STATUS_IN_PROGRESS
                    : $model->status);

            $updates = [
                'status'           => $next,
                'last_activity_at' => now(),
            ];

            if (in_array($next, [SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_CLOSED], true)
                && !$model->closed_at
            ) {
                $updates['closed_at']         = now();
                $updates['closed_by_user_id'] = $user?->id;
            }

            $model->update($updates);
        });

        ActivityLogService::record(
            request: $request,
            action: 'super.support.reply',
            description: "Replied on support ticket #{$model->id}",
            metadata: [
                'ticket_id'  => $model->id,
                'tenant_id'  => $model->tenant_id,
                'next_status'=> $validated['next_status'] ?? null,
            ],
            targetType: SupportTicket::class,
            targetId: $model->id,
            tenantId: $model->tenant_id,
        );

        return redirect()
            ->route('super.support.show', $model->id)
            ->with('success', 'Reply posted.');
    }

    public function updateStatus(Request $request, int $ticket): RedirectResponse
    {
        $user  = $request->user();
        $model = SupportTicket::query()->find($ticket);

        if (!$model) {
            return redirect()
                ->route('super.support.index')
                ->with('error', 'Support ticket not found.');
        }

        $validated = $request->validate([
            'status'       => ['required', Rule::in(SupportTicket::STATUSES)],
            'closure_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $updates = [
            'status'           => $validated['status'],
            'last_activity_at' => now(),
        ];

        if (in_array($validated['status'], [SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_CLOSED], true)) {
            $updates['closed_at']         = now();
            $updates['closed_by_user_id'] = $user?->id;
            if (!empty($validated['closure_note'])) {
                $updates['closure_note'] = $validated['closure_note'];
            }
        } else {
            // Reopening: clear closure trail so audit stays clean.
            $updates['closed_at']         = null;
            $updates['closed_by_user_id'] = null;
        }

        $model->update($updates);

        ActivityLogService::record(
            request: $request,
            action: 'super.support.status_update',
            description: "Changed support ticket #{$model->id} status to {$validated['status']}",
            metadata: [
                'ticket_id' => $model->id,
                'tenant_id' => $model->tenant_id,
                'status'    => $validated['status'],
            ],
            targetType: SupportTicket::class,
            targetId: $model->id,
            tenantId: $model->tenant_id,
        );

        return redirect()
            ->route('super.support.show', $model->id)
            ->with('success', 'Ticket status updated.');
    }
}
