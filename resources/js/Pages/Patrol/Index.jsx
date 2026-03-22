import { Link, router } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';

export default function PatrolIndex({ patrolLogs }) {
  const items = patrolLogs?.data ?? patrolLogs ?? [];
  const handleFilter = (e) => {
    e.preventDefault();
    const date = e.target.date?.value;
    router.get('/patrol', date ? { date } : {});
  };

  return (
    <AppLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-slate-800">Patrol logs</h1>
        <Link href="/patrol/create" className="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Log patrol</Link>
      </div>
      <form method="GET" onSubmit={handleFilter} className="mb-4 flex gap-2">
        <input type="date" name="date" className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm" defaultValue={typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('date') || '' : ''} />
        <button type="submit" className="rounded-lg bg-slate-600 px-3 py-1.5 text-sm text-white">Filter</button>
      </form>
      <div className="space-y-3">
        {items.length ? items.map((log) => (
          <div key={log.id} className="rounded-lg bg-white p-4 shadow">
            <div className="flex items-start justify-between">
              <div>
                <p className="font-medium">{log.patrol_date ? new Date(log.patrol_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</p>
                <p className="text-sm text-slate-600">By {log.user?.name}</p>
                {log.area_patrolled && <p className="text-sm text-slate-500">Area: {log.area_patrolled}</p>}
                {log.start_time && <p className="text-sm text-slate-500">{log.start_time} – {log.end_time}</p>}
                {log.activities && <p className="mt-1 text-sm">{log.activities.length > 120 ? log.activities.slice(0, 120) + '…' : log.activities}</p>}
              </div>
              <Link href={`/patrol/${log.id}/edit`} className="text-emerald-600 hover:underline">Edit</Link>
            </div>
          </div>
        )) : (
          <p className="rounded-lg bg-white p-6 text-center text-slate-500 shadow">No patrol logs found.</p>
        )}
      </div>
      {patrolLogs?.links && (
        <div className="mt-4 flex flex-wrap gap-1">
          {patrolLogs.links.map((link, i) => (
            <Link key={i} href={link.url || '#'} className={`rounded px-3 py-1 text-sm ${link.active ? 'bg-devias-primary text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'} ${!link.url ? 'pointer-events-none opacity-50' : ''}`}>
              {link.label}
            </Link>
          ))}
        </div>
      )}
    </AppLayout>
  );
}
