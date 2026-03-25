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
import TenantLayout from './Layouts/TenantLayout';

const STATUS_LABELS = {
  open: 'Open',
  under_mediation: 'Under Mediation',
  settled: 'Settled',
  escalated_to_barangay: 'Escalated',
};
const CHART_COLORS = ['#f59e0b', '#3b82f6', '#10b981', '#475569'];

export default function Dashboard({ tenant, role, stats, recentIncidents, myBlotterRequests, canSeeAnalytics }) {
  const totalStatus = stats.open + stats.under_mediation + stats.settled + stats.escalated;
  const pieData = [
    { name: STATUS_LABELS.open, value: stats.open, fill: CHART_COLORS[0] },
    { name: STATUS_LABELS.under_mediation, value: stats.under_mediation, fill: CHART_COLORS[1] },
    { name: STATUS_LABELS.settled, value: stats.settled, fill: CHART_COLORS[2] },
    { name: STATUS_LABELS.escalated_to_barangay, value: stats.escalated, fill: CHART_COLORS[3] },
  ].filter((d) => d.value > 0);

  const roleLabels = {
    purok_secretary: 'Barangay Secretary',
    purok_leader: 'Barangay Captain',
    community_watch: 'Community Watch',
    mediator: 'Community Mediator',
    resident: 'Resident',
    citizen: 'Citizen',
  };

  return (
    <TenantLayout>
      {tenant && (
        <>
          <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
              <h1 className="text-2xl font-bold text-slate-900">Overview</h1>
              <p className="mt-1 text-sm text-slate-500">{roleLabels[role] || role} · {tenant.name}</p>
            </div>
            <Link
              href="/incidents/create"
              className="inline-flex items-center gap-2 rounded-devias bg-devias-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-95"
            >
              New incident
            </Link>
          </div>

          <div className="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
              <p className="text-sm font-medium text-slate-500">Incidents this month</p>
              <p className="mt-2 text-2xl font-bold text-slate-900">{stats.incidents_this_month}</p>
              {!tenant.plan?.has_unlimited && (
                <p className="mt-1 text-xs text-slate-400">Limit: {tenant.plan?.incident_limit_per_month}/month</p>
              )}
            </div>
            <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
              <p className="text-sm font-medium text-slate-500">Open</p>
              <p className="mt-2 text-2xl font-bold text-amber-600">{stats.open}</p>
            </div>
            <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
              <p className="text-sm font-medium text-slate-500">Under mediation</p>
              <p className="mt-2 text-2xl font-bold text-blue-600">{stats.under_mediation}</p>
            </div>
            <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
              <p className="text-sm font-medium text-slate-500">Settled</p>
              <p className="mt-2 text-2xl font-bold text-emerald-600">{stats.settled}</p>
            </div>
            <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
              <p className="text-sm font-medium text-slate-500">Escalated</p>
              <p className="mt-2 text-2xl font-bold text-slate-700">{stats.escalated}</p>
            </div>
          </div>

          {/* Charts: show when plan has analytics (even with zero data) */}
          {canSeeAnalytics && (
            <div className="mb-8 grid gap-6 lg:grid-cols-2">
              {/* GitHub-style donut: incidents by status */}
              <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
                <h2 className="mb-4 font-semibold text-slate-900">Incidents by status</h2>
                <div className="flex flex-col items-center sm:flex-row sm:items-start">
                  <div className="h-52 w-52 shrink-0">
                    <ResponsiveContainer width="100%" height="100%">
                      {pieData.length > 0 ? (
                        <PieChart>
                          <Pie
                            data={pieData}
                            cx="50%"
                            cy="50%"
                            innerRadius={52}
                            outerRadius={88}
                            paddingAngle={1}
                            dataKey="value"
                            stroke="none"
                          >
                            {pieData.map((entry, i) => (
                              <Cell key={i} fill={entry.fill} />
                            ))}
                          </Pie>
                          <Tooltip
                            contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.08)' }}
                            formatter={(value, name) => [
                              `${value} (${totalStatus ? Math.round((Number(value) / totalStatus) * 100) : 0}%)`,
                              name,
                            ]}
                          />
                        </PieChart>
                      ) : (
                        <div className="flex h-full w-full items-center justify-center rounded-full bg-slate-50 text-sm text-slate-500">
                          No incident data yet
                        </div>
                      )}
                    </ResponsiveContainer>
                  </div>
                  {pieData.length > 0 && (
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
                            ({totalStatus ? Math.round((entry.payload.value / totalStatus) * 100) : 0}%)
                          </span>
                        </span>
                      )}
                    />
                  )}
                </div>
              </div>
              {/* Bar chart (same data; always show so chart area is visible) */}
              <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
                <h2 className="mb-4 font-semibold text-slate-900">Incidents by status (bar)</h2>
                <div className="h-52">
                  <ResponsiveContainer width="100%" height="100%">
                    <BarChart
                      data={[
                        { name: STATUS_LABELS.open, count: stats.open, fill: CHART_COLORS[0] },
                        { name: STATUS_LABELS.under_mediation, count: stats.under_mediation, fill: CHART_COLORS[1] },
                        { name: STATUS_LABELS.settled, count: stats.settled, fill: CHART_COLORS[2] },
                        { name: STATUS_LABELS.escalated_to_barangay, count: stats.escalated, fill: CHART_COLORS[3] },
                      ]}
                      margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
                    >
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
            </div>
          )}

          <div className="grid gap-6 lg:grid-cols-2">
            <div className="rounded-devias border border-slate-200/80 bg-white shadow-sm">
              <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 className="font-semibold text-slate-900">Recent incidents</h2>
                <Link href="/incidents" className="text-sm font-medium text-devias-primary hover:underline">View all</Link>
              </div>
              <div className="divide-y divide-slate-100">
                {recentIncidents?.length ? recentIncidents.map((inc) => (
                  <Link key={inc.id} href={`/incidents/${inc.id}`} className="flex items-center justify-between px-5 py-3 transition hover:bg-slate-50">
                    <span className="font-medium text-slate-800">{inc.blotter_number || 'N/A'}</span>
                    <span className="text-sm text-slate-500">{inc.incident_type} · {inc.incident_date}</span>
                  </Link>
                )) : (
                  <p className="px-5 py-6 text-sm text-slate-500">No incidents yet.</p>
                )}
              </div>
            </div>
            <div className="rounded-devias border border-slate-200/80 bg-white shadow-sm">
              <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 className="font-semibold text-slate-900">My blotter requests</h2>
                <Link href="/blotter-requests" className="text-sm font-medium text-devias-primary hover:underline">View all</Link>
              </div>
              <div className="divide-y divide-slate-100">
                {myBlotterRequests?.length ? myBlotterRequests.map((req) => (
                  <div key={req.id} className="flex items-center justify-between px-5 py-3">
                    <span className="text-sm text-slate-800">Incident {req.incident?.blotter_number || req.incident_id}</span>
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{req.status}</span>
                  </div>
                )) : (
                  <p className="px-5 py-6 text-sm text-slate-500">No blotter requests.</p>
                )}
              </div>
            </div>
          </div>
        </>
      )}
    </TenantLayout>
  );
}
