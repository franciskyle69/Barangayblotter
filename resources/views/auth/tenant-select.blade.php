@extends('layouts.guest')

@section('title', 'Select Barangay')

@section('content')
    <div class="rounded-devias border border-slate-200/80 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-800">Select your Barangay</h2>
        <p class="mb-4 text-sm text-slate-600">Choose the barangay (Malaybalay City) you want to access.</p>
        <form method="POST" action="{{ route('tenant.select.store') }}">
            @csrf
            <div class="space-y-2">
                @foreach($tenants as $t)
                    <label class="flex cursor-pointer items-center gap-3 rounded border border-slate-200 p-3 hover:bg-slate-50">
                        <input type="radio" name="tenant_id" value="{{ $t->id }}" required>
                        <span class="font-medium">{{ $t->name }}</span>
                        <span class="text-sm text-slate-500">({{ $t->plan->name }})</span>
                    </label>
                @endforeach
            </div>
            @if($tenants->isEmpty())
                <p class="text-slate-600">You are not assigned to any barangay yet. Contact your administrator.</p>
            @else
                <button type="submit" class="mt-4 w-full rounded bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700">
                    Continue
                </button>
            @endif
        </form>
    </div>
@endsection
