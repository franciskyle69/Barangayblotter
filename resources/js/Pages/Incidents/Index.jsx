import { Link, router, usePage } from '@inertiajs/react';
import TenantLayout from '../Layouts/TenantLayout';

const STATUS_CLASS = {
  open: 'bg-amber-100 text-amber-800',
  under_mediation: 'bg-blue-100 text-blue-800',
  settled: 'bg-emerald-100 text-emerald-800',
  escalated_to_barangay: 'bg-slate-100 text-slate-800',
};

export default function IncidentsIndex({ incidents, statuses, role }) {
  const canEdit = !['resident', 'citizen'].includes(role);

  const handleFilter = (e) => {
    e.preventDefault();
    const form = e.target;
    const params = new URLSearchParams();
    if (form.status?.value) params.set('status', form.status.value);
    if (form.from?.value) params.set('from', form.from.value);
    if (form.to?.value) params.set('to', form.to.value);
    router.get('/incidents', Object.fromEntries(params));
  };

  return (
    <TenantLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-slate-800">Incidents / Blotter</h1>
        <Link
          href="/incidents/create"
          className="rounded-devias bg-devias-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-95"
        >
          New incident
        </Link>
      </div>

      <form method="GET" onSubmit={handleFilter} className="mb-4 flex flex-wrap gap-2">
        <select name="status" className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm" defaultValue={new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '').get('status') || ''}>
          <option value="">All statuses</option>
          {statuses && Object.entries(statuses).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
        <input type="date" name="from" defaultValue={new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '').get('from') || ''} className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm" placeholder="From" />
        <input type="date" name="to" defaultValue={new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '').get('to') || ''} className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm" placeholder="To" />
        <button type="submit" className="rounded-lg bg-slate-600 px-3 py-1.5 text-sm text-white">Filter</button>
      </form>

      <div className="overflow-hidden rounded-lg border border-slate-200/80 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Blotter #</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Type</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Complainant / Respondent</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Date</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Status</th>
              <th className="px-4 py-2 text-right text-xs font-medium text-slate-500">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200">
            {incidents?.data?.length ? incidents.data.map((inc) => (
              <tr key={inc.id}>
                <td className="px-4 py-2 font-mono text-sm">{inc.blotter_number ?? '—'}</td>
                <td className="px-4 py-2 text-sm">{inc.incident_type}</td>
                <td className="px-4 py-2 text-sm">{inc.complainant_name} / {inc.respondent_name}</td>
                <td className="px-4 py-2 text-sm">{inc.incident_date ? new Date(inc.incident_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
                <td className="px-4 py-2">
                  <span className={`rounded px-2 py-0.5 text-xs ${STATUS_CLASS[inc.status] || 'bg-slate-100 text-slate-800'}`}>
                    {statuses?.[inc.status] ?? inc.status}
                  </span>
                </td>
                <td className="px-4 py-2 text-right">
                  <Link href={`/incidents/${inc.id}`} className="font-medium text-[#635bff] hover:underline">View</Link>
                  {canEdit !== false && (
                    <Link href={`/incidents/${inc.id}/edit`} className="ml-2 text-slate-600 hover:underline">Edit</Link>
                  )}
                </td>
              </tr>
            )) : (
              <tr>
                <td colSpan={6} className="px-4 py-8 text-center text-slate-500">No incidents found.</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
      {incidents?.links && (
        <div className="mt-4 flex flex-wrap gap-1">
          {incidents.links.map((link, i) => (
            <Link
              key={i}
              href={link.url || '#'}
              className={`rounded px-3 py-1 text-sm ${link.active ? 'bg-devias-primary text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'} ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
            >
              {link.label}
            </Link>
          ))}
        </div>
      )}
    </TenantLayout>
  );
}
