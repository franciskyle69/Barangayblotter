import { useState } from "react";
import { useForm, usePage } from "@inertiajs/react";

export default function Register() {
    const { errors, logo_url, registrationRoleOptions, current_tenant } =
        usePage().props;
    const [logoError, setLogoError] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] =
        useState(false);
    const roleOptions = registrationRoleOptions ?? {};
    const { data, setData, post, processing } = useForm({
        name: "",
        email: "",
        phone: "",
        requested_role: "citizen",
        password: "",
        password_confirmation: "",
    });

    const logoSrc = logo_url || "/images/logo.png";
    const tenantLabel = current_tenant?.slug || current_tenant?.name;
    const title = tenantLabel
        ? `Barangay ${tenantLabel} Blotter`
        : "Barangay Blotter Tenancy";

    return (
        <div className="flex min-h-screen items-center justify-center bg-[#f9fafb] px-4 font-sans antialiased">
            <div className="w-full max-w-md">
                <div className="mb-8 flex flex-col items-center justify-center text-center">
                    <div className="relative mb-4 flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-devias bg-white shadow-md ring-1 ring-slate-200/80">
                        {!logoError ? (
                            <img
                                src={logoSrc}
                                alt="Logo"
                                className="h-full w-full object-contain object-center"
                                onError={() => setLogoError(true)}
                            />
                        ) : (
                            <span className="flex size-full items-center justify-center rounded-devias bg-devias-primary text-2xl font-bold text-white">
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
                            {Object.values(errors)
                                .flat()
                                .map((msg, i) => (
                                    <li key={i}>{msg}</li>
                                ))}
                        </ul>
                    </div>
                )}
                <div className="rounded-lg border border-slate-200/80 bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-lg font-semibold text-slate-800">
                        Register
                    </h2>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            post("/register");
                        }}
                        className="space-y-4"
                    >
                        <div>
                            <label
                                htmlFor="name"
                                className="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Name
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) =>
                                    setData("name", e.target.value)
                                }
                                className="w-full rounded-lg border border-slate-300 px-3 py-2"
                                required
                                autoFocus
                            />
                        </div>
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
                                className="w-full rounded-lg border border-slate-300 px-3 py-2"
                                required
                            />
                        </div>
                        <div>
                            <label
                                htmlFor="phone"
                                className="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Phone (optional)
                            </label>
                            <input
                                id="phone"
                                type="text"
                                value={data.phone}
                                onChange={(e) =>
                                    setData("phone", e.target.value)
                                }
                                className="w-full rounded-lg border border-slate-300 px-3 py-2"
                            />
                        </div>
                        <div>
                            <label
                                htmlFor="requested_role"
                                className="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Role
                            </label>
                            <select
                                id="requested_role"
                                value={data.requested_role}
                                onChange={(e) =>
                                    setData("requested_role", e.target.value)
                                }
                                className="w-full rounded-lg border border-slate-300 px-3 py-2"
                            >
                                {Object.entries(roleOptions).map(
                                    ([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </select>
                            <p className="mt-1 text-xs text-slate-500">
                                Selecting admin/staff roles requires super admin
                                approval after registration.
                            </p>
                        </div>
                        <div>
                            <label
                                htmlFor="password"
                                className="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Password
                            </label>
                            <div className="relative">
                                <input
                                    id="password"
                                    type={showPassword ? "text" : "password"}
                                    value={data.password}
                                    onChange={(e) =>
                                        setData("password", e.target.value)
                                    }
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 pr-10"
                                    required
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword((v) => !v)}
                                    className="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700"
                                    aria-label={
                                        showPassword
                                            ? "Hide password"
                                            : "Show password"
                                    }
                                >
                                    {showPassword ? "Hide" : "Show"}
                                </button>
                            </div>
                        </div>
                        <div>
                            <label
                                htmlFor="password_confirmation"
                                className="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Confirm password
                            </label>
                            <div className="relative">
                                <input
                                    id="password_confirmation"
                                    type={
                                        showPasswordConfirmation
                                            ? "text"
                                            : "password"
                                    }
                                    value={data.password_confirmation}
                                    onChange={(e) =>
                                        setData(
                                            "password_confirmation",
                                            e.target.value,
                                        )
                                    }
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 pr-10"
                                    required
                                />
                                <button
                                    type="button"
                                    onClick={() =>
                                        setShowPasswordConfirmation((v) => !v)
                                    }
                                    className="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700"
                                    aria-label={
                                        showPasswordConfirmation
                                            ? "Hide confirm password"
                                            : "Show confirm password"
                                    }
                                >
                                    {showPasswordConfirmation ? "Hide" : "Show"}
                                </button>
                            </div>
                        </div>
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-devias bg-devias-primary px-4 py-2.5 font-semibold text-white shadow-sm hover:opacity-95 disabled:opacity-70"
                        >
                            Register
                        </button>
                    </form>
                    <p className="mt-4 text-center text-sm text-slate-600">
                        Already have an account?{" "}
                        <a
                            href="/login"
                            className="font-medium text-devias-primary hover:underline"
                        >
                            Sign in
                        </a>
                    </p>
                </div>
            </div>
        </div>
    );
}
