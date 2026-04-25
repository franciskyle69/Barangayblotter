import { useForm } from "@inertiajs/react";
import TenantLayout from "../Layouts/TenantLayout";
import SystemUpdaterPanel from "../../Components/SystemUpdaterPanel";
import { usePage } from "@inertiajs/react";

export default function TenantSettings({ tenant, profile }) {
    const { auth } = usePage().props;
    const isSuperAdmin = Boolean(auth?.user?.is_super_admin);
    const {
        data: profileData,
        setData: setProfileData,
        put: putProfile,
        processing: profileProcessing,
        errors: profileErrors,
    } = useForm({
        name: profile?.name ?? "",
        email: profile?.email ?? "",
        phone: profile?.phone ?? "",
    });

    const {
        data: passwordData,
        setData: setPasswordData,
        put: putPassword,
        processing: passwordProcessing,
        errors: passwordErrors,
        reset: resetPassword,
    } = useForm({
        current_password: "",
        password: "",
        password_confirmation: "",
    });

    const submitProfile = (e) => {
        e.preventDefault();
        putProfile("/settings/profile", {
            preserveScroll: true,
        });
    };

    const submitPassword = (e) => {
        e.preventDefault();
        putPassword("/settings/password", {
            preserveScroll: true,
            onSuccess: () =>
                resetPassword(
                    "current_password",
                    "password",
                    "password_confirmation",
                ),
        });
    };

    const inputClass =
        "w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500";
    const errorClass = "text-xs text-red-600 mt-1";

    return (
        <TenantLayout>
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Settings</h1>
                <p className="text-sm text-slate-600">
                    Update your profile and password for {tenant?.name}.
                </p>
            </div>

            <div className="space-y-6">
                {isSuperAdmin && (
                    <SystemUpdaterPanel
                        title="System updater"
                        description="Runs a full application update (affects central + all tenants)."
                    />
                )}
                <form
                    onSubmit={submitProfile}
                    className="rounded-lg bg-white p-6 shadow"
                >
                    <h2 className="mb-4 text-lg font-semibold text-slate-700">
                        Account Profile
                    </h2>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                Full Name *
                            </label>
                            <input
                                type="text"
                                value={profileData.name}
                                onChange={(e) =>
                                    setProfileData("name", e.target.value)
                                }
                                className={inputClass}
                                required
                            />
                            {profileErrors.name && (
                                <p className={errorClass}>
                                    {profileErrors.name}
                                </p>
                            )}
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                Email *
                            </label>
                            <input
                                type="email"
                                value={profileData.email}
                                onChange={(e) =>
                                    setProfileData("email", e.target.value)
                                }
                                className={inputClass}
                                required
                            />
                            {profileErrors.email && (
                                <p className={errorClass}>
                                    {profileErrors.email}
                                </p>
                            )}
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                Phone
                            </label>
                            <input
                                type="text"
                                value={profileData.phone}
                                onChange={(e) =>
                                    setProfileData("phone", e.target.value)
                                }
                                className={inputClass}
                            />
                            {profileErrors.phone && (
                                <p className={errorClass}>
                                    {profileErrors.phone}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-4 flex justify-end">
                        <button
                            type="submit"
                            disabled={profileProcessing}
                            className="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {profileProcessing ? "Saving..." : "Save Profile"}
                        </button>
                    </div>
                </form>

                <form
                    onSubmit={submitPassword}
                    className="rounded-lg bg-white p-6 shadow"
                >
                    <h2 className="mb-4 text-lg font-semibold text-slate-700">
                        Change Password
                    </h2>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                Current Password *
                            </label>
                            <input
                                type="password"
                                value={passwordData.current_password}
                                onChange={(e) =>
                                    setPasswordData(
                                        "current_password",
                                        e.target.value,
                                    )
                                }
                                className={inputClass}
                                required
                            />
                            {passwordErrors.current_password && (
                                <p className={errorClass}>
                                    {passwordErrors.current_password}
                                </p>
                            )}
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                New Password *
                            </label>
                            <input
                                type="password"
                                value={passwordData.password}
                                onChange={(e) =>
                                    setPasswordData("password", e.target.value)
                                }
                                className={inputClass}
                                required
                            />
                            {passwordErrors.password && (
                                <p className={errorClass}>
                                    {passwordErrors.password}
                                </p>
                            )}
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                Confirm New Password *
                            </label>
                            <input
                                type="password"
                                value={passwordData.password_confirmation}
                                onChange={(e) =>
                                    setPasswordData(
                                        "password_confirmation",
                                        e.target.value,
                                    )
                                }
                                className={inputClass}
                                required
                            />
                            {passwordErrors.password_confirmation && (
                                <p className={errorClass}>
                                    {passwordErrors.password_confirmation}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-4 flex justify-end">
                        <button
                            type="submit"
                            disabled={passwordProcessing}
                            className="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                        >
                            {passwordProcessing
                                ? "Updating..."
                                : "Update Password"}
                        </button>
                    </div>
                </form>
            </div>
        </TenantLayout>
    );
}
