import { useForm, usePage } from '@inertiajs/react';

export default function TenantSelect({ tenants }) {
  const { logo_url } = usePage().props;
  const { data, setData, post } = useForm({ tenant_id: '' });

  return (
    <div className="flex min-h-screen items-center justify-center bg-[#f9fafb] px-4 font-sans antialiased">
      <div className="w-full max-w-md">
        <div className="mb-8 flex flex-col items-center gap-3">
          <img
            src={logo_url || '/images/logo.png'}
            alt="Logo"
            className="h-14 w-14 shrink-0 rounded-devias object-contain bg-white shadow-sm ring-1 ring-slate-200/80"
            onError={(e) => {
              e.target.style.display = 'none';
              e.target.nextSibling?.classList.remove('hidden');
            }}
          />
          <span className="hidden size-14 flex shrink-0 items-center justify-center rounded-devias bg-devias-primary text-xl font-bold text-white" aria-hidden>MB</span>
          <h1 className="text-center text-2xl font-bold text-slate-900">Barangay Blotter Tenancy</h1>
        </div>
        <div className="rounded-lg border border-slate-200/80 bg-white p-6 shadow-sm">
          <h2 className="mb-4 text-lg font-semibold text-slate-800">Select your Barangay</h2>
          <p className="mb-4 text-sm text-slate-600">Choose the barangay you want to access.</p>
          <form onSubmit={(e) => { e.preventDefault(); post('/tenant/select'); }}>
            <div className="space-y-2">
              {tenants?.map((t) => (
                <label key={t.id} className="flex cursor-pointer items-center gap-3 rounded border border-slate-200 p-3 hover:bg-slate-50">
                  <input
                    type="radio"
                    name="tenant_id"
                    value={t.id}
                    checked={data.tenant_id === String(t.id)}
                    onChange={() => setData('tenant_id', String(t.id))}
                    required
                  />
                  <span className="font-medium">{t.name}</span>
                  <span className="text-sm text-slate-500">({t.plan?.name})</span>
                </label>
              ))}
            </div>
            {tenants?.length ? (
              <button
                type="submit"
                className="mt-4 w-full rounded-devias bg-devias-primary px-4 py-2.5 font-semibold text-white shadow-sm hover:opacity-95"
              >
                Continue
              </button>
            ) : (
              <p className="text-slate-600">You are not assigned to any barangay yet. Contact your administrator.</p>
            )}
          </form>
        </div>
      </div>
    </div>
  );
}
