<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Barangay Blotter') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f9fafb] font-sans antialiased">
    <div class="flex min-h-screen">
        {{-- Sidebar (Devias-style) - hidden on small screens --}}
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-white/5 bg-devias-sidebar lg:flex">
            <div class="flex h-16 shrink-0 items-center gap-2 border-b border-white/5 px-6">
                <span class="flex size-9 items-center justify-center rounded-devias bg-devias-primary text-sm font-bold text-white">MB</span>
                <span class="truncate text-base font-semibold text-white">{{ config('app.name') }}</span>
            </div>
            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4" aria-label="Main">
                @if(auth()->user()->is_super_admin ?? false)
                    <a href="{{ route('super.dashboard') }}" class="flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('super.dashboard') ? 'bg-devias-primary/20 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Overview</a>
                    <a href="{{ route('super.tenants') }}" class="flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('super.tenants') ? 'bg-devias-primary/20 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">All Barangays</a>
                @else
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-devias-primary/20 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Overview</a>
                    @if($navTenant ?? null)
                        <a href="{{ route('incidents.index') }}" class="flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('incidents.*') ? 'bg-devias-primary/20 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Incidents</a>
                        <a href="{{ route('incidents.create') }}" class="flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-white/5 hover:text-white">New Incident</a>
                        @if($navShowMediations ?? false)
                            <a href="{{ route('mediations.index') }}" class="flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('mediations.*') ? 'bg-devias-primary/20 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Mediations</a>
                        @endif
                        <a href="{{ route('patrol.index') }}" class="flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('patrol.*') ? 'bg-devias-primary/20 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Patrol</a>
                        <a href="{{ route('blotter-requests.index') }}" class="flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('blotter-requests.*') ? 'bg-devias-primary/20 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Blotter Requests</a>
                    @endif
                @endif
            </nav>
        </aside>

        {{-- Main content area --}}
        <div class="flex flex-1 flex-col lg:pl-64">
            {{-- Top bar (Devias MainNav-style) --}}
            <header class="sticky top-0 z-30 flex h-16 shrink-0 flex-wrap items-center gap-3 border-b border-slate-200 bg-white px-4 shadow-sm sm:gap-4 sm:px-6">
                {{-- Mobile nav links (visible when sidebar hidden) --}}
                <div class="flex flex-1 flex-wrap items-center gap-1 lg:hidden">
                    <a href="{{ route('dashboard') }}" class="rounded-devias px-2.5 py-1.5 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-devias-primary/10 text-devias-primary' : 'text-slate-600 hover:bg-slate-100' }}">Dashboard</a>
                    @if($navTenant ?? null)
                        <a href="{{ route('incidents.index') }}" class="rounded-devias px-2.5 py-1.5 text-sm font-medium {{ request()->routeIs('incidents.*') ? 'bg-devias-primary/10 text-devias-primary' : 'text-slate-600 hover:bg-slate-100' }}">Incidents</a>
                        <a href="{{ route('incidents.create') }}" class="rounded-devias px-2.5 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100">New</a>
                        @if($navShowMediations ?? false)
                            <a href="{{ route('mediations.index') }}" class="rounded-devias px-2.5 py-1.5 text-sm font-medium {{ request()->routeIs('mediations.*') ? 'bg-devias-primary/10 text-devias-primary' : 'text-slate-600 hover:bg-slate-100' }}">Mediations</a>
                        @endif
                        <a href="{{ route('patrol.index') }}" class="rounded-devias px-2.5 py-1.5 text-sm font-medium {{ request()->routeIs('patrol.*') ? 'bg-devias-primary/10 text-devias-primary' : 'text-slate-600 hover:bg-slate-100' }}">Patrol</a>
                        <a href="{{ route('blotter-requests.index') }}" class="rounded-devias px-2.5 py-1.5 text-sm font-medium {{ request()->routeIs('blotter-requests.*') ? 'bg-devias-primary/10 text-devias-primary' : 'text-slate-600 hover:bg-slate-100' }}">Blotter</a>
                    @endif
                    @if(auth()->user()->is_super_admin ?? false)
                        <a href="{{ route('super.dashboard') }}" class="rounded-devias px-2.5 py-1.5 text-sm font-medium {{ request()->routeIs('super.dashboard') ? 'bg-devias-primary/10 text-devias-primary' : 'text-slate-600 hover:bg-slate-100' }}">Dashboard</a>
                        <a href="{{ route('super.tenants') }}" class="rounded-devias px-2.5 py-1.5 text-sm font-medium {{ request()->routeIs('super.tenants') ? 'bg-devias-primary/10 text-devias-primary' : 'text-slate-600 hover:bg-slate-100' }}">Barangays</a>
                    @endif
                </div>
                <div class="flex items-center justify-end gap-3">
                    @if(!(auth()->user()->is_super_admin ?? false) && ($navTenant ?? null))
                        <a href="{{ route('tenant.select') }}" class="flex items-center gap-2 rounded-devias border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                            <span>{{ $navTenant->name }}</span>
                            <svg class="size-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
                        </a>
                    @endif
                    <span class="text-sm text-slate-600">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-devias border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Logout</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-6">
                @if(session('success'))
                    <div class="mb-4 rounded-devias border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-devias border border-red-200 bg-red-50 p-4 text-red-800">{{ session('error') }}</div>
                @endif
                @if(session('warning'))
                    <div class="mb-4 rounded-devias border border-amber-200 bg-amber-50 p-4 text-amber-800">{{ session('warning') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
