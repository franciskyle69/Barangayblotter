import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import TenantLayout from '../Layouts/TenantLayout';

const STATUS_CLASS = {
  pending: 'bg-amber-100 text-amber-800',
  approved: 'bg-blue-100 text-blue-800',
  printed: 'bg-emerald-100 text-emerald-800',
  rejected: 'bg-slate-100 text-slate-800',
};

export default function BlotterRequestsIndex({ requests, role, filters = {} }) {
  const items = requests?.data ?? requests ?? [];
  const isStaff = !['resident', 'citizen'].includes(role);
  const [remarksById, setRemarksById] = useState({});

  const handleFilter = (e) => {
    e.preventDefault();
    const form = e.target;
    const params = {};
    if (form.status?.value) params.status = form.status.value;
    if (form.from?.value) params.from = form.from.value;
    if (form.to?.value) params.to = form.to.value;
    router.get('/blotter-requests', params, { preserveState: true });
  };

  const setRemark = (id, value) => {
    setRemarksById((prev) => ({ ...prev, [id]: value }));
  };

  return (
    <TenantLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-slate-800">Blotter / certified copy requests</h1>
        <Link href="/blotter-requests/create" className="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Request copy</Link>
      </div>

      <form method="GET" onSubmit={handleFilter} className="mb-4 flex flex-wrap gap-2">
        <select
          name="status"
          className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
          defaultValue={filters.status || ''}
        >
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
        <input type="date" name="from" defaultValue={filters.from || ''} className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
        <input type="date" name="to" defaultValue={filters.to || ''} className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
        <button type="submit" className="rounded-lg bg-slate-600 px-3 py-1.5 text-sm text-white">Filter</button>
      </form>

      <div className="overflow-hidden rounded-lg bg-white shadow">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Incident</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Requested by</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Purpose</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Remarks</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Reviewed by</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Status</th>
              {isStaff && <th className="px-4 py-2 text-right text-xs font-medium text-slate-500">Actions</th>}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200">
            {items.length ? items.map((req) => (
              <tr key={req.id}>
                <td className="px-4 py-2">
                  <Link href={`/incidents/${req.incident?.id}`} className="text-emerald-600 hover:underline">
                    {req.incident?.blotter_number ?? `#${req.incident?.id}`}
                  </Link>
                </td>
                <td className="px-4 py-2 text-sm">{req.requested_by?.name}</td>
                <td className="px-4 py-2 text-sm">{req.purpose ?? '—'}</td>
                <td className="px-4 py-2 text-sm text-slate-600">{req.remarks ?? '—'}</td>
                <td className="px-4 py-2 text-sm text-slate-600">{req.reviewed_by?.name ?? '—'}</td>
                <td className="px-4 py-2">
                  <span className={`rounded px-2 py-0.5 text-xs ${STATUS_CLASS[req.status] || 'bg-slate-100 text-slate-800'}`}>
                    {req.status}
                  </span>
                </td>
                {isStaff && (
                  <td className="px-4 py-2 text-right">
                    {req.status === 'pending' && (
                      <div className="flex flex-col items-end gap-2">
                        <textarea
                          value={remarksById[req.id] ?? ''}
                          onChange={(e) => setRemark(req.id, e.target.value)}
                          rows={2}
                          className="w-56 rounded border border-slate-300 px-2 py-1 text-xs"
                          placeholder="Optional remarks"
                        />
                        <div>
                          <button
                            type="button"
                            onClick={() => router.post(`/blotter-requests/${req.id}/approve`, { remarks: remarksById[req.id] ?? '' })}
                            className="text-emerald-600 hover:underline"
                          >
                            Approve
                          </button>
                          <button
                            type="button"
                            onClick={() => router.post(`/blotter-requests/${req.id}/reject`, { remarks: remarksById[req.id] ?? '' })}
                            className="ml-2 text-red-600 hover:underline"
                          >
                            Reject
                          </button>
                        </div>
                      </div>
                    )}
                  </td>
                )}
              </tr>
            )) : (
              <tr>
                <td colSpan={isStaff ? 7 : 6} className="px-4 py-8 text-center text-slate-500">No requests.</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
      {requests?.links && (
        <div className="mt-4 flex flex-wrap gap-1">
          {requests.links.map((link, i) => (
            <Link key={i} href={link.url || '#'} className={`rounded px-3 py-1 text-sm ${link.active ? 'bg-devias-primary text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'} ${!link.url ? 'pointer-events-none opacity-50' : ''}`}>
              {link.label}
            </Link>
          ))}
        </div>
      )}
    </TenantLayout>
  );
}
