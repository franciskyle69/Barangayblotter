@extends('layouts.app')

@section('title', 'Mediations')

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-slate-800">Mediation scheduling</h1>

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Incident</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Scheduled</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Mediator</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Status</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($incidentsWithMediations as $incident)
                    @foreach($incident->mediations as $med)
                        <tr>
                            <td class="px-4 py-2">
                                <a href="{{ route('incidents.show', $incident) }}" class="text-emerald-600 hover:underline">{{ $incident->blotter_number ?? '#' . $incident->id }}</a>
                                <span class="text-slate-500">— {{ $incident->incident_type }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $med->scheduled_at->format('M d, Y H:i') }}</td>
                            <td class="px-4 py-2 text-sm">{{ $med->mediator->name }}</td>
                            <td class="px-4 py-2 text-sm">{{ $med->status }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('incidents.show', $incident) }}" class="text-emerald-600 hover:underline">View incident</a>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">No mediations scheduled.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $incidentsWithMediations->links() }}</div>
@endsection
