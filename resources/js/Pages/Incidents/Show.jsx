import { Link, usePage } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';

const STATUS_CLASS = {
  open: 'bg-amber-100 text-amber-800',
  under_mediation: 'bg-blue-100 text-blue-800',
  settled: 'bg-emerald-100 text-emerald-800',
  escalated_to_barangay: 'bg-slate-100 text-slate-800',
};

export default function IncidentsShow({ incident, role }) {
  const { current_tenant } = usePage().props;
  const canEdit = role !== 'resident';
  const canScheduleMediation = current_tenant?.plan?.mediation_scheduling &&
    incident.status !== 'settled' && incident.status !== 'escalated_to_barangay';
  const blotterLabel = incident.blotter_number ?? `#${incident.id}`;

  const formatDate = (d) => d ? new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';

  return (
    <AppLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-slate-800">Incident {blotterLabel}</h1>
        {canEdit && (
          <div className="flex gap-2">
            <Link href={`/incidents/${incident.id}/edit`} className="rounded-lg bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">Edit</Link>
            {canScheduleMediation && (
              <Link href={`/incidents/${incident.id}/mediations/create`} className="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Schedule mediation</Link>
            )}
            <Link href={`/blotter-requests/create?incident_id=${incident.id}`} className="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Request certified copy</Link>
          </div>
        )}
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="rounded-lg bg-white p-6 shadow">
          <dl className="space-y-3">
            <div>
              <dt className="text-sm text-slate-500">Type</dt>
              <dd className="font-medium">{incident.incident_type}</dd>
            </div>
            <div>
              <dt className="text-sm text-slate-500">Status</dt>
              <dd>
                <span className={`rounded px-2 py-0.5 text-sm ${STATUS_CLASS[incident.status] || 'bg-slate-100 text-slate-800'}`}>
                  {incident.status?.replace(/_/g, ' ') ?? incident.status}
                </span>
              </dd>
            </div>
            <div>
              <dt className="text-sm text-slate-500">Incident date</dt>
              <dd>{formatDate(incident.incident_date)}</dd>
            </div>
            <div>
              <dt className="text-sm text-slate-500">Location</dt>
              <dd>{incident.location ?? '—'}</dd>
            </div>
            <div>
              <dt className="text-sm text-slate-500">Description</dt>
              <dd className="whitespace-pre-wrap">{incident.description}</dd>
            </div>
            {incident.reported_by && (
              <div>
                <dt className="text-sm text-slate-500">Recorded by</dt>
                <dd>{incident.reported_by.name}</dd>
              </div>
            )}
          </dl>
        </div>
        <div className="space-y-6">
          <div className="rounded-lg bg-white p-6 shadow">
            <h3 className="mb-3 font-semibold text-slate-800">Complainant</h3>
            <p className="font-medium">{incident.complainant_name}</p>
            {incident.complainant_contact && <p className="text-sm text-slate-600">{incident.complainant_contact}</p>}
            {incident.complainant_address && <p className="text-sm text-slate-600">{incident.complainant_address}</p>}
          </div>
          <div className="rounded-lg bg-white p-6 shadow">
            <h3 className="mb-3 font-semibold text-slate-800">Respondent</h3>
            <p className="font-medium">{incident.respondent_name}</p>
            {incident.respondent_contact && <p className="text-sm text-slate-600">{incident.respondent_contact}</p>}
            {incident.respondent_address && <p className="text-sm text-slate-600">{incident.respondent_address}</p>}
          </div>
          {incident.attachments?.length > 0 && (
            <div className="rounded-lg bg-white p-6 shadow">
              <h3 className="mb-3 font-semibold text-slate-800">Attachments</h3>
              <ul className="space-y-1">
                {incident.attachments.map((att) => (
                  <li key={att.id}>
                    <a href={`/storage/${att.file_path}`} target="_blank" rel="noreferrer" className="text-emerald-600 hover:underline">
                      {att.original_name || 'Attachment'}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          )}
          {incident.mediations?.length > 0 && (
            <div className="rounded-lg bg-white p-6 shadow">
              <h3 className="mb-3 font-semibold text-slate-800">Mediations</h3>
              {incident.mediations.map((med) => (
                <div key={med.id} className="mb-3 border-b border-slate-100 pb-3 last:border-0">
                  <p className="font-medium">Scheduled: {formatDate(med.scheduled_at)}</p>
                  <p className="text-sm text-slate-600">Mediator: {med.mediator?.name}</p>
                  <p className="text-sm">Status: {med.status}</p>
                  {med.settlement_terms && <p className="mt-1 text-sm">{med.settlement_terms}</p>}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
