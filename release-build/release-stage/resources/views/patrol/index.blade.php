@extends('layouts.app')

@section('title', 'Patrol logs')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">Patrol logs</h1>
        <a href="{{ route('patrol.create') }}" class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Log patrol</a>
    </div>

    <form method="GET" class="mb-4 flex gap-2">
        <input type="date" name="date" value="{{ request('date') }}" class="rounded border border-slate-300 px-3 py-1.5 text-sm">
        <button type="submit" class="rounded bg-slate-600 px-3 py-1.5 text-sm text-white">Filter</button>
    </form>

    <div class="space-y-3">
        @forelse($patrolLogs as $log)
            <div class="rounded-lg bg-white p-4 shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium">{{ $log->patrol_date->format('M d, Y') }}</p>
                        <p class="text-sm text-slate-600">By {{ $log->user->name }}</p>
                        @if($log->area_patrolled)<p class="text-sm text-slate-500">Area: {{ $log->area_patrolled }}</p>@endif
                        @if($log->start_time)<p class="text-sm text-slate-500">{{ $log->start_time }} – {{ $log->end_time }}</p>@endif
                        @if($log->activities)<p class="mt-1 text-sm">{{ Str::limit($log->activities, 120) }}</p>@endif
                    </div>
                    <a href="{{ route('patrol.edit', $log) }}" class="text-emerald-600 hover:underline">Edit</a>
                </div>
            </div>
        @empty
            <p class="rounded-lg bg-white p-6 text-center text-slate-500 shadow">No patrol logs found.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $patrolLogs->withQueryString()->links() }}</div>
@endsection
