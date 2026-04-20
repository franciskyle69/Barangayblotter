import { useState } from "react";
import { router, useForm } from "@inertiajs/react";
import CentralLayout from "../Layouts/CentralLayout";

function RoleCard({ role, permissions, permissionLabels }) {
    const [isOpen, setIsOpen] = useState(false);
    const { data, setData, put, processing } = useForm({
        permissions: role.permissions ?? [],
    });

    const saveRole = (event) => {
        event.preventDefault();

        put(`/super/roles-permissions/${role.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ["roles"] });
            },
        });
    };

    return (
        <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
            <button
                type="button"
                onClick={() => setIsOpen((current) => !current)}
                className="flex w-full items-start justify-between gap-4 px-5 py-5 text-left"
            >
                <div>
                    <h3 className="text-lg font-semibold text-slate-900">
                        {role.label}
                    </h3>
                    <p className="text-sm text-slate-500">{role.name}</p>
                    <p className="mt-1 text-xs text-slate-500">
                        Click to {isOpen ? "hide" : "edit"} permissions.
                    </p>
                </div>
                <span className="rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600">
                    {isOpen ? "Hide" : "Edit"}
                </span>
            </button>

            {isOpen && (
                <form
                    onSubmit={saveRole}
                    className="border-t border-slate-200 p-5"
                >
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {permissions.map((permission) => {
                            const checked =
                                data.permissions.includes(permission);

                            return (
                                <label
                                    key={permission}
                                    className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition ${checked ? "border-blue-500 bg-blue-50" : "border-slate-200 hover:border-slate-300 hover:bg-slate-50"}`}
                                >
                                    <input
                                        type="checkbox"
                                        className="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600"
                                        checked={checked}
                                        onChange={(e) => {
                                            const next = new Set(
                                                data.permissions,
                                            );
                                            if (e.target.checked) {
                                                next.add(permission);
                                            } else {
                                                next.delete(permission);
                                            }

                                            setData(
                                                "permissions",
                                                Array.from(next),
                                            );
                                        }}
                                    />
                                    <div>
                                        <div className="font-medium text-slate-900">
                                            {permissionLabels[permission] ??
                                                permission}
                                        </div>
                                        <div className="text-xs text-slate-500">
                                            {permission}
                                        </div>
                                    </div>
                                </label>
                            );
                        })}
                    </div>

                    <div className="mt-5 flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {processing ? "Saving..." : "Save Role"}
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}

export default function RolesPermissions({
    roles,
    permissions,
    permissionLabels,
}) {
    return (
        <CentralLayout>
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-800">
                            Roles & Permissions
                        </h1>
                        <p className="mt-1 text-sm text-slate-600">
                            Update which permissions belong to each tenant role.
                        </p>
                    </div>
                    <div className="rounded-lg border border-blue-100 bg-blue-50 px-4 py-2 text-sm text-blue-900">
                        Changes update the central RBAC tables used by tenant
                        access checks.
                    </div>
                </div>

                <div className="grid gap-6">
                    {(roles || []).map((role) => (
                        <RoleCard
                            key={role.id}
                            role={role}
                            permissions={permissions || []}
                            permissionLabels={permissionLabels || {}}
                        />
                    ))}
                </div>
            </div>
        </CentralLayout>
    );
}
