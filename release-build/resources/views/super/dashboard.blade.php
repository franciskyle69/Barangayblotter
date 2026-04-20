@extends('layouts.app')

@section('title', 'Barangay dashboard')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">Barangay Blotter Tenancy — Central Monitoring</h1>
        <a href="{{ route('super.tenants') }}" class="rounded bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">All Barangays</a>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow">
            <p class="text-sm text-slate-500">Total incidents</p>
            <p class="text-2xl font-bold text-slate-800">{{ $totalIncidents }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
            <p class="text-sm text-slate-500">This month</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $incidentsThisMonth }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
            <p class="text-sm text-slate-500">Barangays</p>
            <p class="text-2xl font-bold text-slate-800">{{ $tenants->count() }}</p>
        </div>
    </div>

    <div class="mb-6 rounded-lg bg-white p-4 shadow">
        <h2 class="mb-3 font-semibold text-slate-800">By status</h2>
        <div class="flex flex-wrap gap-4">
            @foreach($byStatus as $status => $total)
                <span class="rounded bg-slate-100 px-3 py-1 text-sm">{{ $status }}: {{ $total }}</span>
            @endforeach
        </div>
    </div>

    <div class="rounded-lg bg-white p-4 shadow">
        <h2 class="mb-3 font-semibold text-slate-800">Recent incidents (all barangays)</h2>
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Barangay</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Blotter / Type</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($recentIncidents as $inc)
                    <tr>
                        <td class="px-4 py-2 text-sm">{{ $inc->tenant->name }}</td>
                        <td class="px-4 py-2 text-sm">{{ $inc->blotter_number ?? '#' . $inc->id }} — {{ $inc->incident_type }}</td>
                        <td class="px-4 py-2 text-sm">{{ $inc->status }}</td>
                        <td class="px-4 py-2 text-sm">{{ $inc->created_at->format('M d, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
