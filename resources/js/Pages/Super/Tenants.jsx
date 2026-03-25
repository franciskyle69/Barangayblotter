import { Link, router } from '@inertiajs/react';
import CentralLayout from '../Layouts/CentralLayout';

export default function SuperTenants({ tenants }) {
  const list = Array.isArray(tenants) ? tenants : [];

  const handleToggle = (tenant) => {
    if (confirm(`Are you sure you want to ${tenant.is_active ? 'deactivate' : 'activate'} ${tenant.name}?`)) {
      router.post(`/super/tenants/${tenant.id}/toggle`);
    }
  };

  return (
    <CentralLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-slate-800">All Barangays — Malaybalay City</h1>
        <div className="flex gap-3">
          <Link href="/super/dashboard" className="rounded-lg bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">Dashboard</Link>
          <Link href="/super/tenants/create" className="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">+ Add Barangay</Link>
        </div>
      </div>
      <div className="overflow-hidden rounded-lg bg-white shadow">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Barangay</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">District</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Subdomain</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Custom Domain</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Plan</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Incidents</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Status</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200">
            {list.map((t) => (
              <tr key={t.id} className={!t.is_active ? 'bg-red-50 opacity-70' : ''}>
                <td className="px-4 py-2 font-medium">{t.name}</td>
                <td className="px-4 py-2 text-sm">{t.barangay ?? '—'}</td>
                <td className="px-4 py-2 text-sm font-mono text-blue-600">{t.subdomain ?? '—'}</td>
                <td className="px-4 py-2 text-sm font-mono text-purple-600">{t.custom_domain ?? '—'}</td>
                <td className="px-4 py-2 text-sm">{t.plan?.name}</td>
                <td className="px-4 py-2 text-sm">{t.incidents_count ?? 0}</td>
                <td className="px-4 py-2 text-sm">
                  <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${t.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                    {t.is_active ? 'Active' : 'Inactive'}
                  </span>
                </td>
                <td className="px-4 py-2 text-sm">
                  <div className="flex gap-2">
                    <Link href={`/super/tenants/${t.id}/edit`} className="text-blue-600 hover:text-blue-800 hover:underline">Edit</Link>
                    <Link href={`/super/tenants/${t.id}/users`} className="text-indigo-600 hover:text-indigo-800 hover:underline">Users</Link>
                    <button
                      onClick={() => handleToggle(t)}
                      className={`text-sm hover:underline ${t.is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800'}`}
                    >
                      {t.is_active ? 'Deactivate' : 'Activate'}
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </CentralLayout>
  );
}
