@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Overview</h1>
            <p class="mt-1 text-sm text-slate-500">{{ \App\Models\User::tenantRoles()[$role] ?? $role }} · {{ $tenant->name }}</p>
        </div>
        <a href="{{ route('incidents.create') }}" class="inline-flex items-center gap-2 rounded-devias bg-devias-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-95">New incident</a>
    </div>

    {{-- Stats grid (Devias-style cards) --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Incidents this month</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stats['incidents_this_month'] }}</p>
            @if(!$tenant->plan->hasUnlimitedIncidents())
                <p class="mt-1 text-xs text-slate-400">Limit: {{ $tenant->plan->incident_limit_per_month }}/month</p>
            @endif
        </div>
        <div class="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Open</p>
            <p class="mt-2 text-2xl font-bold text-amber-600">{{ $stats['open'] }}</p>
        </div>
        <div class="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Under mediation</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ $stats['under_mediation'] }}</p>
        </div>
        <div class="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Settled</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $stats['settled'] }}</p>
        </div>
        <div class="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Escalated</p>
            <p class="mt-2 text-2xl font-bold text-slate-700">{{ $stats['escalated'] }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-devias border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Recent incidents</h2>
                <a href="{{ route('incidents.index') }}" class="text-sm font-medium text-devias-primary hover:underline">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($recentIncidents as $inc)
                    <a href="{{ route('incidents.show', $inc) }}" class="flex items-center justify-between px-5 py-3 transition hover:bg-slate-50">
                        <span class="font-medium text-slate-800">{{ $inc->blotter_number ?? 'N/A' }}</span>
                        <span class="text-sm text-slate-500">{{ $inc->incident_type }} · {{ $inc->incident_date->format('M d, Y') }}</span>
                    </a>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">No incidents yet.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-devias border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">My blotter requests</h2>
                <a href="{{ route('blotter-requests.index') }}" class="text-sm font-medium text-devias-primary hover:underline">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($myBlotterRequests as $req)
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-slate-800">Incident {{ $req->incident->blotter_number ?? $req->incident->id }}</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $req->status }}</span>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">No blotter requests.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
