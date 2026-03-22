import { Link, useForm } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';

export default function BlotterRequestsCreate({ incidents, initialIncidentId }) {
  const { data, setData, post, processing } = useForm({
    incident_id: initialIncidentId?.toString() ?? '',
    purpose: '',
  });

  return (
    <AppLayout>
      <h1 className="mb-6 text-2xl font-bold text-slate-800">Request certified blotter copy</h1>
      <form onSubmit={(e) => { e.preventDefault(); post('/blotter-requests'); }} className="max-w-md space-y-4 rounded-lg bg-white p-6 shadow">
        <div>
          <label htmlFor="incident_id" className="mb-1 block text-sm font-medium text-slate-700">Incident</label>
          <select
            id="incident_id"
            value={data.incident_id}
            onChange={(e) => setData('incident_id', e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-3 py-2"
            required
          >
            <option value="">Select incident</option>
            {(incidents || []).map((inc) => (
              <option key={inc.id} value={inc.id}>
                {inc.blotter_number ?? `#${inc.id}`} — {inc.incident_type} ({inc.incident_date ? new Date(inc.incident_date).toLocaleDateString('en-US') : ''})
              </option>
            ))}
          </select>
        </div>
        <div>
          <label htmlFor="purpose" className="mb-1 block text-sm font-medium text-slate-700">Purpose (optional)</label>
          <input
            id="purpose"
            type="text"
            value={data.purpose}
            onChange={(e) => setData('purpose', e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-3 py-2"
            placeholder="e.g. Legal requirement, Personal record"
          />
        </div>
        <div className="flex gap-2">
          <button type="submit" disabled={processing} className="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:opacity-70">Submit request</button>
          <Link href="/blotter-requests" className="rounded-lg bg-slate-200 px-4 py-2 text-slate-700 hover:bg-slate-300">Cancel</Link>
        </div>
      </form>
    </AppLayout>
  );
}
