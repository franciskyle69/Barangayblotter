@extends('layouts.app')

@section('title', 'Incidents')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">Incidents / Blotter</h1>
        <a href="{{ route('incidents.create') }}" class="rounded-devias bg-devias-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-95">New incident</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <select name="status" class="rounded border border-slate-300 px-3 py-1.5 text-sm">
            <option value="">All statuses</option>
            @foreach(\App\Models\Incident::statuses() as $k => $v)
                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded border border-slate-300 px-3 py-1.5 text-sm" placeholder="From">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded border border-slate-300 px-3 py-1.5 text-sm" placeholder="To">
        <button type="submit" class="rounded bg-slate-600 px-3 py-1.5 text-sm text-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-devias border border-slate-200/80 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Blotter #</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Type</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Complainant / Respondent</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Date</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Status</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($incidents as $inc)
                    <tr>
                        <td class="px-4 py-2 font-mono text-sm">{{ $inc->blotter_number ?? '—' }}</td>
                        <td class="px-4 py-2 text-sm">{{ $inc->incident_type }}</td>
                        <td class="px-4 py-2 text-sm">{{ $inc->complainant_name }} / {{ $inc->respondent_name }}</td>
                        <td class="px-4 py-2 text-sm">{{ $inc->incident_date->format('M d, Y') }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded px-2 py-0.5 text-xs
                                @if($inc->status === 'open') bg-amber-100 text-amber-800
                                @elseif($inc->status === 'under_mediation') bg-blue-100 text-blue-800
                                @elseif($inc->status === 'settled') bg-emerald-100 text-emerald-800
                                @else bg-slate-100 text-slate-800
                                @endif">{{ \App\Models\Incident::statuses()[$inc->status] ?? $inc->status }}</span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('incidents.show', $inc) }}" class="text-devias-primary font-medium hover:underline">View</a>
                            @if(auth()->user()->roleIn($tenant) !== 'resident')
                                <a href="{{ route('incidents.edit', $inc) }}" class="ml-2 text-slate-600 hover:underline">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">No incidents found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $incidents->withQueryString()->links() }}</div>
@endsection
