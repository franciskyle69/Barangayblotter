@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <div class="rounded-devias border border-slate-200/80 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-4">
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="mb-4">
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="mb-4 flex items-center">
                <input type="checkbox" name="remember" id="remember" class="rounded">
                <label for="remember" class="ml-2 text-sm text-slate-600">Remember me</label>
            </div>
            <button type="submit" class="w-full rounded-devias bg-devias-primary px-4 py-2.5 font-semibold text-white shadow-sm hover:opacity-95">
                Sign in
            </button>
        </form>
        <p class="mt-4 text-center text-sm text-slate-600">
            Don't have an account? <a href="{{ route('register') }}" class="text-devias-primary font-medium hover:underline">Register</a>
        </p>
    </div>
@endsection
