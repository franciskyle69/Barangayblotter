@extends('layouts.app')

@section('title', 'Schedule mediation')

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-slate-800">Schedule mediation — Incident {{ $incident->blotter_number ?? '#' . $incident->id }}</h1>

    <form method="POST" action="{{ route('mediations.store') }}" class="max-w-md space-y-4 rounded-lg bg-white p-6 shadow">
        @csrf
        <input type="hidden" name="incident_id" value="{{ $incident->id }}">
        <div>
            <label for="mediator_user_id" class="mb-1 block text-sm font-medium text-slate-700">Mediator</label>
            <select name="mediator_user_id" id="mediator_user_id" required class="w-full rounded border border-slate-300 px-3 py-2">
                @foreach($mediators as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="scheduled_at" class="mb-1 block text-sm font-medium text-slate-700">Date & time</label>
            <input type="datetime-local" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Schedule</button>
            <a href="{{ route('incidents.show', $incident) }}" class="rounded bg-slate-200 px-4 py-2 text-slate-700 hover:bg-slate-300">Cancel</a>
        </div>
    </form>
@endsection
