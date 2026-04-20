@extends('layouts.app')

@section('title', 'All Barangays')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">All Barangays — Barangay Blotter Tenancy</h1>
        <a href="{{ route('super.dashboard') }}" class="rounded bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">Dashboard</a>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Barangay</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">District</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Plan</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Incidents</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Active</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($tenants as $t)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $t->name }}</td>
                        <td class="px-4 py-2 text-sm">{{ $t->barangay ?? '—' }}</td>
                        <td class="px-4 py-2 text-sm">{{ $t->plan->name }}</td>
                        <td class="px-4 py-2 text-sm">{{ $t->incidents_count }}</td>
                        <td class="px-4 py-2 text-sm">{{ $t->is_active ? 'Yes' : 'No' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
