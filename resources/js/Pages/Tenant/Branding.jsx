import { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import TenantLayout from "../Layouts/TenantLayout";

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

const themeOptions = [
    { value: "default", label: "Default Indigo" },
    { value: "ocean", label: "Ocean Blue" },
    { value: "forest", label: "Forest Green" },
    { value: "amber", label: "Warm Amber" },
    { value: "dusk", label: "Dusk Slate" },
    { value: "custom", label: "Custom Colors" },
];

const themeDefaults = {
    default: { primary: "#635bff", background: "#f8fafc" },
    ocean: { primary: "#0ea5e9", background: "#eff6ff" },
    forest: { primary: "#16a34a", background: "#f0fdf4" },
    amber: { primary: "#f59e0b", background: "#fffbeb" },
    dusk: { primary: "#8b5cf6", background: "#f8fafc" },
};

const deriveSidebarColor = (primaryColor) => {
    const normalized = String(primaryColor || "").replace("#", "");

    if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
        return "#121621";
    }

    const red = parseInt(normalized.slice(0, 2), 16);
    const green = parseInt(normalized.slice(2, 4), 16);
    const blue = parseInt(normalized.slice(4, 6), 16);

    const shade = (value) => Math.max(0, Math.round(value * 0.36));

    return `#${[shade(red), shade(green), shade(blue)]
        .map((value) => value.toString(16).padStart(2, "0"))
        .join("")}`;
};

export default function TenantBranding({
    tenant,
    logoOptions: serverLogoOptions,
}) {
    const csrfToken =
        typeof document !== "undefined"
            ? document.head
                  ?.querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content")
            : null;
    const options =
        Array.isArray(serverLogoOptions) && serverLogoOptions.length > 0
            ? serverLogoOptions
            : logoOptions;
    const {
        data,
        setData,
        post,
        processing,
        errors,
        clearErrors,
        transform,
    } = useForm({
        sidebar_label: tenant?.sidebar_label ?? tenant?.name ?? "",
        logo_choice: tenant?.logo_choice ?? "default",
        logo_file: null,
        theme_preset: tenant?.theme_preset ?? "default",
        theme_primary_color: tenant?.theme_primary_color ?? "#635bff",
        theme_bg_color: tenant?.theme_bg_color ?? "#f8fafc",
        theme_sidebar_color:
            tenant?.theme_sidebar_color ??
            deriveSidebarColor(tenant?.theme_primary_color ?? "#635bff"),
        login_background_file: null,
        remove_login_background: false,
        login_background_opacity:
            tenant?.login_background_opacity != null
                ? Number(tenant.login_background_opacity)
                : 0.45,
        login_background_blur:
            tenant?.login_background_blur != null
                ? Number(tenant.login_background_blur)
                : 0,
    });

    useEffect(() => {
        if (data.logo_choice !== "custom") {
            setData("logo_file", null);
        }
        if (data.theme_preset !== "custom") {
            const defaults =
                themeDefaults[data.theme_preset] ?? themeDefaults.default;
            setData("theme_primary_color", defaults.primary);
            setData("theme_bg_color", defaults.background);
        }
        setData(
            "theme_sidebar_color",
            deriveSidebarColor(data.theme_primary_color),
        );
        clearErrors();
    }, [data.logo_choice, data.theme_preset, clearErrors, setData]);

    useEffect(() => {
        setData(
            "theme_sidebar_color",
            deriveSidebarColor(data.theme_primary_color),
        );
    }, [data.theme_primary_color, setData]);

    const submit = (e) => {
        e.preventDefault();
        post("/branding", {
            forceFormData: true,
            preserveScroll: true,
            headers: csrfToken ? { "X-CSRF-TOKEN": csrfToken } : undefined,
            onBefore: () => {
                // If CSRF cookie/header sync breaks on some tenant domains
                // (seen as 419 Page Expired), fall back to sending the token
                // explicitly with the request.
                transform((payload) => ({
                    ...payload,
                    ...(csrfToken ? { _token: csrfToken } : {}),
                }));
            },
            onFinish: () => transform((payload) => payload),
        });
    };

    return (
        <TenantLayout>
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Branding</h1>
                <p className="mt-1 text-sm text-slate-600">
                    Update the logo used in the browser tab and sidebar, plus
                    the label shown in the tenant navigation. You can also
                    customize the tenant login background, opacity, and blur.
                </p>
            </div>

            <form
                onSubmit={submit}
                className="w-full max-w-none rounded-lg bg-white p-6 shadow"
            >
                <div className="grid gap-6 xl:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 p-4">
                        <div className="space-y-5">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700">
                                    Sidebar Label
                                </label>
                                <input
                                    type="text"
                                    value={data.sidebar_label}
                                    onChange={(e) =>
                                        setData("sidebar_label", e.target.value)
                                    }
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    placeholder={tenant?.slug || tenant?.name}
                                />
                                <p className="mt-1 text-xs text-slate-500">
                                    Leave blank to use the tenant slug or
                                    barangay name.
                                </p>
                                {errors.sidebar_label && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {errors.sidebar_label}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700">
                                    Logo
                                </label>
                                <div className="grid gap-4 md:grid-cols-[160px_1fr] md:items-start">
                                    <img
                                        src={
                                            tenant?.logo_url ||
                                            "/images/logo.png"
                                        }
                                        alt="Tenant logo preview"
                                        className="h-16 w-16 rounded-lg border border-slate-200 object-contain bg-white p-2"
                                    />
                                    <div className="space-y-3">
                                        <select
                                            value={data.logo_choice}
                                            onChange={(e) =>
                                                setData(
                                                    "logo_choice",
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                        >
                                            {options.map((option) => (
                                                <option
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                            {options.map((option) => (
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
                                                        className="mb-2 h-10 w-10 rounded-md border border-slate-200 object-contain bg-white p-1"
                                                    />
                                                    <span className="block font-medium text-slate-700">
                                                        {option.label}
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                        {data.logo_choice === "custom" && (
                                            <div>
                                                <input
                                                    type="file"
                                                    accept="image/*"
                                                    onChange={(e) =>
                                                        setData(
                                                            "logo_file",
                                                            e.target
                                                                .files?.[0] ??
                                                                null,
                                                        )
                                                    }
                                                    className="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-blue-700"
                                                />
                                                <p className="mt-1 text-xs text-slate-500">
                                                    PNG, JPG, or SVG. Max 2 MB.
                                                </p>
                                                {errors.logo_file && (
                                                    <p className="mt-1 text-xs text-red-600">
                                                        {errors.logo_file}
                                                    </p>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 p-4">
                        <h2 className="text-sm font-semibold text-slate-800">
                            Login Background
                        </h2>
                        <p className="mt-1 text-xs text-slate-500">
                            Upload a background image for this tenant login page
                            and control the overlay opacity and blur.
                        </p>

                        <div className="mt-4 grid gap-4 md:grid-cols-[220px_1fr]">
                            <div className="overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                {tenant?.login_background_url ? (
                                    <img
                                        src={tenant.login_background_url}
                                        alt="Login background preview"
                                        className="h-36 w-full object-cover"
                                    />
                                ) : (
                                    <div className="flex h-36 items-center justify-center px-3 text-center text-xs text-slate-500">
                                        No custom login background uploaded.
                                    </div>
                                )}
                            </div>

                            <div className="space-y-4">
                                <div>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        onChange={(e) =>
                                            setData(
                                                "login_background_file",
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                        className="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-blue-700"
                                    />
                                    <p className="mt-1 text-xs text-slate-500">
                                        PNG, JPG, or WEBP. Max 4 MB.
                                    </p>
                                    {errors.login_background_file && (
                                        <p className="mt-1 text-xs text-red-600">
                                            {errors.login_background_file}
                                        </p>
                                    )}
                                </div>

                                <label className="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={Boolean(
                                            data.remove_login_background,
                                        )}
                                        onChange={(e) =>
                                            setData(
                                                "remove_login_background",
                                                e.target.checked,
                                            )
                                        }
                                        className="rounded border-slate-300"
                                    />
                                    Remove current login background
                                </label>

                                <div>
                                    <label className="mb-1 flex items-center justify-between text-sm font-medium text-slate-700">
                                        <span>Overlay Opacity</span>
                                        <span className="text-xs text-slate-500">
                                            {Number(
                                                data.login_background_opacity,
                                            ).toFixed(2)}
                                        </span>
                                    </label>
                                    <input
                                        type="range"
                                        min="0"
                                        max="0.9"
                                        step="0.05"
                                        value={data.login_background_opacity}
                                        onChange={(e) =>
                                            setData(
                                                "login_background_opacity",
                                                Number(e.target.value),
                                            )
                                        }
                                        className="w-full"
                                    />
                                    {errors.login_background_opacity && (
                                        <p className="mt-1 text-xs text-red-600">
                                            {errors.login_background_opacity}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label className="mb-1 flex items-center justify-between text-sm font-medium text-slate-700">
                                        <span>Background Blur</span>
                                        <span className="text-xs text-slate-500">
                                            {Number(data.login_background_blur)}
                                            px
                                        </span>
                                    </label>
                                    <input
                                        type="range"
                                        min="0"
                                        max="20"
                                        step="1"
                                        value={data.login_background_blur}
                                        onChange={(e) =>
                                            setData(
                                                "login_background_blur",
                                                Number(e.target.value),
                                            )
                                        }
                                        className="w-full"
                                    />
                                    {errors.login_background_blur && (
                                        <p className="mt-1 text-xs text-red-600">
                                            {errors.login_background_blur}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 p-4 xl:col-span-2">
                        <h2 className="text-sm font-semibold text-slate-800">
                            Theme Colors
                        </h2>
                        <p className="mt-1 text-xs text-slate-500">
                            Choose a preset theme or set custom colors for the
                            tenant app shell and login accents.
                        </p>

                        <div className="mt-4 grid gap-4 md:grid-cols-[220px_1fr]">
                            <div className="space-y-2">
                                {themeOptions.map((option) => {
                                    const active =
                                        data.theme_preset === option.value;

                                    return (
                                        <button
                                            key={option.value}
                                            type="button"
                                            onClick={() =>
                                                setData(
                                                    "theme_preset",
                                                    option.value,
                                                )
                                            }
                                            className={`w-full rounded-lg border px-3 py-2 text-left text-sm transition ${active ? "border-blue-500 bg-blue-50 text-blue-900" : "border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50"}`}
                                        >
                                            {option.label}
                                        </button>
                                    );
                                })}
                            </div>

                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-slate-700">
                                            Primary Color
                                        </label>
                                        <input
                                            type="color"
                                            value={data.theme_primary_color}
                                            onChange={(e) =>
                                                setData(
                                                    "theme_primary_color",
                                                    e.target.value,
                                                )
                                            }
                                            disabled={
                                                data.theme_preset !== "custom"
                                            }
                                            className="h-10 w-full rounded-lg border border-slate-300 bg-white p-1 disabled:opacity-50"
                                        />
                                        <p className="mt-1 text-xs text-slate-500">
                                            Used for buttons and highlights.
                                        </p>
                                        {errors.theme_primary_color && (
                                            <p className="mt-1 text-xs text-red-600">
                                                {errors.theme_primary_color}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-slate-700">
                                            Background Color
                                        </label>
                                        <input
                                            type="color"
                                            value={data.theme_bg_color}
                                            onChange={(e) =>
                                                setData(
                                                    "theme_bg_color",
                                                    e.target.value,
                                                )
                                            }
                                            disabled={
                                                data.theme_preset !== "custom"
                                            }
                                            className="h-10 w-full rounded-lg border border-slate-300 bg-white p-1 disabled:opacity-50"
                                        />
                                        <p className="mt-1 text-xs text-slate-500">
                                            Used for the tenant app background.
                                        </p>
                                        {errors.theme_bg_color && (
                                            <p className="mt-1 text-xs text-red-600">
                                                {errors.theme_bg_color}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-slate-700">
                                            Sidebar Color
                                        </label>
                                        <input
                                            type="text"
                                            value={data.theme_sidebar_color}
                                            readOnly
                                            className="h-10 w-full rounded-lg border border-slate-300 bg-slate-100 px-3 py-2 font-mono text-sm text-slate-700"
                                        />
                                        <p className="mt-1 text-xs text-slate-500">
                                            Auto-derived from the primary color.
                                        </p>
                                    </div>
                                </div>

                                <div
                                    className="mt-4 rounded-xl border border-slate-200 p-4"
                                    style={{
                                        backgroundColor: data.theme_bg_color,
                                    }}
                                >
                                    <div className="flex items-center gap-3">
                                        <div
                                            className="h-10 w-10 rounded-full"
                                            style={{
                                                backgroundColor:
                                                    data.theme_primary_color,
                                            }}
                                        />
                                        <div>
                                            <p
                                                className="text-sm font-semibold"
                                                style={{
                                                    color: data.theme_sidebar_color,
                                                }}
                                            >
                                                Theme Preview
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                {themeOptions.find(
                                                    (option) =>
                                                        option.value ===
                                                        data.theme_preset,
                                                )?.label ?? "Custom"}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {processing ? "Saving..." : "Save Branding"}
                    </button>
                </div>
            </form>
        </TenantLayout>
    );
}
