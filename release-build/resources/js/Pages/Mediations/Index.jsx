import { Link } from '@inertiajs/react';
import TenantLayout from '../Layouts/TenantLayout';

export default function MediationsIndex({ incidentsWithMediations }) {
  const formatDate = (d) => d ? new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
  const rows = [];
  (incidentsWithMediations?.data ?? incidentsWithMediations ?? []).forEach((incident) => {
    (incident.mediations || []).forEach((med) => {
      rows.push({ incident, med });
    });
  });

  return (
    <TenantLayout>
      <h1 className="mb-6 text-2xl font-bold text-slate-800">Mediation scheduling</h1>
      <div className="overflow-hidden rounded-lg bg-white shadow">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Incident</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Scheduled</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Mediator</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Status</th>
              <th className="px-4 py-2 text-right text-xs font-medium text-slate-500">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200">
            {rows.length ? rows.map(({ incident, med }) => (
              <tr key={med.id}>
                <td className="px-4 py-2">
                  <Link href={`/incidents/${incident.id}`} className="text-emerald-600 hover:underline">
                    {incident.blotter_number ?? `#${incident.id}`}
                  </Link>
                  <span className="text-slate-500"> — {incident.incident_type}</span>
                </td>
                <td className="px-4 py-2 text-sm">{formatDate(med.scheduled_at)}</td>
                <td className="px-4 py-2 text-sm">{med.mediator?.name}</td>
                <td className="px-4 py-2 text-sm">{med.status}</td>
                <td className="px-4 py-2 text-right">
                  <Link href={`/incidents/${incident.id}`} className="text-emerald-600 hover:underline">View incident</Link>
                </td>
              </tr>
            )) : (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-slate-500">No mediations scheduled.</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
      {incidentsWithMediations?.links && (
        <div className="mt-4 flex flex-wrap gap-1">
          {incidentsWithMediations.links.map((link, i) => (
            <Link key={i} href={link.url || '#'} className={`rounded px-3 py-1 text-sm ${link.active ? 'bg-devias-primary text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'} ${!link.url ? 'pointer-events-none opacity-50' : ''}`}>
              {link.label}
            </Link>
          ))}
        </div>
      )}
    </TenantLayout>
  );
}
