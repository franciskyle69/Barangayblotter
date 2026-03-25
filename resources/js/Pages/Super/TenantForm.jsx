import { useState } from 'react';
import { useForm, Link, router } from '@inertiajs/react';
import CentralLayout from '../Layouts/CentralLayout';

export default function TenantForm({ tenant, plans }) {
  const isEditing = !!tenant;
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deleteConfirmation, setDeleteConfirmation] = useState('');
  const [deletingTenant, setDeletingTenant] = useState(false);
  
  const { data, setData, post, put, processing, errors } = useForm({
    name: tenant?.name ?? '',
    slug: tenant?.slug ?? '',
    subdomain: tenant?.subdomain ?? '',
    custom_domain: tenant?.custom_domain ?? '',
    barangay: tenant?.barangay ?? '',
    address: tenant?.address ?? '',
    contact_phone: tenant?.contact_phone ?? '',
    plan_id: tenant?.plan_id ?? (plans?.[0]?.id ?? ''),
    is_active: tenant?.is_active ?? true,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    if (isEditing) {
      put(`/super/tenants/${tenant.id}`);
    } else {
      post('/super/tenants');
    }
  };

  const handleDeleteTenant = () => {
    if (deleteConfirmation !== tenant.name) {
      alert('Barangay name does not match. Please try again.');
      return;
    }

    setDeletingTenant(true);
    router.delete(`/super/tenants/${tenant.id}`, {
      onSuccess: () => {
        setShowDeleteModal(false);
        setDeleteConfirmation('');
      },
      onFinish: () => {
        setDeletingTenant(false);
      },
    });
  };

  const inputClass = "w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500";
  const labelClass = "block text-sm font-medium text-slate-700 mb-1";
  const errorClass = "text-xs text-red-600 mt-1";

  return (
    <CentralLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-slate-800">
          {isEditing ? `Edit ${tenant.name}` : 'Add New Barangay'}
        </h1>
        <div className="flex gap-2">
          {isEditing && (
            <Link href={`/super/tenants/${tenant.id}/users`} className="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
              Manage Users
            </Link>
          )}
          <Link href="/super/tenants" className="rounded-lg bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">
            ← Back to List
          </Link>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6 rounded-lg bg-white p-6 shadow">
        {/* Basic Info */}
        <div className="border-b border-slate-200 pb-4">
          <h2 className="mb-4 text-lg font-semibold text-slate-700">Basic Information</h2>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label className={labelClass}>Barangay Name *</label>
              <input type="text" value={data.name} onChange={e => setData('name', e.target.value)} className={inputClass} required />
              {errors.name && <p className={errorClass}>{errors.name}</p>}
            </div>
            <div>
              <label className={labelClass}>Slug *</label>
              <input type="text" value={data.slug} onChange={e => setData('slug', e.target.value)} className={inputClass} required placeholder="e.g. casisang" />
              {errors.slug && <p className={errorClass}>{errors.slug}</p>}
            </div>
            <div>
              <label className={labelClass}>District / Area</label>
              <input type="text" value={data.barangay} onChange={e => setData('barangay', e.target.value)} className={inputClass} />
              {errors.barangay && <p className={errorClass}>{errors.barangay}</p>}
            </div>
            <div>
              <label className={labelClass}>Plan *</label>
              <select value={data.plan_id} onChange={e => setData('plan_id', e.target.value)} className={inputClass} required>
                {(plans ?? []).map(p => (
                  <option key={p.id} value={p.id}>{p.name} — ₱{p.price_monthly}/mo</option>
                ))}
              </select>
              {errors.plan_id && <p className={errorClass}>{errors.plan_id}</p>}
            </div>
          </div>
        </div>

        {/* Domain Settings */}
        <div className="border-b border-slate-200 pb-4">
          <h2 className="mb-4 text-lg font-semibold text-slate-700">Domain Settings</h2>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label className={labelClass}>Subdomain</label>
              <div className="flex items-center">
                <input type="text" value={data.subdomain} onChange={e => setData('subdomain', e.target.value)} className={`${inputClass} rounded-r-none`} placeholder="casisang" />
                <span className="inline-flex items-center rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                  .{window?.location?.hostname?.replace(/^[^.]+\./, '') || 'app.com'}
                </span>
              </div>
              {errors.subdomain && <p className={errorClass}>{errors.subdomain}</p>}
            </div>
            <div>
              <label className={labelClass}>Custom Domain</label>
              <input type="text" value={data.custom_domain} onChange={e => setData('custom_domain', e.target.value)} className={inputClass} placeholder="barangay-casisang.gov.ph" />
              {errors.custom_domain && <p className={errorClass}>{errors.custom_domain}</p>}
            </div>
          </div>
        </div>

        {/* Contact Info */}
        <div className="border-b border-slate-200 pb-4">
          <h2 className="mb-4 text-lg font-semibold text-slate-700">Contact Information</h2>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label className={labelClass}>Address</label>
              <input type="text" value={data.address} onChange={e => setData('address', e.target.value)} className={inputClass} />
              {errors.address && <p className={errorClass}>{errors.address}</p>}
            </div>
            <div>
              <label className={labelClass}>Contact Phone</label>
              <input type="text" value={data.contact_phone} onChange={e => setData('contact_phone', e.target.value)} className={inputClass} />
              {errors.contact_phone && <p className={errorClass}>{errors.contact_phone}</p>}
            </div>
          </div>
        </div>

        {/* Status */}
        <div className="flex items-center gap-3">
          <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" checked={data.is_active} onChange={e => setData('is_active', e.target.checked)} className="h-4 w-4 rounded border-slate-300" />
            Active
          </label>
          <span className="text-xs text-slate-500">Inactive tenants cannot be accessed via subdomain or custom domain.</span>
        </div>

        {/* Delete Danger Zone */}
        {isEditing && (
          <div className="rounded-lg border-2 border-red-200 bg-red-50 p-6">
            <div className="mb-4">
              <h3 className="text-lg font-semibold text-red-900">⚠️ Danger Zone</h3>
              <p className="mt-2 text-sm text-red-800">
                Deleting this barangay will permanently remove all associated data including incidents, mediations, patrol logs, and blotter requests. This action cannot be undone.
              </p>
            </div>
            <button
              type="button"
              onClick={() => setShowDeleteModal(true)}
              className="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
            >
              Delete Barangay
            </button>
          </div>
        )}

        {/* Submit */}
        <div className="flex justify-end gap-3 pt-4">
          <Link href="/super/tenants" className="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Cancel</Link>
          <button type="submit" disabled={processing} className="rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
            {processing ? 'Saving…' : isEditing ? 'Update Barangay' : 'Create Barangay'}
          </button>
        </div>
      </form>

      {/* Delete Confirmation Modal */}
      {showDeleteModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="w-full max-w-md rounded-lg bg-white shadow-xl">
            <div className="border-b border-slate-200 px-6 py-4">
              <h2 className="text-xl font-bold text-red-900">Delete Barangay?</h2>
            </div>

            <div className="space-y-4 px-6 py-4">
              <div className="rounded-lg bg-red-50 p-4">
                <p className="text-sm font-semibold text-red-900">⚠️ This action is permanent!</p>
                <p className="mt-2 text-sm text-red-800">
                  You are about to delete <span className="font-bold">"{tenant.name}"</span> and all its associated data:
                </p>
                <ul className="mt-3 space-y-1 text-sm text-red-700">
                  <li>• All incidents and their attachments</li>
                  <li>• All mediations</li>
                  <li>• All patrol logs</li>
                  <li>• All blotter requests</li>
                  <li>• User associations</li>
                </ul>
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">
                  To confirm, type the barangay name: <span className="font-bold text-red-600">"{tenant.name}"</span>
                </label>
                <input
                  type="text"
                  value={deleteConfirmation}
                  onChange={(e) => setDeleteConfirmation(e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500"
                  placeholder={tenant.name}
                  autoFocus
                />
              </div>
            </div>

            <div className="flex gap-3 border-t border-slate-200 px-6 py-4">
              <button
                onClick={() => {
                  setShowDeleteModal(false);
                  setDeleteConfirmation('');
                }}
                disabled={deletingTenant}
                className="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
              >
                Cancel
              </button>
              <button
                onClick={handleDeleteTenant}
                disabled={deletingTenant || deleteConfirmation !== tenant.name}
                className="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
              >
                {deletingTenant ? 'Deleting…' : 'Delete Permanently'}
              </button>
            </div>
          </div>
        </div>
      )}
    </CentralLayout>
  );
}
