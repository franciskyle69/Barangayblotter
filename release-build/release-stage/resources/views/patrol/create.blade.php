@extends('layouts.app')

@section('title', 'Log patrol')

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-slate-800">Log patrol activity</h1>

    <form method="POST" action="{{ route('patrol.store') }}" class="max-w-2xl space-y-4 rounded-lg bg-white p-6 shadow">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="patrol_date" class="mb-1 block text-sm font-medium text-slate-700">Date</label>
                <input type="date" name="patrol_date" id="patrol_date" value="{{ old('patrol_date', now()->format('Y-m-d')) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="start_time" class="mb-1 block text-sm text-slate-600">Start time</label>
                    <input type="time" name="start_time" id="start_time" value="{{ old('start_time') }}" class="w-full rounded border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label for="end_time" class="mb-1 block text-sm text-slate-600">End time</label>
                    <input type="time" name="end_time" id="end_time" value="{{ old('end_time') }}" class="w-full rounded border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>
        <div>
            <label for="area_patrolled" class="mb-1 block text-sm font-medium text-slate-700">Area patrolled</label>
            <input type="text" name="area_patrolled" id="area_patrolled" value="{{ old('area_patrolled') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label for="activities" class="mb-1 block text-sm font-medium text-slate-700">Activities</label>
            <textarea name="activities" id="activities" rows="3" class="w-full rounded border border-slate-300 px-3 py-2">{{ old('activities') }}</textarea>
        </div>
        <div>
            <label for="incidents_observed" class="mb-1 block text-sm font-medium text-slate-700">Incidents observed</label>
            <textarea name="incidents_observed" id="incidents_observed" rows="2" class="w-full rounded border border-slate-300 px-3 py-2">{{ old('incidents_observed') }}</textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="response_details" class="mb-1 block text-sm text-slate-600">Response details</label>
                <textarea name="response_details" id="response_details" rows="2" class="w-full rounded border border-slate-300 px-3 py-2">{{ old('response_details') }}</textarea>
            </div>
            <div>
                <label for="response_time_minutes" class="mb-1 block text-sm text-slate-600">Response time (minutes)</label>
                <input type="number" name="response_time_minutes" id="response_time_minutes" value="{{ old('response_time_minutes') }}" min="0" class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Save</button>
            <a href="{{ route('patrol.index') }}" class="rounded bg-slate-200 px-4 py-2 text-slate-700 hover:bg-slate-300">Cancel</a>
        </div>
    </form>
@endsection
