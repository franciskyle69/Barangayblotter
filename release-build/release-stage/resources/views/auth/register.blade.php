@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <div class="rounded-devias border border-slate-200/80 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="mb-4">
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Full name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="mb-4">
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="mb-4">
                <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone (optional)</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                    class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="mb-4">
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="mb-4">
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <button type="submit" class="w-full rounded-devias bg-devias-primary px-4 py-2.5 font-semibold text-white shadow-sm hover:opacity-95">
                Register
            </button>
        </form>
        <p class="mt-4 text-center text-sm text-slate-600">
            Already have an account? <a href="{{ route('login') }}" class="text-devias-primary font-medium hover:underline">Login</a>
        </p>
    </div>
@endsection
