import { Link } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';

export default function SuperTenants({ tenants }) {
  const list = Array.isArray(tenants) ? tenants : [];

  return (
    <AppLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-slate-800">All Barangays — Malaybalay City</h1>
        <Link href="/super/dashboard" className="rounded-lg bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">Dashboard</Link>
      </div>
      <div className="overflow-hidden rounded-lg bg-white shadow">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Barangay</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">District</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Plan</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Incidents</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Active</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200">
            {list.map((t) => (
              <tr key={t.id}>
                <td className="px-4 py-2 font-medium">{t.name}</td>
                <td className="px-4 py-2 text-sm">{t.barangay ?? '—'}</td>
                <td className="px-4 py-2 text-sm">{t.plan?.name}</td>
                <td className="px-4 py-2 text-sm">{t.incidents_count ?? 0}</td>
                <td className="px-4 py-2 text-sm">{t.is_active ? 'Yes' : 'No'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </AppLayout>
  );
}
