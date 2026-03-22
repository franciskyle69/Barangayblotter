import { Link, router, usePage } from '@inertiajs/react';

const navItems = (tenant, isSuperAdmin) => {
  if (isSuperAdmin) {
    return [
      { label: 'Overview', mobileLabel: 'Dashboard', href: '/super/dashboard' },
      { label: 'All Barangays', mobileLabel: 'Barangays', href: '/super/tenants' },
    ];
  }
  const items = [
    { label: 'Overview', mobileLabel: 'Dashboard', href: '/dashboard' },
    { label: 'Incidents', mobileLabel: 'Incidents', href: '/incidents' },
    { label: 'New Incident', mobileLabel: 'New', href: '/incidents/create' },
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

export default function AppLayout({ children }) {
  const page = usePage();
  const { auth, current_tenant, app_name, flash, logo_url } = page.props;
  const user = auth?.user;
  const isSuperAdmin = user?.is_super_admin;
  const items = navItems(current_tenant, isSuperAdmin);
  const path = page.url || (typeof window !== 'undefined' ? window.location.pathname : '');
  const isActive = (href) => {
    if (href === '/dashboard' || href === '/super/dashboard') return path === href;
    return path.startsWith(href);
  };

  return (
    <div className="flex min-h-screen">
      {/* Sidebar (Devias-style) - hidden on small screens - copy of layouts/app.blade.php */}
      <aside className="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-white/5 bg-devias-sidebar lg:flex">
        <div className="flex h-16 shrink-0 items-center gap-2 border-b border-white/5 px-6">
          <img
            src={logo_url || '/images/logo.png'}
            alt="Logo"
            className="h-9 w-9 shrink-0 rounded-devias object-contain bg-devias-primary/20"
            onError={(e) => {
              e.target.style.display = 'none';
              e.target.nextSibling?.classList.remove('hidden');
            }}
          />
          <span className="hidden size-9 flex items-center justify-center rounded-devias bg-devias-primary text-sm font-bold text-white" aria-hidden>MB</span>
          <span className="truncate text-base font-semibold text-white">{app_name || 'Malaybalay City Barangay Blotter'}</span>
        </div>
        <nav className="flex-1 space-y-0.5 overflow-y-auto px-3 py-4" aria-label="Main">
          {items.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={`flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium transition ${
                isActive(item.href) ? 'bg-devias-primary/20 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white'
              }`}
            >
              {item.label}
            </Link>
          ))}
        </nav>
      </aside>

      {/* Main content area */}
      <div className="flex flex-1 flex-col lg:pl-64">
        {/* Top bar (Devias MainNav-style) - copy of layouts/app.blade.php */}
        <header className="sticky top-0 z-30 flex h-16 shrink-0 flex-wrap items-center gap-3 border-b border-slate-200 bg-white px-4 shadow-sm sm:gap-4 sm:px-6">
          {/* Mobile nav links (visible when sidebar hidden) */}
          <div className="flex flex-1 flex-wrap items-center gap-1 lg:hidden">
            {items.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className={`rounded-devias px-2.5 py-1.5 text-sm font-medium ${
                  isActive(item.href) ? 'bg-devias-primary/10 text-devias-primary' : 'text-slate-600 hover:bg-slate-100'
                }`}
              >
                {item.mobileLabel ?? item.label}
              </Link>
            ))}
          </div>
          <div className="flex items-center justify-end gap-3">
            {!isSuperAdmin && current_tenant && (
              <Link
                href="/tenant/select"
                className="flex items-center gap-2 rounded-devias border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
              >
                <span>{current_tenant.name}</span>
                <ChevronDownIcon />
              </Link>
            )}
            <span className="text-sm text-slate-600">{user?.name}</span>
            <button
              type="button"
              onClick={() => router.post('/logout')}
              className="rounded-devias border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
              Logout
            </button>
          </div>
        </header>

        <main className="flex-1 p-6">
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
