import { Head, useForm, router } from "@inertiajs/react";

/**
 * Full-page forced-password-change form. Shown when a user has
 * `must_change_password = true` — they cannot reach any other page until
 * they submit a new password. This is a dedicated page (not a layout +
 * modal) so no tenant data is loaded into the browser before the
 * temporary credential is rotated.
 */
export default function ForcedPasswordChange({ user }) {
    const {
        data,
        setData,
        put,
        processing,
        errors,
        reset,
    } = useForm({
        current_password: "",
        password: "",
        password_confirmation: "",
    });

    const submit = (e) => {
        e.preventDefault();
        put(route("password.force.update"), {
            preserveScroll: true,
            onSuccess: () => {
                reset("current_password", "password", "password_confirmation");
                router.visit(route("dashboard"));
            },
        });
    };

    const inputClass =
        "w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:bg-slate-100";

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-4 py-10">
            <Head title="Password change required" />

            <div className="mx-auto flex max-w-xl flex-col items-center gap-6">
                <div className="flex size-14 items-center justify-center rounded-2xl bg-amber-500/15 ring-1 ring-amber-500/30">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="size-7 text-amber-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth="1.8"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-7a2 2 0 00-2-2H6a2 2 0 00-2 2v7a2 2 0 002 2zm10-12V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </div>

                <header className="text-center">
                    <h1 className="text-2xl font-bold text-white">
                        Password change required
                    </h1>
                    <p className="mt-2 text-sm text-slate-300">
                        {user?.name ? `Welcome, ${user.name}. ` : ""}
                        Your account is using a temporary password. Set a new password to continue.
                    </p>
                </header>

                <form
                    onSubmit={submit}
                    className="w-full space-y-4 rounded-2xl border border-white/10 bg-white/95 p-6 shadow-2xl backdrop-blur"
                    noValidate
                >
                    <div>
                        <label
                            htmlFor="current_password"
                            className="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Current (temporary) password
                        </label>
                        <input
                            id="current_password"
                            type="password"
                            autoComplete="current-password"
                            value={data.current_password}
                            onChange={(e) =>
                                setData("current_password", e.target.value)
                            }
                            className={inputClass}
                            required
                            autoFocus
                        />
                        {errors.current_password && (
                            <p className="mt-1 text-xs text-red-600">
                                {errors.current_password}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="password"
                            className="mb-1 block text-sm font-medium text-slate-700"
                        >
                            New password
                        </label>
                        <input
                            id="password"
                            type="password"
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData("password", e.target.value)}
                            className={inputClass}
                            required
                        />
                        {errors.password && (
                            <p className="mt-1 text-xs text-red-600">
                                {errors.password}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="password_confirmation"
                            className="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Confirm new password
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData("password_confirmation", e.target.value)
                            }
                            className={inputClass}
                            required
                        />
                        {errors.password_confirmation && (
                            <p className="mt-1 text-xs text-red-600">
                                {errors.password_confirmation}
                            </p>
                        )}
                    </div>

                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        You cannot access any page on this account until a new password is saved.
                    </div>

                    <div className="flex items-center justify-between gap-2 pt-2">
                        <a
                            href={route("logout")}
                            onClick={(e) => {
                                e.preventDefault();
                                router.post(route("logout"));
                            }}
                            className="text-sm font-medium text-slate-500 underline-offset-2 hover:text-slate-700 hover:underline"
                        >
                            Sign out instead
                        </a>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing ? "Updating…" : "Update password"}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
