@extends('layouts.app')

@section('title', 'Request blotter copy')

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-slate-800">Request certified blotter copy</h1>

    <form method="POST" action="{{ route('blotter-requests.store') }}" class="max-w-md space-y-4 rounded-lg bg-white p-6 shadow">
        @csrf
        <div>
            <label for="incident_id" class="mb-1 block text-sm font-medium text-slate-700">Incident</label>
            <select name="incident_id" id="incident_id" required class="w-full rounded border border-slate-300 px-3 py-2">
                <option value="">Select incident</option>
                @foreach($incidents as $inc)
                    <option value="{{ $inc->id }}" @selected(old('incident_id') == $inc->id)>
                        {{ $inc->blotter_number ?? '#' . $inc->id }} — {{ $inc->incident_type }} ({{ $inc->incident_date->format('M d, Y') }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="purpose" class="mb-1 block text-sm font-medium text-slate-700">Purpose (optional)</label>
            <input type="text" name="purpose" id="purpose" value="{{ old('purpose') }}" class="w-full rounded border border-slate-300 px-3 py-2" placeholder="e.g. Legal requirement, Personal record">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Submit request</button>
            <a href="{{ route('blotter-requests.index') }}" class="rounded bg-slate-200 px-4 py-2 text-slate-700 hover:bg-slate-300">Cancel</a>
        </div>
    </form>
@endsection
