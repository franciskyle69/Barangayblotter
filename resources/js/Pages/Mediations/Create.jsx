import { Link, useForm } from '@inertiajs/react';
import TenantLayout from '../Layouts/TenantLayout';

export default function MediationsCreate({ incident, mediators }) {
  const { data, setData, post, processing } = useForm({
    incident_id: incident.id,
    mediator_user_id: mediators?.[0]?.id?.toString() ?? '',
    scheduled_at: new Date().toISOString().slice(0, 16),
  });

  return (
    <TenantLayout>
      <h1 className="mb-6 text-2xl font-bold text-slate-800">Schedule mediation — Incident {incident?.blotter_number ?? `#${incident?.id}`}</h1>
      <form onSubmit={(e) => { e.preventDefault(); post('/mediations'); }} className="max-w-md space-y-4 rounded-lg bg-white p-6 shadow">
        <input type="hidden" name="incident_id" value={data.incident_id} />
        <div>
          <label htmlFor="mediator_user_id" className="mb-1 block text-sm font-medium text-slate-700">Mediator</label>
          <select
            id="mediator_user_id"
            value={data.mediator_user_id}
            onChange={(e) => setData('mediator_user_id', e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-3 py-2"
            required
          >
            {(mediators || []).map((m) => (
              <option key={m.id} value={m.id}>{m.name}</option>
            ))}
          </select>
        </div>
        <div>
          <label htmlFor="scheduled_at" className="mb-1 block text-sm font-medium text-slate-700">Date & time</label>
          <input
            id="scheduled_at"
            type="datetime-local"
            value={data.scheduled_at}
            onChange={(e) => setData('scheduled_at', e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-3 py-2"
            required
          />
        </div>
        <div className="flex gap-2">
          <button type="submit" disabled={processing} className="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:opacity-70">Schedule</button>
          <Link href={`/incidents/${incident.id}`} className="rounded-lg bg-slate-200 px-4 py-2 text-slate-700 hover:bg-slate-300">Cancel</Link>
        </div>
      </form>
    </TenantLayout>
  );
}
