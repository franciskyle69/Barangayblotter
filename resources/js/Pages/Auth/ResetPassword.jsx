import { useForm, usePage } from "@inertiajs/react";

export default function ResetPassword({ token, email }) {
    const { errors } = usePage().props;
    const { data, setData, post, processing } = useForm({
        token: token ?? "",
        email: email ?? "",
        password: "",
        password_confirmation: "",
    });

    return (
        <div className="flex min-h-screen items-center justify-center bg-[#f9fafb] px-4 font-sans antialiased">
            <div className="w-full max-w-md rounded-lg border border-slate-200/80 bg-white p-6 shadow-sm">
                <h1 className="text-2xl font-bold leading-tight text-slate-900">
                    Reset Password
                </h1>
                <p className="mt-2 text-sm text-slate-600">
                    Enter your new password to complete the reset.
                </p>

                {(errors?.email || errors?.password || errors?.token) && (
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
                        post("/reset-password");
                    }}
                    className="mt-4 space-y-4"
                >
                    <input type="hidden" value={data.token} readOnly />

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
                            onChange={(e) => setData("email", e.target.value)}
                            className="w-full rounded-lg border border-slate-300 px-3 py-2"
                            required
                            autoFocus
                        />
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
                            value={data.password}
                            onChange={(e) =>
                                setData("password", e.target.value)
                            }
                            className="w-full rounded-lg border border-slate-300 px-3 py-2"
                            required
                        />
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
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData("password_confirmation", e.target.value)
                            }
                            className="w-full rounded-lg border border-slate-300 px-3 py-2"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-devias bg-devias-primary px-4 py-2.5 font-semibold text-white shadow-sm hover:opacity-95 disabled:opacity-70"
                    >
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    );
}
