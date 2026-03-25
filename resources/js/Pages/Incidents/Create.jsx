import { Link, useForm } from '@inertiajs/react';
import TenantLayout from '../Layouts/TenantLayout';

export default function IncidentsCreate({ statuses }) {
  const { data, setData, post, processing, errors } = useForm({
    incident_type: '',
    description: '',
    location: '',
    incident_date: new Date().toISOString().slice(0, 16),
    complainant_name: '',
    complainant_contact: '',
    complainant_address: '',
    respondent_name: '',
    respondent_contact: '',
    respondent_address: '',
    status: 'open',
    attachments: [],
  });

  const submit = (e) => {
    e.preventDefault();
    post('/incidents', { forceFormData: true });
  };

  return (
    <TenantLayout>
      <h1 className="mb-6 text-2xl font-bold text-slate-800">Report an Incident</h1>
      {errors && Object.keys(errors).length > 0 && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
          <ul className="list-disc pl-4">
            {Object.values(errors).flat().map((msg, i) => (
              <li key={i}>{msg}</li>
            ))}
          </ul>
        </div>
      )}
      <form onSubmit={submit} className="max-w-2xl space-y-4 rounded-lg bg-white p-6 shadow">
        <div className="grid gap-4 sm:grid-cols-2">
          <div>
            <label htmlFor="incident_type" className="mb-1 block text-sm font-medium text-slate-700">Incident type</label>
            <input
              id="incident_type"
              type="text"
              value={data.incident_type}
              onChange={(e) => setData('incident_type', e.target.value)}
              className="w-full rounded-lg border border-slate-300 px-3 py-2"
              placeholder="e.g. Boundary dispute, Noise complaint"
              required
            />
          </div>
          <div>
            <label htmlFor="incident_date" className="mb-1 block text-sm font-medium text-slate-700">Incident date</label>
            <input
              id="incident_date"
              type="datetime-local"
              value={data.incident_date}
              onChange={(e) => setData('incident_date', e.target.value)}
              className="w-full rounded-lg border border-slate-300 px-3 py-2"
              required
            />
          </div>
        </div>
        <div>
          <label htmlFor="description" className="mb-1 block text-sm font-medium text-slate-700">Description</label>
          <textarea
            id="description"
            rows={4}
            value={data.description}
            onChange={(e) => setData('description', e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-3 py-2"
            required
          />
        </div>
        <div>
          <label htmlFor="location" className="mb-1 block text-sm font-medium text-slate-700">Location (optional)</label>
          <input
            id="location"
            type="text"
            value={data.location}
            onChange={(e) => setData('location', e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-3 py-2"
          />
        </div>
        <div className="border-t border-slate-200 pt-4">
          <h3 className="mb-2 font-medium text-slate-700">Complainant</h3>
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="sm:col-span-2">
              <label htmlFor="complainant_name" className="mb-1 block text-sm text-slate-600">Name *</label>
              <input id="complainant_name" type="text" value={data.complainant_name} onChange={(e) => setData('complainant_name', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" required />
            </div>
            <div>
              <label htmlFor="complainant_contact" className="mb-1 block text-sm text-slate-600">Contact</label>
              <input id="complainant_contact" type="text" value={data.complainant_contact} onChange={(e) => setData('complainant_contact', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
            <div className="sm:col-span-3">
              <label htmlFor="complainant_address" className="mb-1 block text-sm text-slate-600">Address</label>
              <input id="complainant_address" type="text" value={data.complainant_address} onChange={(e) => setData('complainant_address', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
          </div>
        </div>
        <div className="border-t border-slate-200 pt-4">
          <h3 className="mb-2 font-medium text-slate-700">Respondent</h3>
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="sm:col-span-2">
              <label htmlFor="respondent_name" className="mb-1 block text-sm text-slate-600">Name *</label>
              <input id="respondent_name" type="text" value={data.respondent_name} onChange={(e) => setData('respondent_name', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" required />
            </div>
            <div>
              <label htmlFor="respondent_contact" className="mb-1 block text-sm text-slate-600">Contact</label>
              <input id="respondent_contact" type="text" value={data.respondent_contact} onChange={(e) => setData('respondent_contact', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
            <div className="sm:col-span-3">
              <label htmlFor="respondent_address" className="mb-1 block text-sm text-slate-600">Address</label>
              <input id="respondent_address" type="text" value={data.respondent_address} onChange={(e) => setData('respondent_address', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
          </div>
        </div>
        <div>
          <label htmlFor="status" className="mb-1 block text-sm font-medium text-slate-700">Status</label>
          <select id="status" value={data.status} onChange={(e) => setData('status', e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2">
            {statuses && Object.entries(statuses).map(([k, v]) => (
              <option key={k} value={k}>{v}</option>
            ))}
          </select>
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Attachments (optional)</label>
          <input
            type="file"
            multiple
            accept=".pdf,.jpg,.jpeg,.png"
            className="w-full text-sm"
            onChange={(e) => setData('attachments', Array.from(e.target.files || []))}
          />
        </div>
        <div className="flex gap-2">
          <button type="submit" disabled={processing} className="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:opacity-70">
            Save incident
          </button>
          <Link href="/incidents" className="rounded-lg bg-slate-200 px-4 py-2 text-slate-700 hover:bg-slate-300">Cancel</Link>
        </div>
      </form>
    </TenantLayout>
  );
}
