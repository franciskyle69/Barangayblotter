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
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-slate-800">Malaybalay City — Central Monitoring</h1>
        <Link href="/super/tenants" className="rounded-devias bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">All Barangays</Link>
      </div>
      <div className="mb-8 grid gap-4 sm:grid-cols-3">
        <div className="rounded-devias border border-slate-200/80 bg-white p-4 shadow-sm">
          <p className="text-sm font-medium text-slate-500">Total incidents</p>
          <p className="mt-2 text-2xl font-bold text-slate-800">{totalIncidents ?? 0}</p>
        </div>
        <div className="rounded-devias border border-slate-200/80 bg-white p-4 shadow-sm">
          <p className="text-sm font-medium text-slate-500">This month</p>
          <p className="mt-2 text-2xl font-bold text-emerald-600">{incidentsThisMonth ?? 0}</p>
        </div>
        <div className="rounded-devias border border-slate-200/80 bg-white p-4 shadow-sm">
          <p className="text-sm font-medium text-slate-500">Barangays</p>
          <p className="mt-2 text-2xl font-bold text-slate-800">{Array.isArray(tenants) ? tenants.length : 0}</p>
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
        <h2 className="mb-3 font-semibold text-slate-800">Recent incidents (all barangays)</h2>
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Barangay</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Blotter / Type</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Status</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Date</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200">
            {list.map((inc) => (
              <tr key={inc.id}>
                <td className="px-4 py-2 text-sm">{inc.tenant?.name}</td>
                <td className="px-4 py-2 text-sm">{inc.blotter_number ?? `#${inc.id}`} — {inc.incident_type}</td>
                <td className="px-4 py-2 text-sm">{inc.status}</td>
                <td className="px-4 py-2 text-sm">{inc.created_at ? new Date(inc.created_at).toLocaleDateString('en-US') : '—'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </CentralLayout>
  );
}
