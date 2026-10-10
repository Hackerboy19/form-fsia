import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../lib/api';
import { PageHeader } from '../components/Layout';

export default function Dashboard() {
  const [stats, setStats] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let cancelled = false;
    Promise.all([api.listSeo(), api.listTeam(), api.listNews({ limit: 1 })])
      .then(([seo, team, news]) => {
        if (cancelled) return;
        setStats({
          seoMissing: seo.filter((p) => !p.meta_description || !p.og_image_url).length,
          pages: seo.length,
          team: team.length,
          hidden: team.filter((m) => !m.is_active).length,
          news: news.total,
        });
      })
      .catch((e) => !cancelled && setError(e.message));
    return () => { cancelled = true; };
  }, []);

  const tiles = stats && [
    { label: 'Managed pages', value: stats.pages, note: stats.seoMissing ? `${stats.seoMissing} missing description or share image` : 'SEO complete', to: '/seo' },
    { label: 'Team members', value: stats.team, note: stats.hidden ? `${stats.hidden} hidden from the site` : 'All visible', to: '/team' },
    { label: 'News articles', value: stats.news, note: 'Standard, Special & Top 10', to: '/news' },
  ];

  return (
    <>
      <PageHeader title="Dashboard" description="Manage content, images and SEO for the FSIA website." />
      {error && <p className="card p-4 text-sm text-red-600">{error}</p>}
      <div className="grid gap-4 sm:grid-cols-3">
        {(tiles || [1, 2, 3]).map((t, i) =>
          t.label ? (
            <Link key={t.label} to={t.to} className="card p-5 transition hover:border-gold">
              <p className="text-xs font-bold uppercase tracking-wider text-gold-dark">{t.label}</p>
              <p className="mt-2 font-display text-4xl font-bold">{t.value}</p>
              <p className="mt-1 text-sm text-slate-500">{t.note}</p>
            </Link>
          ) : (
            <div key={i} className="card h-32 animate-pulse bg-cream" />
          ),
        )}
      </div>
      <div className="card mt-6 grid gap-3 p-5 sm:grid-cols-2">
        <Link to="/homepage" className="btn-gold">⌂ Homepage slider, calendar &amp; ads</Link>
        <Link to="/news/new" className="btn-ghost">+ Write a news article</Link>
        <Link to="/team?new=1" className="btn-ghost">+ Add a team member</Link>
      </div>
    </>
  );
}
