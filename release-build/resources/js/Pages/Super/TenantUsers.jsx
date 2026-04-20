import { Link, router, useForm } from '@inertiajs/react';
import CentralLayout from '../Layouts/CentralLayout';

export default function TenantUsers({ tenant, users, roles }) {
  const roleEntries = Object.entries(roles ?? {});
  const defaultRole = roleEntries.find(([value]) => value === 'citizen')?.[0] ?? roleEntries[0]?.[0] ?? 'citizen';

  const {
    data: assignData,
    setData: setAssignData,
    post: postAssign,
    processing: assigning,
    errors: assignErrors,
    reset: resetAssign,
  } = useForm({
    email: '',
    role: defaultRole,
  });

  const {
    data: createData,
    setData: setCreateData,
    post: postCreate,
    processing: creating,
    errors: createErrors,
    reset: resetCreate,
  } = useForm({
    name: '',
    email: '',
    phone: '',
    role: defaultRole,
  });

  const handleAdd = (e) => {
    e.preventDefault();
    postAssign(`/super/tenants/${tenant.id}/users`, {
      preserveScroll: true,
      onSuccess: () => resetAssign('email'),
    });
  };

  const handleCreate = (e) => {
    e.preventDefault();
    postCreate(`/super/tenants/${tenant.id}/users/create-account`, {
      preserveScroll: true,
      onSuccess: () => resetCreate('name', 'email', 'phone'),
    });
  };

  const handleRoleUpdate = (userId, role) => {
    router.put(`/super/tenants/${tenant.id}/users/${userId}`, { role }, { preserveScroll: true });
  };

  const handleRemove = (user) => {
    if (!confirm(`Remove ${user.name} from ${tenant.name}?`)) return;
    router.delete(`/super/tenants/${tenant.id}/users/${user.id}`, { preserveScroll: true });
  };

  const inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500';
  const errorClass = 'text-xs text-red-600 mt-1';

  return (
    <CentralLayout>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-800">Manage Users — {tenant.name}</h1>
          <p className="text-sm text-slate-600">Assign users and update tenant roles for this barangay.</p>
        </div>
        <div className="flex gap-2">
          <Link href={`/super/tenants/${tenant.id}/edit`} className="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
            Edit Barangay
          </Link>
          <Link href="/super/tenants" className="rounded-lg bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">
            Back to Barangays
          </Link>
        </div>
      </div>

      <form onSubmit={handleAdd} className="mb-6 rounded-lg bg-white p-6 shadow">
        <h2 className="mb-4 text-lg font-semibold text-slate-700">Add Existing User</h2>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div className="md:col-span-2">
            <label className="mb-1 block text-sm font-medium text-slate-700">User Email *</label>
            <input
              type="email"
              value={assignData.email}
              onChange={(e) => setAssignData('email', e.target.value)}
              className={inputClass}
              placeholder="user@example.com"
              required
            />
            {assignErrors.email && <p className={errorClass}>{assignErrors.email}</p>}
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Role *</label>
            <select value={assignData.role} onChange={(e) => setAssignData('role', e.target.value)} className={inputClass} required>
              {roleEntries.map(([value, label]) => (
                <option key={value} value={value}>{label}</option>
              ))}
            </select>
            {assignErrors.role && <p className={errorClass}>{assignErrors.role}</p>}
          </div>
        </div>
        <div className="mt-4 flex justify-end">
          <button type="submit" disabled={assigning} className="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
            {assigning ? 'Assigning...' : 'Assign User'}
          </button>
        </div>
      </form>

      <form onSubmit={handleCreate} className="mb-6 rounded-lg bg-white p-6 shadow">
        <h2 className="mb-4 text-lg font-semibold text-slate-700">Create & Assign New User</h2>
        <p className="mb-4 text-sm text-slate-600">A secure password is generated automatically and sent to the user email after successful account creation.</p>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Full Name *</label>
            <input type="text" value={createData.name} onChange={(e) => setCreateData('name', e.target.value)} className={inputClass} required />
            {createErrors.name && <p className={errorClass}>{createErrors.name}</p>}
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Email *</label>
            <input type="email" value={createData.email} onChange={(e) => setCreateData('email', e.target.value)} className={inputClass} required />
            {createErrors.email && <p className={errorClass}>{createErrors.email}</p>}
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Phone</label>
            <input type="text" value={createData.phone} onChange={(e) => setCreateData('phone', e.target.value)} className={inputClass} />
            {createErrors.phone && <p className={errorClass}>{createErrors.phone}</p>}
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Role *</label>
            <select value={createData.role} onChange={(e) => setCreateData('role', e.target.value)} className={inputClass} required>
              {roleEntries.map(([value, label]) => (
                <option key={value} value={value}>{label}</option>
              ))}
            </select>
            {createErrors.role && <p className={errorClass}>{createErrors.role}</p>}
          </div>
        </div>
        <div className="mt-4 flex justify-end">
          <button type="submit" disabled={creating} className="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
            {creating ? 'Creating...' : 'Create & Assign User'}
          </button>
        </div>
      </form>

      <div className="overflow-hidden rounded-lg bg-white shadow">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Name</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Email</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Role</th>
              <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200">
            {(users ?? []).map((user) => (
              <tr key={user.id}>
                <td className="px-4 py-2 text-sm font-medium">
                  {user.name}
                  {user.is_super_admin && <span className="ml-2 rounded bg-slate-200 px-2 py-0.5 text-xs text-slate-700">Super Admin</span>}
                </td>
                <td className="px-4 py-2 text-sm text-slate-700">{user.email}</td>
                <td className="px-4 py-2 text-sm">
                  <select
                    value={user.role}
                    onChange={(e) => handleRoleUpdate(user.id, e.target.value)}
                    className="rounded border border-slate-300 px-2 py-1 text-sm"
                    disabled={user.is_super_admin}
                  >
                    {roleEntries.map(([value, label]) => (
                      <option key={value} value={value}>{label}</option>
                    ))}
                  </select>
                </td>
                <td className="px-4 py-2 text-sm">
                  <button
                    type="button"
                    onClick={() => handleRemove(user)}
                    disabled={user.is_super_admin}
                    className="text-red-600 hover:underline disabled:cursor-not-allowed disabled:opacity-40"
                  >
                    Remove
                  </button>
                </td>
              </tr>
            ))}
            {(!users || users.length === 0) && (
              <tr>
                <td colSpan={4} className="px-4 py-6 text-center text-sm text-slate-500">
                  No users assigned to this barangay yet.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </CentralLayout>
  );
}
