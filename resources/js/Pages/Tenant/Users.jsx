import { useEffect, useMemo, useState } from "react";
import { Link, router, useForm } from "@inertiajs/react";
import Swal from "sweetalert2";
import TenantLayout from "../Layouts/TenantLayout";

function Modal({ title, subtitle, onClose, children, disableClose = false }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center px-4 py-6">
            <button
                type="button"
                className="absolute inset-0 bg-slate-950/50"
                aria-label="Close modal"
                onClick={disableClose ? undefined : onClose}
                disabled={disableClose}
            />
            <div className="relative z-10 w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                <div className="flex items-start justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <h2 className="text-lg font-semibold text-slate-900">
                            {title}
                        </h2>
                        {subtitle && (
                            <p className="mt-1 text-sm text-slate-500">
                                {subtitle}
                            </p>
                        )}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={disableClose}
                        className="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50"
                    >
                        Close
                    </button>
                </div>
                <div className="px-6 py-5">{children}</div>
            </div>
        </div>
    );
}

export default function TenantUsers({
    tenant,
    users,
    availableUsers = [],
    roles,
}) {
    const roleEntries = Object.entries(roles ?? {});
    const roleLabelByValue = Object.fromEntries(roleEntries);
    const defaultRole =
        roleEntries.find(([value]) => value === "citizen")?.[0] ??
        roleEntries[0]?.[0] ??
        "citizen";

    const [activeModal, setActiveModal] = useState(null);
    const [roleEditUser, setRoleEditUser] = useState(null);
    const [searchTerm, setSearchTerm] = useState("");

    const {
        data: assignData,
        setData: setAssignData,
        post: postAssign,
        processing: assigning,
        errors: assignErrors,
        reset: resetAssign,
    } = useForm({
        user_id: "",
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
        name: "",
        email: "",
        phone: "",
        role: defaultRole,
    });

    const {
        data: roleData,
        setData: setRoleData,
        put: putRole,
        processing: updatingRole,
    } = useForm({
        role: defaultRole,
    });

    const filteredAvailableUsers = useMemo(() => {
        const query = searchTerm.trim().toLowerCase();
        if (!query) {
            return availableUsers ?? [];
        }

        return (availableUsers ?? []).filter((user) => {
            return [user.name, user.email, user.phone]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(query));
        });
    }, [availableUsers, searchTerm]);

    useEffect(() => {
        if (activeModal === "existing") {
            setSearchTerm("");
            resetAssign("user_id");
        }
    }, [activeModal, resetAssign]);

    const closeModal = () => {
        setActiveModal(null);
        setSearchTerm("");
    };

    const closeRoleModal = () => {
        if (updatingRole) {
            return;
        }

        setRoleEditUser(null);
    };

    const handleAdd = (e) => {
        e.preventDefault();
        postAssign("/users", {
            preserveScroll: true,
            onSuccess: () => {
                resetAssign("user_id");
                closeModal();
            },
        });
    };

    const handleCreate = (e) => {
        e.preventDefault();
        postCreate("/users/create-account", {
            preserveScroll: true,
            onSuccess: () => {
                resetCreate("name", "email", "phone");
                closeModal();
            },
        });
    };

    const openRoleEditor = (user) => {
        if (updatingRole) {
            return;
        }

        setRoleEditUser(user);
        setRoleData("role", user.role ?? defaultRole);
    };

    const submitRoleUpdate = async (e) => {
        e.preventDefault();

        if (!roleEditUser || !roleData.role) {
            return;
        }

        const roleLabel = roleLabelByValue[roleData.role] ?? roleData.role;

        const confirmation = await Swal.fire({
            title: "Confirm role change",
            text: `Update ${roleEditUser.name} to ${roleLabel} in ${tenant.name}? This only affects this tenant.`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes, update role",
            cancelButtonText: "Cancel",
        });

        if (!confirmation.isConfirmed) {
            return;
        }

        putRole(`/users/${roleEditUser.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: "Role updated",
                    text: `${roleEditUser.name}'s role was updated successfully for ${tenant.name}.`,
                    icon: "success",
                });
                closeRoleModal();
            },
            onError: (errors) => {
                const firstError =
                    errors?.role ||
                    errors?.user ||
                    errors?.message ||
                    "Unable to update role. Please verify tenant assignment and try again.";

                Swal.fire({
                    title: "Role update failed",
                    text: firstError,
                    icon: "error",
                });
            },
        });
    };

    const handleRemove = (user) => {
        if (!confirm(`Remove ${user.name} from ${tenant.name}?`)) return;
        router.delete(`/users/${user.id}`, { preserveScroll: true });
    };

    const inputClass =
        "w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500";
    const errorClass = "text-xs text-red-600 mt-1";

    return (
        <TenantLayout>
            <div className="mb-6 flex items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-slate-800">
                        Manage Users
                    </h1>
                    <p className="text-sm text-slate-600">
                        Tenant-scoped users and roles for {tenant.name}.
                    </p>
                </div>
                <div className="flex gap-3">
                    <button
                        type="button"
                        onClick={() => setActiveModal("existing")}
                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Add Existing User
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveModal("create")}
                        className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        Create & Assign New User
                    </button>
                    <Link
                        href="/dashboard"
                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                    >
                        Back to Dashboard
                    </Link>
                </div>
            </div>

            <div className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                Barangay Admin can manage tenant users, change roles, and remove
                accounts. The tenant must always keep at least one Barangay
                Admin.
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-slate-200">
                    <thead className="bg-slate-50">
                        <tr>
                            <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">
                                Name
                            </th>
                            <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">
                                Email
                            </th>
                            <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">
                                Role
                            </th>
                            <th className="px-4 py-2 text-left text-xs font-medium text-slate-500">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200">
                        {(users ?? []).map((user) => (
                            <tr key={user.id}>
                                <td className="px-4 py-2 text-sm font-medium">
                                    {user.name}
                                    {user.is_super_admin && (
                                        <span className="ml-2 rounded bg-slate-200 px-2 py-0.5 text-xs text-slate-700">
                                            Super Admin
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-2 text-sm text-slate-700">
                                    {user.email}
                                </td>
                                <td className="px-4 py-2 text-sm">
                                    <button
                                        type="button"
                                        onClick={() => openRoleEditor(user)}
                                        disabled={user.is_super_admin}
                                        className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        {roleLabelByValue[user.role] ??
                                            user.role ??
                                            "Assign Role"}
                                    </button>
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
                                <td
                                    colSpan={4}
                                    className="px-4 py-6 text-center text-sm text-slate-500"
                                >
                                    No users assigned to this barangay yet.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {activeModal === "existing" && (
                <Modal
                    title="Add Existing User"
                    subtitle="Search for an existing account and assign it to this tenant. Already assigned users are excluded."
                    onClose={closeModal}
                >
                    <form onSubmit={handleAdd} className="space-y-5">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                Search users
                            </label>
                            <input
                                type="search"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className={inputClass}
                                placeholder="Search by name, email, or phone"
                            />
                        </div>

                        <div className="max-h-72 overflow-y-auto rounded-xl border border-slate-200">
                            {filteredAvailableUsers.length > 0 ? (
                                filteredAvailableUsers.map((user) => (
                                    <label
                                        key={user.id}
                                        className={`flex cursor-pointer items-center justify-between gap-4 border-b border-slate-100 px-4 py-3 last:border-b-0 ${assignData.user_id === String(user.id) ? "bg-blue-50" : "hover:bg-slate-50"}`}
                                    >
                                        <div>
                                            <p className="font-medium text-slate-900">
                                                {user.name}
                                            </p>
                                            <p className="text-sm text-slate-500">
                                                {user.email}
                                                {user.phone
                                                    ? ` · ${user.phone}`
                                                    : ""}
                                            </p>
                                        </div>
                                        <input
                                            type="radio"
                                            name="user_id"
                                            value={user.id}
                                            checked={
                                                assignData.user_id ===
                                                String(user.id)
                                            }
                                            onChange={(e) =>
                                                setAssignData(
                                                    "user_id",
                                                    e.target.value,
                                                )
                                            }
                                            className="h-4 w-4 text-blue-600"
                                        />
                                    </label>
                                ))
                            ) : (
                                <p className="px-4 py-6 text-sm text-slate-500">
                                    No matching unassigned users found.
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                Role *
                            </label>
                            <select
                                value={assignData.role}
                                onChange={(e) =>
                                    setAssignData("role", e.target.value)
                                }
                                className={inputClass}
                                required
                            >
                                {roleEntries.map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                            {assignErrors.role && (
                                <p className={errorClass}>
                                    {assignErrors.role}
                                </p>
                            )}
                            {assignErrors.user_id && (
                                <p className={errorClass}>
                                    {assignErrors.user_id}
                                </p>
                            )}
                        </div>

                        <div className="flex justify-end gap-3 border-t border-slate-200 pt-4">
                            <button
                                type="button"
                                onClick={closeModal}
                                className="rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={assigning || !assignData.user_id}
                                className="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {assigning ? "Assigning..." : "Assign User"}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {activeModal === "create" && (
                <Modal
                    title="Create & Assign New User"
                    subtitle="A secure password will be generated automatically and emailed to the new user."
                    onClose={closeModal}
                >
                    <form onSubmit={handleCreate} className="space-y-5">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700">
                                    Full Name *
                                </label>
                                <input
                                    type="text"
                                    value={createData.name}
                                    onChange={(e) =>
                                        setCreateData("name", e.target.value)
                                    }
                                    className={inputClass}
                                    required
                                />
                                {createErrors.name && (
                                    <p className={errorClass}>
                                        {createErrors.name}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700">
                                    Email *
                                </label>
                                <input
                                    type="email"
                                    value={createData.email}
                                    onChange={(e) =>
                                        setCreateData("email", e.target.value)
                                    }
                                    className={inputClass}
                                    required
                                />
                                {createErrors.email && (
                                    <p className={errorClass}>
                                        {createErrors.email}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700">
                                    Phone
                                </label>
                                <input
                                    type="text"
                                    value={createData.phone}
                                    onChange={(e) =>
                                        setCreateData("phone", e.target.value)
                                    }
                                    className={inputClass}
                                />
                                {createErrors.phone && (
                                    <p className={errorClass}>
                                        {createErrors.phone}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700">
                                    Role *
                                </label>
                                <select
                                    value={createData.role}
                                    onChange={(e) =>
                                        setCreateData("role", e.target.value)
                                    }
                                    className={inputClass}
                                    required
                                >
                                    {roleEntries.map(([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                                {createErrors.role && (
                                    <p className={errorClass}>
                                        {createErrors.role}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                            The user will be created, assigned immediately, and
                            emailed their login details automatically.
                        </div>

                        <div className="flex justify-end gap-3 border-t border-slate-200 pt-4">
                            <button
                                type="button"
                                onClick={closeModal}
                                className="rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={creating}
                                className="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                {creating
                                    ? "Creating..."
                                    : "Create & Assign User"}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {roleEditUser && (
                <Modal
                    title="Change User Role"
                    subtitle={`Select a new role for ${roleEditUser.name}. This will update their permissions after confirmation.`}
                    onClose={closeRoleModal}
                    disableClose={updatingRole}
                >
                    <form onSubmit={submitRoleUpdate} className="space-y-5">
                        <div className="grid gap-3 sm:grid-cols-2">
                            {roleEntries.map(([value, label]) => {
                                const selected = roleData.role === value;

                                return (
                                    <label
                                        key={value}
                                        className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition ${selected ? "border-blue-500 bg-blue-50" : "border-slate-200 hover:border-slate-300 hover:bg-slate-50"}`}
                                    >
                                        <input
                                            type="radio"
                                            name="role"
                                            value={value}
                                            checked={selected}
                                            disabled={updatingRole}
                                            onChange={(e) =>
                                                setRoleData(
                                                    "role",
                                                    e.target.value,
                                                )
                                            }
                                            className="mt-1 h-4 w-4 border-slate-300 text-blue-600"
                                        />
                                        <div>
                                            <p className="font-medium text-slate-900">
                                                {label}
                                            </p>
                                            <p className="mt-1 text-xs text-slate-500">
                                                {value === "barangay_admin"
                                                    ? "Full access to tenant management tools."
                                                    : "Limited access based on the selected role."}
                                            </p>
                                        </div>
                                    </label>
                                );
                            })}
                        </div>

                        <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            Changing a role changes the user's permissions
                            across the tenant. Please confirm before saving.
                        </div>

                        <div className="flex justify-end gap-3 border-t border-slate-200 pt-4">
                            <button
                                type="button"
                                onClick={closeRoleModal}
                                disabled={updatingRole}
                                className="rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={!roleData.role || updatingRole}
                                className="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                {updatingRole ? "Saving..." : "Save Role"}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </TenantLayout>
    );
}
