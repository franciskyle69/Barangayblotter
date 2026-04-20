import { useForm, usePage } from "@inertiajs/react";

export default function ForgotPassword() {
    const { errors, flash } = usePage().props;
    const { data, setData, post, processing } = useForm({
        email: "",
    });

    return (
        <div className="flex min-h-screen items-center justify-center bg-[#f9fafb] px-4 font-sans antialiased">
            <div className="w-full max-w-md rounded-lg border border-slate-200/80 bg-white p-6 shadow-sm">
                <h1 className="text-2xl font-bold leading-tight text-slate-900">
                    Forgot Password
                </h1>
                <p className="mt-2 text-sm text-slate-600">
                    Enter your email and we will send a password reset link.
                </p>

                {flash?.success && (
                    <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                        {flash.success}
                    </div>
                )}

                {errors?.email && (
                    <div className="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        {errors.email}
                    </div>
                )}

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post("/forgot-password");
                    }}
                    className="mt-4 space-y-4"
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
                            onChange={(e) => setData("email", e.target.value)}
                            className="w-full rounded-lg border border-slate-300 px-3 py-2"
                            required
                            autoFocus
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-devias bg-devias-primary px-4 py-2.5 font-semibold text-white shadow-sm hover:opacity-95 disabled:opacity-70"
                    >
                        Send Reset Link
                    </button>
                </form>

                <p className="mt-4 text-center text-sm text-slate-600">
                    <a
                        href="/login"
                        className="font-medium text-devias-primary hover:underline"
                    >
                        Back to sign in
                    </a>
                </p>
            </div>
        </div>
    );
}
