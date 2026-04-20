import { useForm } from "@inertiajs/react";
import { useEffect } from "react";

export default function ForcedPasswordChangeModal({ open }) {
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

    useEffect(() => {
        if (typeof document === "undefined") {
            return undefined;
        }

        const previousOverflow = document.body.style.overflow;

        if (open) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = previousOverflow;
        }

        return () => {
            document.body.style.overflow = previousOverflow;
        };
    }, [open]);

    if (!open) {
        return null;
    }

    const submit = (e) => {
        e.preventDefault();

        put("/password/force-change", {
            preserveScroll: true,
            onSuccess: () => {
                reset("current_password", "password", "password_confirmation");
            },
        });
    };

    const inputClass =
        "w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500";

    return (
        <div className="fixed inset-0 z-[120] flex items-center justify-center bg-slate-900/70 p-4">
            <div className="w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div className="border-b border-slate-200 px-6 py-5">
                    <h2 className="text-xl font-bold text-slate-900">
                        Password Change Required
                    </h2>
                    <p className="mt-1 text-sm text-slate-600">
                        Your account is using a temporary password. Update it now before continuing.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-4 px-6 py-5">
                    <div>
                        <label className="mb-1 block text-sm font-medium text-slate-700">
                            Current Password
                        </label>
                        <input
                            type="password"
                            value={data.current_password}
                            onChange={(e) => setData("current_password", e.target.value)}
                            className={inputClass}
                            required
                            autoFocus
                        />
                        {errors.current_password && (
                            <p className="mt-1 text-xs text-red-600">{errors.current_password}</p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-slate-700">
                            New Password
                        </label>
                        <input
                            type="password"
                            value={data.password}
                            onChange={(e) => setData("password", e.target.value)}
                            className={inputClass}
                            required
                        />
                        {errors.password && (
                            <p className="mt-1 text-xs text-red-600">{errors.password}</p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-slate-700">
                            Confirm New Password
                        </label>
                        <input
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData("password_confirmation", e.target.value)
                            }
                            className={inputClass}
                            required
                        />
                    </div>

                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        Navigation and actions remain locked until password update succeeds.
                    </div>

                    <div className="flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                        >
                            {processing ? "Updating..." : "Update Password"}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
