import { useState } from "react";
import { useForm, usePage } from "@inertiajs/react";

export default function Login() {
    const { errors, logo_url, current_tenant } = usePage().props;
    const [logoError, setLogoError] = useState(false);
    const { data, setData, post, processing } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    const logoSrc = logo_url || "/images/logo.png";
    const tenantLabel = current_tenant?.slug || current_tenant?.name;
    const title = tenantLabel
        ? `Barangay ${tenantLabel} Blotter`
        : "Malaybalay City Barangay Blotter";
    const loginBackgroundUrl = current_tenant?.login_background_url || null;
    const loginOverlayOpacity =
        current_tenant?.login_background_opacity != null
            ? Number(current_tenant.login_background_opacity)
            : 0.45;
    const loginBackgroundBlur =
        current_tenant?.login_background_blur != null
            ? Number(current_tenant.login_background_blur)
            : 0;
    const themePrimary = current_tenant?.theme_primary_color || "#635bff";
    const themeSidebar = current_tenant?.theme_sidebar_color || "#121621";

    return (
        <div
            className="relative flex min-h-screen items-center justify-center overflow-hidden px-4 font-sans antialiased"
            style={{
                backgroundColor: current_tenant?.theme_bg_color || "#f9fafb",
            }}
        >
            {loginBackgroundUrl && (
                <>
                    <div
                        className="absolute inset-0"
                        style={{
                            backgroundImage: `url(${loginBackgroundUrl})`,
                            backgroundSize: "cover",
                            backgroundPosition: "center",
                            filter: `blur(${loginBackgroundBlur}px)`,
                            transform:
                                loginBackgroundBlur > 0
                                    ? "scale(1.08)"
                                    : "none",
                        }}
                    />
                    <div
                        className="absolute inset-0"
                        style={{
                            backgroundColor: `rgba(15, 23, 42, ${loginOverlayOpacity})`,
                        }}
                    />
                </>
            )}
            <div className="relative z-10 w-full max-w-md">
                <div className="mb-8 flex flex-col items-center justify-center text-center">
                    <div
                        className="relative mb-4 flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-devias bg-white shadow-md ring-1 ring-slate-200/80"
                        style={{ borderColor: themePrimary }}
                    >
                        {!logoError ? (
                            <img
                                src={logoSrc}
                                alt="Logo"
                                className="h-full w-full object-contain object-center"
                                onError={() => setLogoError(true)}
                            />
                        ) : (
                            <span
                                className="flex size-full items-center justify-center rounded-devias text-2xl font-bold text-white"
                                style={{ backgroundColor: themePrimary }}
                            >
                                MB
                            </span>
                        )}
                    </div>
                    <h1 className="text-2xl font-bold leading-tight text-slate-900">
                        {title}
                    </h1>
                </div>
                {errors && Object.keys(errors).length > 0 && (
                    <div className="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        <ul className="list-disc pl-4">
                            {Object.values(errors).map((msg) => (
                                <li key={msg}>{msg}</li>
                            ))}
                        </ul>
                    </div>
                )}
                <div
                    className="rounded-lg border border-slate-200/80 bg-white p-6 shadow-sm"
                    style={{
                        backgroundColor: `rgba(255,255,255,0.96)`,
                        boxShadow: `0 20px 45px -15px ${themeSidebar}33`,
                    }}
                >
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            post("/login");
                        }}
                        className="space-y-4"
                    >
                        <div>
                            <label
                                htmlFor="email"
                                className="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) =>
                                    setData("email", e.target.value)
                                }
                                className="w-full rounded-lg border px-3 py-2"
                                style={{ borderColor: themePrimary }}
                                required
                                autoFocus
                            />
                        </div>
                        <div>
                            <label
                                htmlFor="password"
                                className="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Password
                            </label>
                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) =>
                                    setData("password", e.target.value)
                                }
                                className="w-full rounded-lg border px-3 py-2"
                                style={{ borderColor: themePrimary }}
                                required
                            />
                            <div className="mt-2 text-right">
                                <a
                                    href="/forgot-password"
                                    className="text-sm font-medium hover:underline"
                                    style={{ color: themePrimary }}
                                >
                                    Forgot password?
                                </a>
                            </div>
                        </div>
                        <div className="flex items-center">
                            <input
                                id="remember"
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) =>
                                    setData("remember", e.target.checked)
                                }
                                className="rounded"
                            />
                            <label
                                htmlFor="remember"
                                className="ml-2 text-sm text-slate-600"
                            >
                                Remember me
                            </label>
                        </div>
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-devias px-4 py-2.5 font-semibold text-white shadow-sm hover:opacity-95 disabled:opacity-70"
                            style={{ backgroundColor: themePrimary }}
                        >
                            Sign in
                        </button>
                    </form>
                    {current_tenant ? (
                        <p className="mt-4 text-center text-sm text-slate-600">
                            Don't have an account?{" "}
                            <a
                                href="/register"
                                className="font-medium hover:underline"
                                style={{ color: themePrimary }}
                            >
                                Register
                            </a>
                        </p>
                    ) : (
                        <p className="mt-4 text-center text-sm text-slate-600">
                            Central app access is admin-only. Admin accounts are
                            created by the super admin.
                        </p>
                    )}
                    {!current_tenant && (
                        <p className="mt-2 text-center text-sm text-slate-600">
                            Need a barangay workspace?{" "}
                            <a
                                href="/tenant-signup"
                                className="font-medium hover:underline"
                                style={{ color: themePrimary }}
                            >
                                Request tenant signup
                            </a>
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
