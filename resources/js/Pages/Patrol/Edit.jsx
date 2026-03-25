import { Link, useForm } from '@inertiajs/react';
import TenantLayout from '../Layouts/TenantLayout';

export default function PatrolEdit({ patrol }) {
  const patrolDate = patrol.patrol_date ? new Date(patrol.patrol_date).toISOString().slice(0, 10) : '';
  const startTime = patrol.start_time ? String(patrol.start_time).slice(0, 5) : '';
  const endTime = patrol.end_time ? String(patrol.end_time).slice(0, 5) : '';
  const { data, setData, put, processing } = useForm({
    patrol_date: patrolDate,
    start_time: startTime,
    end_time: endTime,
    area_patrolled: patrol.area_patrolled || '',
    activities: patrol.activities || '',
    incidents_observed: patrol.incidents_observed || '',
    response_details: patrol.response_details || '',
    response_time_minutes: patrol.response_time_minutes ?? '',
  });

  return (
    <TenantLayout>
      <h1 className="mb-6 text-2xl font-bold text-slate-800">Edit patrol log</h1>
      <form onSubmit={(e) => { e.preventDefault(); put(`/patrol/${patrol.id}`); }} className="max-w-2xl space-y-4 rounded-lg bg-white p-6 shadow">
        <div className="grid gap-4 sm:grid-cols-2">
          <div>
            <label htmlFor="patrol_date" className="mb-1 block text-sm font-medium text-slate-700">Date</label>
            <input id="patrol_date" type="date" value={data.patrol_date} onChange={(e) => setData('patrol_date', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" required />
          </div>
          <div className="grid grid-cols-2 gap-2">
            <div>
              <label htmlFor="start_time" className="mb-1 block text-sm text-slate-600">Start time</label>
              <input id="start_time" type="time" value={data.start_time} onChange={(e) => setData('start_time', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
            <div>
              <label htmlFor="end_time" className="mb-1 block text-sm text-slate-600">End time</label>
              <input id="end_time" type="time" value={data.end_time} onChange={(e) => setData('end_time', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
          </div>
        </div>
        <div>
          <label htmlFor="area_patrolled" className="mb-1 block text-sm font-medium text-slate-700">Area patrolled</label>
          <input id="area_patrolled" type="text" value={data.area_patrolled} onChange={(e) => setData('area_patrolled', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label htmlFor="activities" className="mb-1 block text-sm font-medium text-slate-700">Activities</label>
          <textarea id="activities" rows={3} value={data.activities} onChange={(e) => setData('activities', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label htmlFor="incidents_observed" className="mb-1 block text-sm font-medium text-slate-700">Incidents observed</label>
          <textarea id="incidents_observed" rows={2} value={data.incidents_observed} onChange={(e) => setData('incidents_observed', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div className="grid gap-4 sm:grid-cols-2">
          <div>
            <label htmlFor="response_details" className="mb-1 block text-sm text-slate-600">Response details</label>
            <textarea id="response_details" rows={2} value={data.response_details} onChange={(e) => setData('response_details', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label htmlFor="response_time_minutes" className="mb-1 block text-sm text-slate-600">Response time (minutes)</label>
            <input id="response_time_minutes" type="number" min={0} value={data.response_time_minutes} onChange={(e) => setData('response_time_minutes', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
        </div>
        <div className="flex gap-2">
          <button type="submit" disabled={processing} className="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:opacity-70">Update</button>
          <Link href="/patrol" className="rounded-lg bg-slate-200 px-4 py-2 text-slate-700 hover:bg-slate-300">Cancel</Link>
        </div>
      </form>
    </TenantLayout>
  );
}
