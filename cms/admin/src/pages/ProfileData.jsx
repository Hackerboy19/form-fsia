import { useEffect, useState } from 'react';
import { api } from '../lib/api';

const PROBLEMS = [
  ['bio', 'Bio missing', 'bg-orange-100 text-orange-800'],
  ['banner', 'Banner missing', 'bg-sky-100 text-sky-800'],
  ['image', 'Image missing', 'bg-red-100 text-red-700'],
  ['seo', 'SEO issue', 'bg-violet-100 text-violet-700'],
];
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const LIVE_LIMIT = 200;

/**
 * Profiles straight from the database, filtered by category / year / month and by
 * what is missing (bio, banner, photos on the server, SEO fields). The matching
 * pages can then be opened live by the Site Check.
 */
export default function ProfileData({ onCheckLive, disabled }) {
  const [f, setF] = useState({ category: '', year: '', month: '', status: 'approved', q: '' });
  const [problems, setProblems] = useState([]);
  const [match, setMatch] = useState('any');
  const [page, setPage] = useState(1);
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const params = { ...f, problem: problems.join(','), match };
  const key = JSON.stringify(params);

  useEffect(() => { setPage(1); }, [key]);
  useEffect(() => {
    let alive = true;
    setLoading(true);
    const t = setTimeout(() => {
      api.siteCheckProfiles({ ...params, page })
        .then((d) => { if (alive) { setData(d); setError(''); } })
        .catch((e) => alive && setError(e.message))
        .finally(() => alive && setLoading(false));
    }, f.q ? 400 : 0);
    return () => { alive = false; clearTimeout(t); };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key, page]);

  const set = (k) => (e) => setF((p) => ({ ...p, [k]: e.target.value }));
  const toggle = (k) => setProblems((p) => (p.includes(k) ? p.filter((x) => x !== k) : [...p, k]));
  const pages = data ? Math.max(1, Math.ceil(data.total / data.page_size)) : 1;

  async function downloadCsv() {
    try {
      const all = await api.siteCheckProfiles({ ...params, all: 1 });
      const esc = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;
      const lines = [['ID', 'Name', 'Category', 'Date', 'Link', 'Photos', 'Bio', 'Banner', 'Image', 'SEO'].map(esc).join(',')];
      all.rows.forEach((r) => lines.push([
        r.id, r.name, r.category, r.date, r.link, r.photos,
        ...PROBLEMS.map(([k]) => (r.problems[k] || []).join('; ')),
      ].map(esc).join(',')));
      const a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob(['﻿' + lines.join('\n')], { type: 'text/csv' }));
      a.download = `fsia-profiles-${new Date().toISOString().slice(0, 10)}.csv`;
      a.click();
      URL.revokeObjectURL(a.href);
    } catch (e) { setError(e.message); }
  }

  return (
    <div>
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <label>
          <span className="label">Category</span>
          <select className="input" value={f.category} onChange={set('category')}>
            <option value="">All categories</option>
            {data?.categories.map((c) => <option key={c.key} value={c.key}>{c.label}</option>)}
          </select>
        </label>
        <label>
          <span className="label">Year</span>
          <select className="input" value={f.year} onChange={set('year')}>
            <option value="">All years</option>
            {data?.years.map((y) => <option key={y} value={y}>{y}</option>)}
          </select>
        </label>
        <label>
          <span className="label">Month</span>
          <select className="input" value={f.month} onChange={set('month')}>
            <option value="">All months</option>
            {MONTHS.map((m, i) => <option key={m} value={i + 1}>{m}</option>)}
          </select>
        </label>
        <label>
          <span className="label">Profiles</span>
          <select className="input" value={f.status} onChange={set('status')}>
            <option value="approved">Live (approved)</option>
            <option value="all">All, incl. pending</option>
          </select>
        </label>
        <label>
          <span className="label">Name / ID</span>
          <input className="input" placeholder="e.g. azra or 86294" value={f.q} onChange={set('q')} />
        </label>
      </div>

      <div className="mt-4 flex flex-wrap items-center gap-2">
        <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Show profiles with:</span>
        {PROBLEMS.map(([k, label, cls]) => {
          const on = problems.includes(k);
          return (
            <button key={k} type="button" onClick={() => toggle(k)} aria-pressed={on}
              className={`rounded-full border px-3 py-1.5 text-xs font-bold transition ${on ? `${cls} border-transparent ring-2 ring-gold` : 'border-slate-200 text-slate-600 hover:border-gold'}`}>
              {on ? '✓ ' : ''}{label}{data ? ` (${data.counts[k].toLocaleString()})` : ''}
            </button>
          );
        })}
        {problems.length > 1 && (
          <select className="input !w-auto !py-1.5 text-xs" value={match} onChange={(e) => setMatch(e.target.value)} aria-label="Match">
            <option value="any">any of these</option>
            <option value="all">all of these</option>
          </select>
        )}
        {problems.length > 0 && <button type="button" className="text-xs font-semibold text-gold-dark underline" onClick={() => setProblems([])}>Clear</button>}
      </div>

      {error && <p className="mt-3 text-sm text-red-600">{error}</p>}

      <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
        <p className="text-sm text-slate-600">
          {data ? (
            <>
              <b>{data.total.toLocaleString()}</b> of {data.scanned.toLocaleString()} profiles
              {data.capped && <span className="text-amber-700"> (first {data.scanned.toLocaleString()} only: narrow the filters)</span>}
              {' · '}{data.counts.ok.toLocaleString()} with nothing missing
            </>
          ) : 'Loading…'}
          {loading && data && <span className="ml-2 text-slate-400">updating…</span>}
        </p>
        {data?.total > 0 && (
          <div className="flex flex-wrap gap-2">
            <button type="button" className="btn-ghost !py-1.5" onClick={downloadCsv}>⤓ Download list</button>
            <button type="button" className="btn-gold !py-1.5" disabled={disabled || !data.paths.length}
              onClick={() => onCheckLive(data.paths.slice(0, LIVE_LIMIT))}
              title="Open these pages on the live site and check status, images and SEO tags">
              ▶ Check {Math.min(data.paths.length, LIVE_LIMIT)} page{data.paths.length === 1 ? '' : 's'} live
            </button>
          </div>
        )}
      </div>

      {data?.rows.length > 0 && (
        <div className="mt-3 overflow-x-auto rounded-xl border border-slate-100">
          <table className="w-full min-w-[640px] text-left text-sm">
            <thead className="bg-cream/60 text-[11px] uppercase tracking-wider text-slate-500">
              <tr><th className="p-3">Profile</th><th className="p-3">Category</th><th className="p-3">Joined</th><th className="p-3">What is missing</th></tr>
            </thead>
            <tbody>
              {data.rows.map((r) => (
                <tr key={r.id} className="border-t border-slate-100 align-top">
                  <td className="p-3">
                    {r.link ? <a href={r.link} target="_blank" rel="noreferrer" className="font-semibold underline decoration-gold/50 hover:text-gold-dark">{r.name}</a> : <span className="font-semibold">{r.name}</span>}
                    <span className="block text-[11px] text-slate-400">#{r.id} · {r.photos} photo{r.photos === 1 ? '' : 's'}{r.status === 3 ? ' · title holder' : ''}</span>
                  </td>
                  <td className="p-3 text-slate-600">{r.category}</td>
                  <td className="whitespace-nowrap p-3 text-slate-600">{r.date}</td>
                  <td className="p-3">
                    {Object.keys(r.problems).length === 0 ? (
                      <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800">✓ Complete</span>
                    ) : (
                      <ul className="space-y-1">
                        {PROBLEMS.filter(([k]) => r.problems[k]).map(([k, label, cls]) => (
                          <li key={k} className="text-xs">
                            <span className={`mr-1.5 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase ${cls}`}>{label.split(' ')[0]}</span>
                            {r.problems[k].join(', ')}
                          </li>
                        ))}
                      </ul>
                    )}
                    {r.missing_photos.length > 0 && (
                      <p className="mt-1 break-all text-[11px] text-slate-400">Missing: {r.missing_photos.join(', ')}</p>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {pages > 1 && (
        <div className="mt-3 flex items-center justify-center gap-3 text-sm">
          <button type="button" className="btn-ghost !py-1.5" disabled={page <= 1} onClick={() => setPage(page - 1)}>← Previous</button>
          <span>Page {page} of {pages}</span>
          <button type="button" className="btn-ghost !py-1.5" disabled={page >= pages} onClick={() => setPage(page + 1)}>Next →</button>
        </div>
      )}
    </div>
  );
}
