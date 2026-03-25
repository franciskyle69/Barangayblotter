import { Link, router, usePage } from '@inertiajs/react';

const centralNavItems = [
  { label: 'Overview', mobileLabel: 'Dashboard', href: '/super/dashboard' },
  { label: 'All Barangays', mobileLabel: 'Barangays', href: '/super/tenants' },
];

export default function CentralLayout({ children }) {
  const page = usePage();
  const { auth, app_name, flash, logo_url } = page.props;
  const user = auth?.user;
  const path = page.url || (typeof window !== 'undefined' ? window.location.pathname : '');

  const isActive = (href) => {
    if (href === '/super/dashboard') return path === href;
    return path.startsWith(href);
  };

  return (
    <div className="flex min-h-screen" style={{ backgroundColor: 'var(--color-central-bg, #0f172a)' }}>
      {/* CENTRAL APP SIDEBAR - Deep Blue Theme */}
      <aside className="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r lg:flex" style={{ backgroundColor: 'var(--color-central-sidebar, #0f172a)', borderColor: 'rgba(255, 255, 255, 0.1)' }}>
        <div className="flex h-16 shrink-0 items-center gap-2 border-b px-6" style={{ borderColor: 'rgba(255, 255, 255, 0.1)' }}>
          <img
            src={logo_url || '/images/logo.png'}
            alt="Logo"
            className="h-9 w-9 shrink-0 rounded-devias object-contain"
            style={{ backgroundColor: 'rgba(30, 64, 175, 0.2)' }}
            onError={(e) => {
              e.target.style.display = 'none';
              e.target.nextSibling?.classList.remove('hidden');
            }}
          />
          <span className="hidden size-9 items-center justify-center rounded-devias text-sm font-bold text-white" style={{ backgroundColor: 'var(--color-central-primary, #1e40af)' }} aria-hidden>CA</span>
          <span className="truncate text-base font-semibold text-white">{app_name || 'Central Admin'}</span>
        </div>
        <nav className="flex-1 space-y-0.5 overflow-y-auto px-3 py-4" aria-label="Main">
          {centralNavItems.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={`flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium transition ${
                isActive(item.href) ? 'text-white' : 'text-slate-400 hover:text-white'
              }`}
              style={{
                backgroundColor: isActive(item.href) ? 'rgba(30, 64, 175, 0.3)' : 'transparent',
              }}
            >
              {item.label}
            </Link>
          ))}
        </nav>
      </aside>

      <div className="flex flex-1 flex-col lg:pl-64">
        {/* CENTRAL APP HEADER - Deep Blue Theme */}
        <header className="sticky top-0 z-30 flex h-16 shrink-0 flex-wrap items-center gap-3 border-b px-4 shadow sm:gap-4 sm:px-6" style={{ backgroundColor: '#1e293b', borderColor: 'rgba(30, 64, 175, 0.3)' }}>
          <div className="flex flex-1 flex-wrap items-center gap-1 lg:hidden">
            {centralNavItems.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className={`rounded-devias px-2.5 py-1.5 text-sm font-medium transition ${
                  isActive(item.href) ? 'text-blue-200' : 'text-slate-300 hover:text-blue-200'
                }`}
                style={{
                  backgroundColor: isActive(item.href) ? 'rgba(30, 64, 175, 0.3)' : 'transparent',
                }}
              >
                {item.mobileLabel ?? item.label}
              </Link>
            ))}
          </div>
          {/* CENTRAL ADMIN BADGE - Blue Theme */}
          <div className="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide" style={{ borderColor: 'rgba(6, 182, 212, 0.4)', backgroundColor: 'rgba(6, 182, 212, 0.1)', color: '#22d3ee' }}>
            🏛️ Central Admin
          </div>
          <div className="ml-auto flex items-center gap-3">
            <span className="text-sm text-slate-200">{user?.name}</span>
            <button
              type="button"
              onClick={() => router.post('/logout')}
              className="rounded-devias border px-3 py-2 text-sm font-medium transition"
              style={{ borderColor: '#475569', backgroundColor: '#1e293b', color: '#cbd5e1' }}
              onMouseEnter={(e) => { e.target.style.backgroundColor = '#334155'; }}
              onMouseLeave={(e) => { e.target.style.backgroundColor = '#1e293b'; }}
            >
              Logout
            </button>
          </div>
        </header>

        {/* CENTRAL APP MAIN - Blue Tinted Background */}
        <main className="flex-1 p-6" style={{ backgroundColor: 'var(--color-central-bg, #f0f9ff)' }}>
          {flash?.success && (
            <div className="mb-4 rounded-devias border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{flash.success}</div>
          )}
          {flash?.error && (
            <div className="mb-4 rounded-devias border border-red-200 bg-red-50 p-4 text-red-800">{flash.error}</div>
          )}
          {flash?.warning && (
            <div className="mb-4 rounded-devias border border-amber-200 bg-amber-50 p-4 text-amber-800">{flash.warning}</div>
          )}
          {children}
        </main>
      </div>
    </div>
  );
}
