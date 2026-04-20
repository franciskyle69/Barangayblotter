import { useEffect, useState } from "react";
import { useForm, Link, router } from "@inertiajs/react";
import CentralLayout from "../Layouts/CentralLayout";
import Swal from "sweetalert2";

const logoOptions = [
    {
        value: "default",
        label: "Default App Logo",
        preview: "/images/logo.png",
    },
    { value: "blue", label: "Blue Seal", preview: "/images/logo-blue.svg" },
    { value: "green", label: "Green Seal", preview: "/images/logo-green.svg" },
    { value: "amber", label: "Amber Seal", preview: "/images/logo-amber.svg" },
    {
        value: "custom",
        label: "Upload Custom Logo",
        preview: "/images/logo.png",
    },
];

export default function TenantForm({
    tenant,
    plans,
    signupRequests = [],
    initialSignupRequestId = "",
}) {
    const isEditing = !!tenant;
    const createDefaultLogo = logoOptions[0];
    const isLocalDomainFlow =
        typeof window !== "undefined" &&
        /(^|\.)localhost$|(^|\.)lvh\.me$|^127\.0\.0\.1$/.test(
            window.location.hostname,
        );
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [deleteConfirmation, setDeleteConfirmation] = useState("");
    const [deletingTenant, setDeletingTenant] = useState(false);

    const pendingSignupRequests = Array.isArray(signupRequests)
        ? signupRequests
        : [];

    const initialFormData = {
        name: tenant?.name ?? "",
        slug: tenant?.slug ?? "",
        subdomain: tenant?.subdomain ?? "",
        custom_domain: tenant?.custom_domain ?? "",
        sidebar_label: tenant?.sidebar_label ?? "",
        barangay: tenant?.barangay ?? "",
        address: tenant?.address ?? "",
        contact_phone: tenant?.contact_phone ?? "",
        requested_admin_name: "",
        requested_admin_email: "",
        requested_admin_phone: "",
        plan_id: tenant?.plan_id ?? plans?.[0]?.id ?? "",
        is_active: tenant?.is_active ?? true,
        signup_request_id: initialSignupRequestId
            ? String(initialSignupRequestId)
            : "",
    };

    if (isEditing) {
        initialFormData.logo_choice = tenant?.logo_choice ?? "default";
        initialFormData.logo_file = null;
    }

    const { data, setData, post, put, processing, errors } =
        useForm(initialFormData);

    useEffect(() => {
        if (!isEditing) {
            return;
        }

        if (data.logo_choice !== "custom" && data.logo_file) {
            setData("logo_file", null);
        }
    }, [data.logo_choice, data.logo_file, isEditing, setData]);

    useEffect(() => {
        if (isEditing || !isLocalDomainFlow) {
            return;
        }

        const normalizedSubdomain = String(data.subdomain ?? "")
            .trim()
            .toLowerCase();
        const normalizedCustomDomain = String(data.custom_domain ?? "")
            .trim()
            .toLowerCase();
        const nextCustomDomain = normalizedSubdomain
            ? `${normalizedSubdomain}.lvh.me`
            : "";

        if (
            normalizedCustomDomain === "" ||
            normalizedCustomDomain.endsWith(".lvh.me")
        ) {
            if (normalizedCustomDomain !== nextCustomDomain) {
                setData("custom_domain", nextCustomDomain);
            }
        }
    }, [
        data.custom_domain,
        data.subdomain,
        isEditing,
        isLocalDomainFlow,
        setData,
    ]);

    const selectedSignupRequest = !isEditing
        ? pendingSignupRequests.find(
              (req) => String(req.id) === String(data.signup_request_id),
          )
        : null;

    const handleSignupRequestSelect = (requestId) => {
        if (!requestId) {
            setData((prev) => ({
                ...prev,
                signup_request_id: "",
            }));
            return;
        }

        const request = pendingSignupRequests.find(
            (req) => String(req.id) === String(requestId),
        );

        if (!request) {
            setData("signup_request_id", "");
            return;
        }

        setData((prev) => ({
            ...prev,
            signup_request_id: String(request.id),
            name: request.tenant_name ?? prev.name,
            slug: request.slug ?? prev.slug,
            subdomain: request.subdomain ?? "",
            custom_domain: request.custom_domain ?? "",
            sidebar_label: request.sidebar_label ?? prev.sidebar_label,
            barangay: request.barangay ?? "",
            address: request.address ?? "",
            contact_phone: request.contact_phone ?? "",
            requested_admin_name:
                request.requested_admin_name ?? prev.requested_admin_name,
            requested_admin_email:
                request.requested_admin_email ?? prev.requested_admin_email,
            requested_admin_phone:
                request.requested_admin_phone ?? prev.requested_admin_phone,
            plan_id: request.requested_plan_id ?? prev.plan_id,
        }));
    };

    useEffect(() => {
        if (!isEditing && data.signup_request_id) {
            const request = pendingSignupRequests.find(
                (req) => String(req.id) === String(data.signup_request_id),
            );

            if (!request) {
                return;
            }

            setData((prev) => ({
                ...prev,
                name: request.tenant_name ?? prev.name,
                slug: request.slug ?? prev.slug,
                subdomain: request.subdomain ?? prev.subdomain,
                custom_domain: request.custom_domain ?? prev.custom_domain,
                sidebar_label: request.sidebar_label ?? prev.sidebar_label,
                barangay: request.barangay ?? prev.barangay,
                address: request.address ?? prev.address,
                contact_phone: request.contact_phone ?? prev.contact_phone,
                requested_admin_name:
                    request.requested_admin_name ?? prev.requested_admin_name,
                requested_admin_email:
                    request.requested_admin_email ?? prev.requested_admin_email,
                requested_admin_phone:
                    request.requested_admin_phone ?? prev.requested_admin_phone,
                plan_id: request.requested_plan_id ?? prev.plan_id,
            }));
        }
        // Initial hydration for URL-selected signup request.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleSubmit = (e) => {
        e.preventDefault();
        const needsFormData =
            isEditing &&
            data.logo_choice === "custom" && Boolean(data.logo_file);

        if (isEditing) {
            put(`/super/tenants/${tenant.id}`, {
                forceFormData: needsFormData,
            });
        } else {
            post("/super/tenants", {
                forceFormData: needsFormData,
            });
        }
    };

    const handleDeleteTenant = () => {
        const normalizedInput = deleteConfirmation.trim();
        const normalizedTenantName = (tenant?.name ?? "").trim();

        if (normalizedInput !== normalizedTenantName) {
            Swal.fire({
                title: "Name mismatch",
                text: "Barangay name does not match. Please try again.",
                icon: "error",
                confirmButtonText: "OK",
            });
            return;
        }

        setDeletingTenant(true);
        router.delete(`/super/tenants/${tenant.id}`, {
            data: {
                confirmation: normalizedInput,
            },
            onSuccess: () => {
                setShowDeleteModal(false);
                setDeleteConfirmation("");
            },
            onError: (errs) => {
                const firstError = Object.values(errs || {})[0];
                if (firstError) {
                    Swal.fire({
                        title: "Delete failed",
                        text: Array.isArray(firstError)
                            ? firstError[0]
                            : firstError,
                        icon: "error",
                        confirmButtonText: "OK",
                    });
                } else {
                    Swal.fire({
                        title: "Delete failed",
                        text: "Unable to delete barangay. Please try again.",
                        icon: "error",
                        confirmButtonText: "OK",
                    });
                }
            },
            onFinish: () => {
                setDeletingTenant(false);
            },
        });
    };

    const inputClass =
        "w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500";
    const labelClass = "block text-sm font-medium text-slate-700 mb-1";
    const errorClass = "text-xs text-red-600 mt-1";

    return (
        <CentralLayout>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold text-slate-800">
                    {isEditing ? `Edit ${tenant.name}` : "Add New Barangay"}
                </h1>
                <div className="flex gap-2">
                    {isEditing && (
                        <Link
                            href={`/super/tenants/${tenant.id}/users`}
                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        >
                            Manage Users
                        </Link>
                    )}
                    <Link
                        href="/super/tenants"
                        className="rounded-lg bg-slate-600 px-4 py-2 text-white hover:bg-slate-700"
                    >
                        ← Back to List
                    </Link>
                </div>
            </div>

            <form
                onSubmit={handleSubmit}
                className="space-y-6 rounded-lg bg-white p-6 shadow"
            >
                {!isEditing && (
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                        <h2 className="mb-2 text-base font-semibold text-emerald-900">
                            Copy From Tenant Signup Request
                        </h2>
                        <p className="mb-3 text-sm text-emerald-800">
                            Select a pending request to auto-fill barangay
                            details and optionally assign the requested tenant
                            admin account.
                        </p>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label className={labelClass}>
                                    Pending Request
                                </label>
                                <select
                                    value={data.signup_request_id}
                                    onChange={(e) =>
                                        handleSignupRequestSelect(
                                            e.target.value,
                                        )
                                    }
                                    className={inputClass}
                                    disabled={
                                        pendingSignupRequests.length === 0
                                    }
                                >
                                    <option value="">
                                        {pendingSignupRequests.length > 0
                                            ? "Select pending request (optional)"
                                            : "No pending requests available"}
                                    </option>
                                    {pendingSignupRequests.map((req) => (
                                        <option key={req.id} value={req.id}>
                                            {req.tenant_name} ({req.slug}) -{" "}
                                            {req.requested_admin_email}
                                        </option>
                                    ))}
                                </select>
                                {errors.signup_request_id && (
                                    <p className={errorClass}>
                                        {errors.signup_request_id}
                                    </p>
                                )}
                            </div>
                        </div>

                        {selectedSignupRequest && (
                            <div className="mt-3 rounded border border-emerald-200 bg-white p-3 text-sm text-slate-700">
                                <p>
                                    <span className="font-semibold">
                                        Requested Admin:
                                    </span>{" "}
                                    {selectedSignupRequest.requested_admin_name}{" "}
                                    (
                                    {
                                        selectedSignupRequest.requested_admin_email
                                    }
                                    )
                                </p>
                                <p>
                                    <span className="font-semibold">
                                        Requested Role:
                                    </span>{" "}
                                    Barangay Admin
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {/* Basic Info */}
                <div className="border-b border-slate-200 pb-4">
                    <h2 className="mb-4 text-lg font-semibold text-slate-700">
                        Basic Information
                    </h2>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label className={labelClass}>
                                Barangay Name *
                            </label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) =>
                                    setData("name", e.target.value)
                                }
                                className={inputClass}
                                required
                            />
                            {errors.name && (
                                <p className={errorClass}>{errors.name}</p>
                            )}
                        </div>
                        <div>
                            <label className={labelClass}>Slug *</label>
                            <input
                                type="text"
                                value={data.slug}
                                onChange={(e) =>
                                    setData("slug", e.target.value)
                                }
                                className={inputClass}
                                required
                                placeholder="e.g. casisang"
                            />
                            {errors.slug && (
                                <p className={errorClass}>{errors.slug}</p>
                            )}
                        </div>
                        <div>
                            <label className={labelClass}>
                                District / Area
                            </label>
                            <input
                                type="text"
                                value={data.barangay}
                                onChange={(e) =>
                                    setData("barangay", e.target.value)
                                }
                                className={inputClass}
                            />
                            {errors.barangay && (
                                <p className={errorClass}>{errors.barangay}</p>
                            )}
                        </div>
                        <div>
                            <label className={labelClass}>Plan *</label>
                            <select
                                value={data.plan_id}
                                onChange={(e) =>
                                    setData("plan_id", e.target.value)
                                }
                                className={inputClass}
                                required
                            >
                                {(plans ?? []).map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name} — ₱{p.price_monthly}/mo
                                    </option>
                                ))}
                            </select>
                            {errors.plan_id && (
                                <p className={errorClass}>{errors.plan_id}</p>
                            )}
                        </div>
                    </div>
                </div>

                {/* Domain Settings */}
                <div className="border-b border-slate-200 pb-4">
                    <h2 className="mb-4 text-lg font-semibold text-slate-700">
                        Domain Settings
                    </h2>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label className={labelClass}>Subdomain</label>
                            <div className="flex items-center">
                                <input
                                    type="text"
                                    value={data.subdomain}
                                    onChange={(e) =>
                                        setData("subdomain", e.target.value)
                                    }
                                    className={`${inputClass} rounded-r-none`}
                                    placeholder="casisang"
                                />
                                <span className="inline-flex items-center rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                                    .
                                    {window?.location?.hostname?.replace(
                                        /^[^.]+\./,
                                        "",
                                    ) || "app.com"}
                                </span>
                            </div>
                            {errors.subdomain && (
                                <p className={errorClass}>{errors.subdomain}</p>
                            )}
                            {!isEditing && isLocalDomainFlow && (
                                <p className="mt-1 text-xs text-slate-500">
                                    Local domain is auto-generated as
                                    {" "}
                                    {data.subdomain
                                        ? `${data.subdomain}.lvh.me`
                                        : "your-subdomain.lvh.me"}
                                    .
                                </p>
                            )}
                        </div>
                        <div>
                            <label className={labelClass}>
                                Custom Domain
                            </label>
                            <input
                                type="text"
                                value={data.custom_domain}
                                onChange={(e) =>
                                    setData("custom_domain", e.target.value)
                                }
                                className={`${inputClass} ${!isEditing && isLocalDomainFlow ? "bg-slate-50 text-slate-500" : ""}`}
                                placeholder={
                                    !isEditing && isLocalDomainFlow
                                        ? "Auto: your-subdomain.lvh.me"
                                        : "barangay-casisang.gov.ph"
                                }
                                readOnly={!isEditing && isLocalDomainFlow}
                            />
                            {errors.custom_domain && (
                                <p className={errorClass}>
                                    {errors.custom_domain}
                                </p>
                            )}
                            {!isEditing && isLocalDomainFlow && (
                                <p className="mt-1 text-xs text-slate-500">
                                    Automatically set from subdomain for local
                                    development.
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                <div className="border-b border-slate-200 pb-4">
                    <h2 className="mb-4 text-lg font-semibold text-slate-700">
                        Branding
                    </h2>
                    <div className="grid gap-6 lg:grid-cols-[1fr_280px] lg:items-start">
                        <div>
                            <label className={labelClass}>Sidebar Label</label>
                            <input
                                type="text"
                                value={data.sidebar_label}
                                onChange={(e) =>
                                    setData("sidebar_label", e.target.value)
                                }
                                className={inputClass}
                                placeholder={
                                    tenant?.slug ?? tenant?.name ?? "Barangay"
                                }
                            />
                            <p className="mt-1 text-xs text-slate-500">
                                Leave blank to use the tenant slug or barangay
                                name.
                            </p>
                            {errors.sidebar_label && (
                                <p className={errorClass}>
                                    {errors.sidebar_label}
                                </p>
                            )}

                            {isEditing ? (
                                <div className="mt-5">
                                    <label className={labelClass}>Logo</label>
                                    <select
                                        value={data.logo_choice}
                                        onChange={(e) =>
                                            setData(
                                                "logo_choice",
                                                e.target.value,
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        {logoOptions.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="mt-1 text-xs text-slate-500">
                                        Choose a preset logo or upload a custom
                                        one.
                                    </p>
                                    {data.logo_choice === "custom" && (
                                        <div className="mt-3">
                                            <input
                                                type="file"
                                                accept="image/*"
                                                onChange={(e) =>
                                                    setData(
                                                        "logo_file",
                                                        e.target.files?.[0] ??
                                                            null,
                                                    )
                                                }
                                                className={inputClass}
                                            />
                                            {errors.logo_file && (
                                                <p className={errorClass}>
                                                    {errors.logo_file}
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <div className="mt-5">
                                    <label className={labelClass}>Logo</label>
                                    <p className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                        New tenants use the default app logo by
                                        default.
                                    </p>
                                </div>
                            )}
                        </div>
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div className="flex items-center gap-3">
                                <img
                                    src={
                                        isEditing
                                            ? logoOptions.find(
                                                  (option) =>
                                                      option.value ===
                                                      data.logo_choice,
                                              )?.preview ?? "/images/logo.png"
                                            : createDefaultLogo.preview
                                    }
                                    alt="Tenant logo preview"
                                    className="h-14 w-14 rounded-lg border border-slate-200 bg-white object-contain p-2"
                                />
                                <div>
                                    <p className="text-sm font-semibold text-slate-800">
                                        Current Logo
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {isEditing
                                            ? logoOptions.find(
                                                  (option) =>
                                                      option.value ===
                                                      data.logo_choice,
                                              )?.label ?? "Selected logo"
                                            : createDefaultLogo.label}
                                    </p>
                                </div>
                            </div>

                            {isEditing && (
                                <div className="mt-4 grid grid-cols-2 gap-2">
                                    {logoOptions.map((option) => (
                                        <button
                                            key={option.value}
                                            type="button"
                                            onClick={() =>
                                                setData(
                                                    "logo_choice",
                                                    option.value,
                                                )
                                            }
                                            className={`rounded-lg border p-2 text-left text-xs transition ${data.logo_choice === option.value ? "border-blue-500 bg-blue-50" : "border-slate-200 bg-white hover:border-slate-300"}`}
                                        >
                                            <img
                                                src={option.preview}
                                                alt={option.label}
                                                className="mb-2 h-10 w-10 rounded-md border border-slate-200 bg-white object-contain p-1"
                                            />
                                            <span className="block font-medium text-slate-700">
                                                {option.label}
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Contact Info */}
                <div className="border-b border-slate-200 pb-4">
                    <h2 className="mb-4 text-lg font-semibold text-slate-700">
                        Contact Information
                    </h2>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label className={labelClass}>Address</label>
                            <input
                                type="text"
                                value={data.address}
                                onChange={(e) =>
                                    setData("address", e.target.value)
                                }
                                className={inputClass}
                            />
                            {errors.address && (
                                <p className={errorClass}>{errors.address}</p>
                            )}
                        </div>
                        <div>
                            <label className={labelClass}>Contact Phone</label>
                            <input
                                type="text"
                                value={data.contact_phone}
                                onChange={(e) =>
                                    setData("contact_phone", e.target.value)
                                }
                                className={inputClass}
                            />
                            {errors.contact_phone && (
                                <p className={errorClass}>
                                    {errors.contact_phone}
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                {!isEditing && (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                            <h2 className="text-sm font-semibold text-slate-800">
                                Requested Tenant Admin Account
                            </h2>
                        </div>

                        <p className="mb-3 text-xs text-slate-600">
                            If the admin email already exists, that user will be
                            linked to this barangay as Barangay Admin. For new
                            users, a secure password will be auto-generated and
                            emailed.
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
                                {errors.requested_admin_phone && (
                                    <p className={errorClass}>
                                        {errors.requested_admin_phone}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className={labelClass}>Admin Role</label>
                                <input
                                    className={inputClass}
                                    value="Barangay Admin"
                                    disabled
                                />
                            </div>
                        </div>
                    </div>
                )}

                {/* Status */}
                <div className="flex items-center gap-3">
                    <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) =>
                                setData("is_active", e.target.checked)
                            }
                            className="h-4 w-4 rounded border-slate-300"
                        />
                        Active
                    </label>
                    <span className="text-xs text-slate-500">
                        Inactive tenants cannot be accessed via subdomain or
                        custom domain.
                    </span>
                </div>

                {/* Delete Danger Zone */}
                {isEditing && (
                    <div className="rounded-lg border-2 border-red-200 bg-red-50 p-6">
                        <div className="mb-4">
                            <h3 className="text-lg font-semibold text-red-900">
                                ⚠️ Danger Zone
                            </h3>
                            <p className="mt-2 text-sm text-red-800">
                                Deleting this barangay will permanently remove
                                all associated data including incidents,
                                mediations, patrol logs, and blotter requests.
                                This action cannot be undone.
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
                    <Link
                        href="/super/tenants"
                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {processing
                            ? "Saving…"
                            : isEditing
                              ? "Update Barangay"
                              : "Create Barangay"}
                    </button>
                </div>
            </form>

            {/* Delete Confirmation Modal */}
            {showDeleteModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white shadow-xl">
                        <div className="border-b border-slate-200 px-6 py-4">
                            <h2 className="text-xl font-bold text-red-900">
                                Delete Barangay?
                            </h2>
                        </div>

                        <div className="space-y-4 px-6 py-4">
                            <div className="rounded-lg bg-red-50 p-4">
                                <p className="text-sm font-semibold text-red-900">
                                    ⚠️ This action is permanent!
                                </p>
                                <p className="mt-2 text-sm text-red-800">
                                    You are about to delete{" "}
                                    <span className="font-bold">
                                        "{tenant.name}"
                                    </span>{" "}
                                    and all its associated data:
                                </p>
                                <ul className="mt-3 space-y-1 text-sm text-red-700">
                                    <li>
                                        • All incidents and their attachments
                                    </li>
                                    <li>• All mediations</li>
                                    <li>• All patrol logs</li>
                                    <li>• All blotter requests</li>
                                    <li>• User associations</li>
                                </ul>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-2">
                                    To confirm, type the barangay name:{" "}
                                    <span className="font-bold text-red-600">
                                        "{tenant.name}"
                                    </span>
                                </label>
                                <input
                                    type="text"
                                    value={deleteConfirmation}
                                    onChange={(e) =>
                                        setDeleteConfirmation(e.target.value)
                                    }
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
                                    setDeleteConfirmation("");
                                }}
                                disabled={deletingTenant}
                                className="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleDeleteTenant}
                                disabled={
                                    deletingTenant ||
                                    deleteConfirmation.trim() !==
                                        (tenant?.name ?? "").trim()
                                }
                                className="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                            >
                                {deletingTenant
                                    ? "Deleting…"
                                    : "Delete Permanently"}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </CentralLayout>
    );
}
