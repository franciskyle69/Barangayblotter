@extends('layouts.app')

@section('title', 'Edit incident')

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-slate-800">Edit incident {{ $incident->blotter_number ?? '#' . $incident->id }}</h1>

    <form method="POST" action="{{ route('incidents.update', $incident) }}" class="max-w-2xl space-y-4 rounded-lg bg-white p-6 shadow">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="incident_type" class="mb-1 block text-sm font-medium text-slate-700">Incident type</label>
                <input type="text" name="incident_type" id="incident_type" value="{{ old('incident_type', $incident->incident_type) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label for="incident_date" class="mb-1 block text-sm font-medium text-slate-700">Incident date</label>
                <input type="datetime-local" name="incident_date" id="incident_date" value="{{ old('incident_date', $incident->incident_date->format('Y-m-d\TH:i')) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
        </div>
        <div>
            <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
            <textarea name="description" id="description" rows="4" required class="w-full rounded border border-slate-300 px-3 py-2">{{ old('description', $incident->description) }}</textarea>
        </div>
        <div>
            <label for="location" class="mb-1 block text-sm font-medium text-slate-700">Location (optional)</label>
            <input type="text" name="location" id="location" value="{{ old('location', $incident->location) }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="complainant_name" class="mb-1 block text-sm text-slate-600">Complainant name *</label>
                <input type="text" name="complainant_name" id="complainant_name" value="{{ old('complainant_name', $incident->complainant_name) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label for="complainant_contact" class="mb-1 block text-sm text-slate-600">Complainant contact</label>
                <input type="text" name="complainant_contact" id="complainant_contact" value="{{ old('complainant_contact', $incident->complainant_contact) }}" class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="sm:col-span-3">
                <label for="complainant_address" class="mb-1 block text-sm text-slate-600">Complainant address</label>
                <input type="text" name="complainant_address" id="complainant_address" value="{{ old('complainant_address', $incident->complainant_address) }}" class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="sm:col-span-2">
                <label for="respondent_name" class="mb-1 block text-sm text-slate-600">Respondent name *</label>
                <input type="text" name="respondent_name" id="respondent_name" value="{{ old('respondent_name', $incident->respondent_name) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label for="respondent_contact" class="mb-1 block text-sm text-slate-600">Respondent contact</label>
                <input type="text" name="respondent_contact" id="respondent_contact" value="{{ old('respondent_contact', $incident->respondent_contact) }}" class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="sm:col-span-3">
                <label for="respondent_address" class="mb-1 block text-sm text-slate-600">Respondent address</label>
                <input type="text" name="respondent_address" id="respondent_address" value="{{ old('respondent_address', $incident->respondent_address) }}" class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
        </div>
        <div>
            <label for="status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
            <select name="status" id="status" class="w-full rounded border border-slate-300 px-3 py-2">
                @foreach($statuses as $k => $v)
                    <option value="{{ $k }}" @selected(old('status', $incident->status) === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Update</button>
            <a href="{{ route('incidents.show', $incident) }}" class="rounded bg-slate-200 px-4 py-2 text-slate-700 hover:bg-slate-300">Cancel</a>
        </div>
    </form>
@endsection
