@extends('layouts.app')

@section('title', 'Incident ' . ($incident->blotter_number ?? $incident->id))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">Incident {{ $incident->blotter_number ?? '#' . $incident->id }}</h1>
        @if(auth()->user()->roleIn($tenant) !== 'resident')
            <div class="flex gap-2">
                <a href="{{ route('incidents.edit', $incident) }}" class="rounded bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">Edit</a>
                @if($tenant->plan->mediation_scheduling && $incident->status !== 'settled' && $incident->status !== 'escalated_to_barangay')
                    <a href="{{ route('mediations.create', $incident) }}" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Schedule mediation</a>
                @endif
                <a href="{{ route('blotter-requests.create') }}?incident_id={{ $incident->id }}" class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Request certified copy</a>
            </div>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-6 shadow">
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-slate-500">Type</dt>
                    <dd class="font-medium">{{ $incident->incident_type }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Status</dt>
                    <dd><span class="rounded px-2 py-0.5 text-sm
                        @if($incident->status === 'open') bg-amber-100 text-amber-800
                        @elseif($incident->status === 'under_mediation') bg-blue-100 text-blue-800
                        @elseif($incident->status === 'settled') bg-emerald-100 text-emerald-800
                        @else bg-slate-100 text-slate-800
                        @endif">{{ \App\Models\Incident::statuses()[$incident->status] ?? $incident->status }}</span></dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Incident date</dt>
                    <dd>{{ $incident->incident_date->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Location</dt>
                    <dd>{{ $incident->location ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Description</dt>
                    <dd class="whitespace-pre-wrap">{{ $incident->description }}</dd>
                </div>
                @if($incident->reportedBy)
                    <div>
                        <dt class="text-sm text-slate-500">Recorded by</dt>
                        <dd>{{ $incident->reportedBy->name }}</dd>
                    </div>
                @endif
            </dl>
        </div>
        <div class="space-y-6">
            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-3 font-semibold text-slate-800">Complainant</h3>
                <p class="font-medium">{{ $incident->complainant_name }}</p>
                @if($incident->complainant_contact)<p class="text-sm text-slate-600">{{ $incident->complainant_contact }}</p>@endif
                @if($incident->complainant_address)<p class="text-sm text-slate-600">{{ $incident->complainant_address }}</p>@endif
            </div>
            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-3 font-semibold text-slate-800">Respondent</h3>
                <p class="font-medium">{{ $incident->respondent_name }}</p>
                @if($incident->respondent_contact)<p class="text-sm text-slate-600">{{ $incident->respondent_contact }}</p>@endif
                @if($incident->respondent_address)<p class="text-sm text-slate-600">{{ $incident->respondent_address }}</p>@endif
            </div>
            @if($incident->attachments->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-3 font-semibold text-slate-800">Attachments</h3>
                    <ul class="space-y-1">
                        @foreach($incident->attachments as $att)
                            <li><a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="text-emerald-600 hover:underline">{{ $att->original_name ?: 'Attachment' }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if($incident->mediations->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-3 font-semibold text-slate-800">Mediations</h3>
                    @foreach($incident->mediations as $med)
                        <div class="mb-3 border-b border-slate-100 pb-3 last:border-0">
                            <p class="font-medium">Scheduled: {{ $med->scheduled_at->format('M d, Y H:i') }}</p>
                            <p class="text-sm text-slate-600">Mediator: {{ $med->mediator->name }}</p>
                            <p class="text-sm">Status: {{ $med->status }}</p>
                            @if($med->settlement_terms)<p class="mt-1 text-sm">{{ $med->settlement_terms }}</p>@endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
