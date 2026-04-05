import { useForm } from "@inertiajs/react";

export default function TenantSignup({ plans = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        tenant_name: "",
        slug: "",
        subdomain: "",
        custom_domain: "",
        barangay: "",
        address: "",
        contact_phone: "",
        requested_admin_name: "",
        requested_admin_email: "",
        requested_admin_phone: "",
        requested_admin_password: "",
        requested_admin_password_confirmation: "",
        requested_plan_id: plans[0]?.id ?? "",
    });

    const inputClass = "w-full rounded-lg border border-slate-300 px-3 py-2";
    const labelClass = "mb-1 block text-sm font-medium text-slate-700";
    const errorClass = "mt-1 text-xs text-red-600";

    return (
        <div className="min-h-screen bg-[#f8fafc] px-4 py-10">
            <div className="mx-auto max-w-3xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h1 className="text-2xl font-bold text-slate-800">
                    Request Barangay Tenant Signup
                </h1>
                <p className="mt-2 text-sm text-slate-600">
                    Submit this form to request a new barangay tenant account.
                    Requests are reviewed by city super admins before
                    activation.
                </p>

                {errors && Object.keys(errors).length > 0 && (
                    <div className="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        <ul className="list-disc pl-4">
                            {Object.values(errors)
                                .flat()
                                .map((msg, i) => (
                                    <li key={i}>{msg}</li>
                                ))}
                        </ul>
                    </div>
                )}

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post("/tenant-signup");
                    }}
                    className="mt-6 space-y-5"
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className={labelClass}>
                                Barangay/Tenant Name *
                            </label>
                            <input
                                className={inputClass}
                                value={data.tenant_name}
                                onChange={(e) =>
                                    setData("tenant_name", e.target.value)
                                }
                                required
                            />
                            {errors.tenant_name && (
                                <p className={errorClass}>
                                    {errors.tenant_name}
                                </p>
                            )}
                        </div>
                        <div>
                            <label className={labelClass}>Slug *</label>
                            <input
                                className={inputClass}
                                value={data.slug}
                                onChange={(e) =>
                                    setData("slug", e.target.value)
                                }
                                required
                            />
                            {errors.slug && (
                                <p className={errorClass}>{errors.slug}</p>
                            )}
                        </div>
                        <div>
                            <label className={labelClass}>Subdomain</label>
                            <input
                                className={inputClass}
                                value={data.subdomain}
                                onChange={(e) =>
                                    setData("subdomain", e.target.value)
                                }
                                placeholder="example: casisang"
                            />
                            {errors.subdomain && (
                                <p className={errorClass}>{errors.subdomain}</p>
                            )}
                        </div>
                        <div>
                            <label className={labelClass}>Custom Domain</label>
                            <input
                                className={inputClass}
                                value={data.custom_domain}
                                onChange={(e) =>
                                    setData("custom_domain", e.target.value)
                                }
                                placeholder="example.gov.ph"
                            />
                            {errors.custom_domain && (
                                <p className={errorClass}>
                                    {errors.custom_domain}
                                </p>
                            )}
                        </div>
                        <div>
                            <label className={labelClass}>
                                District / Area
                            </label>
                            <input
                                className={inputClass}
                                value={data.barangay}
                                onChange={(e) =>
                                    setData("barangay", e.target.value)
                                }
                            />
                        </div>
                        <div>
                            <label className={labelClass}>Contact Phone</label>
                            <input
                                className={inputClass}
                                value={data.contact_phone}
                                onChange={(e) =>
                                    setData("contact_phone", e.target.value)
                                }
                            />
                        </div>
                    </div>

                    <div>
                        <label className={labelClass}>Address</label>
                        <input
                            className={inputClass}
                            value={data.address}
                            onChange={(e) => setData("address", e.target.value)}
                        />
                    </div>

                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <h2 className="mb-3 text-sm font-semibold text-slate-800">
                            Requested Tenant Admin Account
                        </h2>
                        <p className="mb-3 text-xs text-slate-600">
                            The first user for the tenant is automatically
                            assigned as Barangay Admin.
                        </p>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className={labelClass}>
                                    Admin Full Name *
                                </label>
                                <input
                                    className={inputClass}
                                    value={data.requested_admin_name}
                                    onChange={(e) =>
                                        setData(
                                            "requested_admin_name",
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                {errors.requested_admin_name && (
                                    <p className={errorClass}>
                                        {errors.requested_admin_name}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className={labelClass}>
                                    Admin Email *
                                </label>
                                <input
                                    type="email"
                                    className={inputClass}
                                    value={data.requested_admin_email}
                                    onChange={(e) =>
                                        setData(
                                            "requested_admin_email",
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                {errors.requested_admin_email && (
                                    <p className={errorClass}>
                                        {errors.requested_admin_email}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className={labelClass}>
                                    Admin Phone
                                </label>
                                <input
                                    className={inputClass}
                                    value={data.requested_admin_phone}
                                    onChange={(e) =>
                                        setData(
                                            "requested_admin_phone",
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <label className={labelClass}>Role</label>
                                <input
                                    className={inputClass}
                                    value="Barangay Admin"
                                    disabled
                                />
                            </div>
                            <div>
                                <label className={labelClass}>
                                    Admin Password *
                                </label>
                                <input
                                    type="password"
                                    className={inputClass}
                                    value={data.requested_admin_password}
                                    onChange={(e) =>
                                        setData(
                                            "requested_admin_password",
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                {errors.requested_admin_password && (
                                    <p className={errorClass}>
                                        {errors.requested_admin_password}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className={labelClass}>
                                    Confirm Admin Password *
                                </label>
                                <input
                                    type="password"
                                    className={inputClass}
                                    value={
                                        data.requested_admin_password_confirmation
                                    }
                                    onChange={(e) =>
                                        setData(
                                            "requested_admin_password_confirmation",
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label className={labelClass}>Requested Plan</label>
                        <select
                            className={inputClass}
                            value={data.requested_plan_id}
                            onChange={(e) =>
                                setData("requested_plan_id", e.target.value)
                            }
                        >
                            <option value="">No preference</option>
                            {plans.map((plan) => (
                                <option key={plan.id} value={plan.id}>
                                    {plan.name} - P{plan.price_monthly}/mo
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex items-center gap-2 pt-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-70"
                        >
                            {processing ? "Submitting..." : "Submit Request"}
                        </button>
                        <a
                            href="/login"
                            className="rounded-lg border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-50"
                        >
                            Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    );
}
