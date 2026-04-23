/**
 * Consistent, accessible error summary banner for Inertia forms.
 *
 * Pass the `errors` object directly from `useForm()`. Renders nothing
 * when `errors` is empty. When there is at least one error, renders a
 * red-styled alert with a list of the messages. This replaces the
 * previous pattern where each form either (a) rendered nothing on 422
 * responses (silent stuck form) or (b) handcrafted its own banner with
 * slightly different markup — a source of UX inconsistency between
 * pages.
 */
export default function FormErrorSummary({ errors, className = "" }) {
    if (!errors) return null;

    const entries = Object.entries(errors).filter(
        ([, msg]) => typeof msg === "string" && msg.trim() !== "",
    );
    if (entries.length === 0) return null;

    return (
        <div
            role="alert"
            aria-live="polite"
            className={`rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 ${className}`}
        >
            <p className="mb-1 font-semibold">
                Please fix the following{entries.length > 1 ? " issues" : " issue"}:
            </p>
            <ul className="list-disc space-y-0.5 pl-5">
                {entries.map(([field, msg]) => (
                    <li key={field}>{msg}</li>
                ))}
            </ul>
        </div>
    );
}

/**
 * Small helper for per-field inline error text. Use directly under an
 * input:
 *
 *   <input ... />
 *   <FieldError message={errors.email} />
 */
export function FieldError({ message }) {
    if (!message) return null;
    return <p className="mt-1 text-xs text-red-600">{message}</p>;
}
