import { Link, router, usePage } from '@inertiajs/react';

const navItems = (tenant, role) => {
  const isCitizenLike = ['resident', 'citizen'].includes(role);
  if (isCitizenLike) {
    return [
      { label: 'Overview', mobileLabel: 'Dashboard', href: '/dashboard' },
      { label: 'Incidents', mobileLabel: 'Incidents', href: '/incidents' },
      { label: 'My Requests', mobileLabel: 'Requests', href: '/blotter-requests' },
    ];
  }

  const items = [
    { label: 'Overview', mobileLabel: 'Dashboard', href: '/dashboard' },
    { label: 'Incidents', mobileLabel: 'Incidents', href: '/incidents' },
  ];

  if (tenant?.plan?.mediation_scheduling) {
    items.push({ label: 'Mediations', mobileLabel: 'Mediations', href: '/mediations' });
  }

  items.push(
    { label: 'Patrol', mobileLabel: 'Patrol', href: '/patrol' },
    { label: 'Blotter Requests', mobileLabel: 'Blotter', href: '/blotter-requests' }
  );

  return items;
};

const ChevronDownIcon = () => (
  <svg className="size-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
  </svg>
);

export default function TenantLayout({ children }) {
  const page = usePage();
  const { auth, current_tenant, current_tenant_role, app_name, flash, logo_url } = page.props;
  const user = auth?.user;
  const items = navItems(current_tenant, current_tenant_role);
  const path = page.url || (typeof window !== 'undefined' ? window.location.pathname : '');

  const isActive = (href) => {
    if (href === '/dashboard') return path === href;
    return path.startsWith(href);
  };

  return (
    <div className="flex min-h-screen" style={{ backgroundColor: 'var(--color-tenant-bg, #f8fafc)' }}>
      {/* TENANT APP SIDEBAR - Purple/Indigo Theme */}
      <aside className="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r lg:flex" style={{ backgroundColor: 'var(--color-tenant-sidebar, #121621)', borderColor: 'rgba(255, 255, 255, 0.05)' }}>
        <div className="flex h-16 shrink-0 items-center gap-2 border-b px-6" style={{ borderColor: 'rgba(255, 255, 255, 0.05)' }}>
          <img
            src={logo_url || '/images/logo.png'}
            alt="Logo"
            className="h-9 w-9 shrink-0 rounded-devias object-contain"
            style={{ backgroundColor: 'rgba(99, 91, 255, 0.2)' }}
            onError={(e) => {
              e.target.style.display = 'none';
              e.target.nextSibling?.classList.remove('hidden');
            }}
          />
          <span className="hidden size-9 items-center justify-center rounded-devias text-sm font-bold text-white" style={{ backgroundColor: 'var(--color-tenant-primary, #635bff)' }} aria-hidden>MB</span>
          <span className="truncate text-base font-semibold text-white">{app_name || 'Malaybalay Barangay Blotter'}</span>
        </div>
        <nav className="flex-1 space-y-0.5 overflow-y-auto px-3 py-4" aria-label="Main">
          {items.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={`flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium transition ${
                isActive(item.href) ? 'text-white' : 'text-slate-400 hover:text-white'
              }`}
              style={{
                backgroundColor: isActive(item.href) ? 'rgba(99, 91, 255, 0.2)' : 'transparent',
              }}
            >
              {item.label}
            </Link>
          ))}
        </nav>
      </aside>

      <div className="flex flex-1 flex-col lg:pl-64">
        <header className="sticky top-0 z-30 flex h-16 shrink-0 flex-wrap items-center gap-3 border-b border-slate-200 bg-white px-4 shadow-sm sm:gap-4 sm:px-6">
          <div className="flex flex-1 flex-wrap items-center gap-1 lg:hidden">
            {items.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className={`rounded-devias px-2.5 py-1.5 text-sm font-medium transition ${
                  isActive(item.href) ? 'text-purple-600' : 'text-slate-600 hover:bg-slate-100'
                }`}
                style={{
                  backgroundColor: isActive(item.href) ? 'rgba(99, 91, 255, 0.1)' : 'transparent',
                }}
              >
                {item.mobileLabel ?? item.label}
              </Link>
            ))}
          </div>
          {/* TENANT BADGE - Green/Emerald for barangay identity */}
          {current_tenant && (
            <div className="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide" style={{ borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', color: '#059669' }}>
              🏘️ {current_tenant.name}
            </div>
          )}
          <div className="ml-auto flex items-center justify-end gap-3">
            {current_tenant && (
              <Link
                href="/tenant/select"
                className="flex items-center gap-2 rounded-devias border border-slate-200 px-3 py-2 text-sm font-medium transition"
                style={{ backgroundColor: '#f8fafc', color: '#475569' }}
                onMouseEnter={(e) => { e.target.style.backgroundColor = '#f1f5f9'; }}
                onMouseLeave={(e) => { e.target.style.backgroundColor = '#f8fafc'; }}
              >
                <span>Switch</span>
                <ChevronDownIcon />
              </Link>
            )}
            <span className="text-sm text-slate-600">{user?.name}</span>
            <button
              type="button"
              onClick={() => router.post('/logout')}
              className="rounded-devias border border-slate-200 px-3 py-2 text-sm font-medium transition"
              style={{ backgroundColor: '#ffffff', color: '#475569' }}
              onMouseEnter={(e) => { e.target.style.backgroundColor = '#f8fafc'; }}
              onMouseLeave={(e) => { e.target.style.backgroundColor = '#ffffff'; }}
            >
              Logout
            </button>
          </div>
        </header>

        <main className="flex-1 p-6" style={{ backgroundColor: 'var(--color-tenant-bg, #f8fafc)' }}>
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
