import { useState } from 'react';
import { NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../lib/auth';

const NAV = [
  { to: '/', label: 'Dashboard', icon: '◈', end: true },
  { to: '/seo', label: 'Global SEO', icon: '⌕' },
  { group: 'Pages' },
  { to: '/pages/index', label: 'Home page', icon: '⌂' },
  { to: '/pages/about', label: 'About page', icon: '✦' },
  { group: 'Content' },
  { to: '/team', label: 'Team Directory', icon: '♛' },
  { to: '/news', label: 'News Manager', icon: '✎' },
];

export default function Layout() {
  const { logout } = useAuth();
  const [open, setOpen] = useState(false);
  const { pathname } = useLocation();

  const nav = (
    <nav className="flex flex-1 flex-col gap-1 p-3" aria-label="Main">
      {NAV.map((item, i) =>
        item.group ? (
          <p key={i} className="mt-4 px-3 pb-1 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">{item.group}</p>
        ) : (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.end}
            onClick={() => setOpen(false)}
            className={({ isActive }) =>
              `flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition ${
                isActive ? 'bg-gold text-navy' : 'text-slate-300 hover:bg-white/5 hover:text-white'
              }`
            }
          >
            <span className="w-5 text-center" aria-hidden="true">{item.icon}</span>
            {item.label}
          </NavLink>
        ),
      )}
    </nav>
  );

  return (
    <div className="flex min-h-screen">
      {/* Sidebar: fixed on desktop, slide-over on mobile */}
      <aside
        className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-navy transition-transform lg:translate-x-0 ${open ? 'translate-x-0' : '-translate-x-full'}`}
      >
        <div className="border-b border-white/10 px-5 py-5">
          <p className="font-display text-lg font-bold text-white">FSIA <span className="text-gold">CMS</span></p>
          <p className="text-xs text-slate-400">Forever Star India</p>
        </div>
        {nav}
        <div className="border-t border-white/10 p-3">
          <button type="button" onClick={logout} className="w-full rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-300 hover:bg-white/5 hover:text-white">
            ⎋ Sign out
          </button>
        </div>
      </aside>
      {open && <button type="button" aria-label="Close menu" className="fixed inset-0 z-30 bg-navy/60 lg:hidden" onClick={() => setOpen(false)} />}

      <div className="flex min-w-0 flex-1 flex-col lg:pl-64">
        <header className="sticky top-0 z-20 flex items-center gap-3 border-b border-gold-light/60 bg-white/90 px-4 py-3 backdrop-blur lg:hidden">
          <button type="button" className="btn-ghost !px-3 !py-2" onClick={() => setOpen(true)} aria-label="Open menu">☰</button>
          <p className="font-display font-bold">FSIA <span className="text-gold-dark">CMS</span></p>
        </header>
        <main key={pathname} className="mx-auto w-full max-w-6xl flex-1 p-4 sm:p-6 lg:p-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
}

export function PageHeader({ title, description, actions }) {
  return (
    <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 className="font-display text-2xl font-bold sm:text-3xl">{title}</h1>
        {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
      </div>
      {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
    </div>
  );
}
