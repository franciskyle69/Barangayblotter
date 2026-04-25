<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tenant-facing controller: lets barangay staff / residents open a ticket
 * against the central team ("complain to central"), view their ticket
 * history, and post replies. Every action here is scoped to the tenant
 * resolved by middleware (`app('current_tenant')`) so one barangay can
 * never see another's tickets.
 */
class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('current_tenant');

        $tickets = SupportTicket::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SupportTicket $t) => [
                'id'               => $t->id,
                'subject'          => $t->subject,
                'category'         => $t->category,
                'priority'         => $t->priority,
                'status'           => $t->status,
                'opened_by_name'   => $t->opened_by_name,
                'created_at'       => $t->created_at?->toDateTimeString(),
                'last_activity_at' => $t->last_activity_at?->toDateTimeString(),
            ]);

        return Inertia::render('Support/Index', [
            'tickets'    => $tickets,
            'categories' => SupportTicket::CATEGORIES,
            'priorities' => SupportTicket::PRIORITIES,
            'statuses'   => SupportTicket::STATUSES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Support/Create', [
            'categories' => SupportTicket::CATEGORIES,
            'priorities' => SupportTicket::PRIORITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app('current_tenant');
        $user   = $request->user();

        $validated = $request->validate([
            'subject'  => ['required', 'string', 'min:5', 'max:255'],
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)],
            'body'     => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        // Wrap in a central-connection transaction so the ticket and its
        // opening message either both persist or neither does. Using the
        // central connection explicitly guards against any ambient tenant
        // connection that may be in scope.
        $ticket = DB::connection('central')->transaction(function () use ($tenant, $user, $validated) {
            $now = now();

            $ticket = SupportTicket::create([
                'tenant_id'        => $tenant->id,
                'subject'          => $validated['subject'],
                'category'         => $validated['category'],
                'priority'         => $validated['priority'],
                'status'           => SupportTicket::STATUS_OPEN,
                'opened_by_user_id'=> $user?->id,
                'opened_by_name'   => $user?->name,
                'opened_by_email'  => $user?->email,
                'last_activity_at' => $now,
            ]);

            SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'author_scope'      => SupportTicketMessage::SCOPE_TENANT,
                'author_user_id'    => $user?->id,
                'author_name'       => $user?->name,
                'author_email'      => $user?->email,
                'body'              => $validated['body'],
            ]);

            return $ticket;
        });

        ActivityLogService::record(
            request: $request,
            action: 'tenant.support.create',
            description: "Opened support ticket #{$ticket->id}: {$ticket->subject}",
            metadata: [
                'ticket_id' => $ticket->id,
                'category'  => $ticket->category,
                'priority'  => $ticket->priority,
            ],
            targetType: SupportTicket::class,
            targetId: $ticket->id,
            tenantId: $tenant->id,
        );

        return redirect()
            ->route('support.show', $ticket->id)
            ->with('success', 'Your support ticket has been submitted. Our team will get back to you shortly.');
    }

    public function show(Request $request, int $ticket): Response|RedirectResponse
    {
        $tenant = app('current_tenant');

        $model = SupportTicket::query()
            ->with(['messages'])
            ->where('tenant_id', $tenant->id)
            ->find($ticket);

        if (!$model) {
            return redirect()
                ->route('support.index')
                ->with('error', 'Support ticket not found.');
        }

        return Inertia::render('Support/Show', [
            'ticket' => [
                'id'               => $model->id,
                'subject'          => $model->subject,
                'category'         => $model->category,
                'priority'         => $model->priority,
                'status'           => $model->status,
                'opened_by_name'   => $model->opened_by_name,
                'opened_by_email'  => $model->opened_by_email,
                'closure_note'     => $model->closure_note,
                'closed_at'        => $model->closed_at?->toDateTimeString(),
                'created_at'       => $model->created_at?->toDateTimeString(),
                'last_activity_at' => $model->last_activity_at?->toDateTimeString(),
                'is_closed'        => $model->isClosed(),
                'messages'         => $model->messages->map(fn (SupportTicketMessage $m) => [
                    'id'           => $m->id,
                    'author_scope' => $m->author_scope,
                    'author_name'  => $m->author_name,
                    'body'         => $m->body,
                    'created_at'   => $m->created_at?->toDateTimeString(),
                ])->all(),
            ],
        ]);
    }

    public function reply(Request $request, int $ticket): RedirectResponse
    {
        $tenant = app('current_tenant');
        $user   = $request->user();

        $model = SupportTicket::query()
            ->where('tenant_id', $tenant->id)
            ->find($ticket);

        if (!$model) {
            return redirect()
                ->route('support.index')
                ->with('error', 'Support ticket not found.');
        }

        if ($model->isClosed()) {
            return redirect()
                ->route('support.show', $model->id)
                ->with('error', 'This ticket is closed. Please open a new ticket if you need further help.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        DB::connection('central')->transaction(function () use ($model, $user, $validated) {
            SupportTicketMessage::create([
                'support_ticket_id' => $model->id,
                'author_scope'      => SupportTicketMessage::SCOPE_TENANT,
                'author_user_id'    => $user?->id,
                'author_name'       => $user?->name,
                'author_email'      => $user?->email,
                'body'              => $validated['body'],
            ]);

            // If the super-admin was awaiting a reply, flip state back
            // into the queue so they see the update.
            $updates = ['last_activity_at' => now()];
            if ($model->status === SupportTicket::STATUS_AWAITING_TENANT) {
                $updates['status'] = SupportTicket::STATUS_IN_PROGRESS;
            }
            $model->update($updates);
        });

        ActivityLogService::record(
            request: $request,
            action: 'tenant.support.reply',
            description: "Replied on support ticket #{$model->id}",
            metadata: ['ticket_id' => $model->id],
            targetType: SupportTicket::class,
            targetId: $model->id,
            tenantId: $tenant->id,
        );

        return redirect()
            ->route('support.show', $model->id)
            ->with('success', 'Reply posted.');
    }
}
