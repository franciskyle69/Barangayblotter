import { Link } from '@inertiajs/react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  Legend,
} from 'recharts';
import CentralLayout from '../Layouts/CentralLayout';

const STATUS_LABELS = {
  open: 'Open',
  under_mediation: 'Under Mediation',
  settled: 'Settled',
  escalated_to_barangay: 'Escalated',
};
const CHART_COLORS = ['#f59e0b', '#3b82f6', '#10b981', '#475569'];

const StatCard = ({ label, value, tone = 'slate', icon, sub, ctaHref, ctaLabel }) => {
  const toneClasses =
    tone === 'good'
      ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
      : tone === 'warn'
        ? 'bg-amber-50 text-amber-800 ring-amber-200'
        : 'bg-slate-50 text-slate-700 ring-slate-200';

  return (
    <div className="group relative overflow-hidden rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-sm font-medium text-slate-500">{label}</p>
          <p className="mt-2 text-2xl font-bold text-slate-900">{value ?? 0}</p>
          {sub && <p className="mt-1 text-xs text-slate-400">{sub}</p>}
          {ctaHref && ctaLabel && (
            <Link href={ctaHref} className="mt-3 inline-flex text-xs font-semibold text-slate-700 hover:underline">
              {ctaLabel}
            </Link>
          )}
        </div>
        <div className="flex flex-col items-end gap-2">
          <span className={`inline-flex items-center rounded-full px-2 py-1 text-[11px] font-semibold ring-1 ${toneClasses}`}>
            {tone === 'good' ? 'Healthy' : tone === 'warn' ? 'Needs attention' : 'Summary'}
          </span>
          <span className="grid size-10 place-items-center rounded-devias bg-slate-900/5 text-slate-700 transition group-hover:bg-slate-900/10">
            {icon}
          </span>
        </div>
      </div>
      <div className="pointer-events-none absolute -right-10 -top-10 size-24 rounded-full bg-slate-900/5 blur-2xl" />
    </div>
  );
};

const StatusBadge = ({ status }) => {
  const s = String(status || '').toLowerCase();
  const tone =
    s === 'open'
      ? 'bg-amber-50 text-amber-800 ring-amber-200'
      : s === 'under_mediation'
        ? 'bg-blue-50 text-blue-800 ring-blue-200'
        : s === 'settled'
          ? 'bg-emerald-50 text-emerald-800 ring-emerald-200'
          : 'bg-slate-50 text-slate-800 ring-slate-200';

  const label = STATUS_LABELS[s] || s.replace(/_/g, ' ') || '—';
  return <span className={`inline-flex items-center rounded-full px-2 py-1 text-[11px] font-semibold ring-1 ${tone}`}>{label}</span>;
};

export default function SuperDashboard({ tenants, totalIncidents, incidentsThisMonth, byStatus, recentIncidents }) {
  const statusCounts = byStatus || {};
  const list = Array.isArray(recentIncidents) ? recentIncidents : [];
  const statusChartData = Object.entries(statusCounts).map(([status, total]) => ({
    name: STATUS_LABELS[status] || status.replace(/_/g, ' '),
    count: total,
    value: total,
    fill: CHART_COLORS[Object.keys(statusCounts).indexOf(status) % CHART_COLORS.length],
  }));
  const statusPieData = statusChartData.filter((d) => d.value > 0);
  const totalStatusCity = statusPieData.reduce((s, d) => s + d.value, 0);
  const barangayChartData = Array.isArray(tenants)
    ? tenants.map((t) => ({ name: t.name, incidents: t.incidents_count ?? 0 })).filter((d) => d.name)
    : [];

  return (
    <CentralLayout>
      <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-slate-800">Central monitoring</h1>
          <p className="mt-1 text-sm text-slate-500">
            City-wide activity across barangays, incidents, and support.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Link href="/super/tenant-signup-requests" className="rounded-devias bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            Review signup requests
          </Link>
          <Link href="/super/tenants" className="rounded-devias border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            All barangays
          </Link>
        </div>
      </div>

      <div className="mb-8 grid gap-4 sm:grid-cols-3">
        <StatCard
          label="Total incidents"
          value={totalIncidents ?? 0}
          icon={
            <svg viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M4 4h16v16H4z" />
              <path d="M8 9h8" />
              <path d="M8 13h8" />
              <path d="M8 17h5" />
            </svg>
          }
          ctaHref="/super/activity-logs"
          ctaLabel="View activity logs"
        />
        <StatCard
          label="This month"
          value={incidentsThisMonth ?? 0}
          tone={Number(incidentsThisMonth ?? 0) > 0 ? 'good' : 'slate'}
          icon={
            <svg viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M4 19V5" />
              <path d="M4 19h16" />
              <path d="M7 15l4-4 3 3 6-7" />
            </svg>
          }
          sub="Counts incidents created this calendar month"
        />
        <StatCard
          label="Barangays"
          value={Array.isArray(tenants) ? tenants.length : 0}
          icon={
            <svg viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M3 21h18" />
              <path d="M5 21V9l7-5 7 5v12" />
              <path d="M9 21v-6h6v6" />
            </svg>
          }
          ctaHref="/super/tenants"
          ctaLabel="Manage barangays"
        />
      </div>

      <div className="mb-8 grid gap-6 lg:grid-cols-3">
        <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm lg:col-span-2">
          <div className="mb-4 flex items-center justify-between gap-3">
            <h2 className="font-semibold text-slate-800">Quick actions</h2>
            <span className="text-xs font-medium text-slate-500">Admin</span>
          </div>
          <div className="grid gap-2 sm:grid-cols-2">
            <Link href="/super/tenant-signup-requests" className="rounded-devias border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50">
              Review signups
              <p className="mt-1 text-xs font-medium text-slate-500">Approve or reject new tenant requests</p>
            </Link>
            <Link href="/super/support" className="rounded-devias border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50">
              Support inbox
              <p className="mt-1 text-xs font-medium text-slate-500">Respond to barangay tickets</p>
            </Link>
            <Link href="/super/backup-restore" className="rounded-devias border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50">
              Backup & restore
              <p className="mt-1 text-xs font-medium text-slate-500">Run backups or restore a snapshot</p>
            </Link>
            <Link href="/super/releases" className="rounded-devias border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50">
              Publish release
              <p className="mt-1 text-xs font-medium text-slate-500">Build and attach `release.zip`</p>
            </Link>
          </div>
        </div>
        <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
          <h2 className="mb-4 font-semibold text-slate-800">Highlights</h2>
          <div className="space-y-3 text-sm text-slate-700">
            <div className="rounded-devias border border-slate-200/70 bg-slate-50 p-3">
              <p className="font-semibold text-slate-900">City-wide status mix</p>
              <p className="mt-1 text-slate-600">
                {totalStatusCity > 0 ? 'Charts update as incidents change.' : 'No incident data yet — create incidents to populate charts.'}
              </p>
            </div>
            <div className="rounded-devias border border-slate-200/70 bg-slate-50 p-3">
              <p className="font-semibold text-slate-900">Operational tip</p>
              <p className="mt-1 text-slate-600">Use Support to track recurring issues across barangays.</p>
            </div>
          </div>
        </div>
      </div>

      <div className="mb-8 grid gap-6 lg:grid-cols-2">
        {statusPieData.length > 0 && (
          <>
            {/* GitHub-style donut: incidents by status (city-wide) */}
            <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
              <h2 className="mb-4 font-semibold text-slate-800">Incidents by status</h2>
              <div className="flex flex-col items-center sm:flex-row sm:items-start">
                <div className="h-52 w-52 shrink-0">
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie
                        data={statusPieData}
                        cx="50%"
                        cy="50%"
                        innerRadius={52}
                        outerRadius={88}
                        paddingAngle={1}
                        dataKey="value"
                        stroke="none"
                      >
                        {statusPieData.map((entry, i) => (
                          <Cell key={i} fill={entry.fill} />
                        ))}
                      </Pie>
                      <Tooltip
                        contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.08)' }}
                        formatter={(value, name) => [
                          `${value} (${totalStatusCity ? Math.round((Number(value) / totalStatusCity) * 100) : 0}%)`,
                          name,
                        ]}
                      />
                    </PieChart>
                  </ResponsiveContainer>
                </div>
                <Legend
                  layout="vertical"
                  align="right"
                  verticalAlign="middle"
                  wrapperStyle={{ paddingLeft: 16 }}
                  formatter={(value, entry) => (
                    <span className="text-sm text-slate-700">
                      <span
                        className="mr-2 inline-block h-3 w-3 shrink-0 rounded-sm"
                        style={{ backgroundColor: entry.color }}
                      />
                      {value}{' '}
                      <span className="text-slate-500">
                        ({totalStatusCity && entry.payload?.value != null
                          ? Math.round((entry.payload.value / totalStatusCity) * 100)
                          : 0}
                        %)
                      </span>
                    </span>
                  )}
                />
              </div>
            </div>
            {/* Bar chart (same data) */}
            <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
              <h2 className="mb-4 font-semibold text-slate-800">Incidents by status (bar)</h2>
              <div className="h-52">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={statusChartData} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                    <XAxis dataKey="name" tick={{ fontSize: 12 }} stroke="#64748b" />
                    <YAxis tick={{ fontSize: 12 }} stroke="#64748b" allowDecimals={false} />
                    <Tooltip
                      contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0' }}
                      formatter={(value) => [value, 'Count']}
                    />
                    <Bar dataKey="count" radius={[4, 4, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          </>
        )}
        {barangayChartData.length > 0 && (
          <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
            <h2 className="mb-4 font-semibold text-slate-800">Incidents by barangay</h2>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={barangayChartData} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                  <XAxis dataKey="name" tick={{ fontSize: 11 }} stroke="#64748b" angle={-35} textAnchor="end" height={60} />
                  <YAxis tick={{ fontSize: 12 }} stroke="#64748b" allowDecimals={false} />
                  <Tooltip
                    contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0' }}
                    formatter={(value) => [value, 'Incidents']}
                  />
                  <Bar dataKey="incidents" fill="#635bff" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>
        )}
      </div>

      {statusChartData.length === 0 && (
        <div className="mb-6 rounded-devias border border-slate-200/80 bg-white p-4 shadow-sm">
          <h2 className="mb-3 font-semibold text-slate-800">By status</h2>
          <div className="flex flex-wrap gap-4">
            {Object.entries(statusCounts).map(([status, total]) => (
              <span key={status} className="rounded bg-slate-100 px-3 py-1 text-sm">{status}: {total}</span>
            ))}
          </div>
        </div>
      )}
      <div className="rounded-devias border border-slate-200/80 bg-white p-4 shadow-sm">
        <div className="mb-3 flex items-center justify-between gap-3">
          <h2 className="font-semibold text-slate-800">Recent incidents (all barangays)</h2>
          <Link href="/super/tenants" className="text-sm font-medium text-slate-700 hover:underline">Browse barangays</Link>
        </div>
        <div className="overflow-x-auto rounded-devias border border-slate-200/70">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
              <tr>
                <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Barangay</th>
                <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Blotter / Type</th>
                <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Status</th>
                <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Date</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 bg-white">
              {list.length ? (
                list.map((inc) => (
                  <tr key={inc.id} className="transition hover:bg-slate-50">
                    <td className="px-4 py-2 text-sm">{inc.tenant?.name}</td>
                    <td className="px-4 py-2 text-sm">{inc.blotter_number ?? `#${inc.id}`} — {inc.incident_type}</td>
                    <td className="px-4 py-2 text-sm"><StatusBadge status={inc.status} /></td>
                    <td className="px-4 py-2 text-sm">{inc.created_at ? new Date(inc.created_at).toLocaleDateString('en-US') : '—'}</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={4} className="px-4 py-6 text-center text-sm text-slate-500">No incidents yet.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </CentralLayout>
  );
}
