@extends('layouts.app')

@section('title', 'Blotter requests')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">Blotter / certified copy requests</h1>
        <a href="{{ route('blotter-requests.create') }}" class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Request copy</a>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Incident</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Requested by</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Purpose</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Status</th>
                    @if($role !== 'resident')
                        <th class="px-4 py-2 text-right text-xs font-medium text-slate-500">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($requests as $req)
                    <tr>
                        <td class="px-4 py-2">
                            <a href="{{ route('incidents.show', $req->incident) }}" class="text-emerald-600 hover:underline">{{ $req->incident->blotter_number ?? '#' . $req->incident->id }}</a>
                        </td>
                        <td class="px-4 py-2 text-sm">{{ $req->requestedBy->name }}</td>
                        <td class="px-4 py-2 text-sm">{{ $req->purpose ?? '—' }}</td>
                        <td class="px-4 py-2"><span class="rounded px-2 py-0.5 text-xs
                            @if($req->status === 'pending') bg-amber-100 text-amber-800
                            @elseif($req->status === 'approved') bg-blue-100 text-blue-800
                            @elseif($req->status === 'printed') bg-emerald-100 text-emerald-800
                            @else bg-slate-100 text-slate-800
                            @endif">{{ $req->status }}</span></td>
                        @if($role !== 'resident')
                            <td class="px-4 py-2 text-right">
                                @if($req->status === 'pending')
                                    <form action="{{ route('blotter-requests.approve', $req) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-emerald-600 hover:underline">Approve</button>
                                    </form>
                                    <form action="{{ route('blotter-requests.reject', $req) }}" method="POST" class="ml-2 inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:underline">Reject</button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $role === 'resident' ? 4 : 5 }}" class="px-4 py-8 text-center text-slate-500">No requests.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
