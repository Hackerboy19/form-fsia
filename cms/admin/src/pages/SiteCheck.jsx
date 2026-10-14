import { useEffect, useMemo, useRef, useState } from 'react';
import { api } from '../lib/api';
import { PageHeader } from '../components/Layout';

const SITE = 'https://www.fsia.in';
const LEVELS = {
  error: { label: 'Error', cls: 'bg-red-100 text-red-700' },
  change: { label: 'Changed', cls: 'bg-violet-100 text-violet-700' },
  warn: { label: 'Warning', cls: 'bg-amber-100 text-amber-800' },
  info: { label: 'Note', cls: 'bg-slate-100 text-slate-600' },
};
const short = (u) => u.replace(/^https?:\/\/(www\.)?fsia\.in/i, '') || '/';
const pick = (list, n) => {
  const a = [...list];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a.slice(0, n);
};
// Most serious issue level of a page; 'ok' when it has none.
const RANK = { error: 0, change: 1, warn: 2, info: 3, ok: 4 };
const worst = (r) => (r.issues || []).reduce((w, i) => (RANK[i.level] < RANK[w] ? i.level : w), 'ok');

/** Checks pages of the live site: opens, images, SEO tags, and changes since the saved baseline. */
export default function SiteCheck() {
  const [lists, setLists] = useState(null);
  const [mode, setMode] = useState('main');
  const [count, setCount] = useState(25);
  const [query, setQuery] = useState('');
  const [matches, setMatches] = useState([]);
  const [custom, setCustom] = useState('');
  const [results, setResults] = useState([]);
  const [progress, setProgress] = useState(null); // { done, total }
  const [onlyProblems, setOnlyProblems] = useState(false);
  const [open, setOpen] = useState(null);
  const [msg, setMsg] = useState('');
  const [error, setError] = useState('');
  const stop = useRef(false);

  useEffect(() => {
    api.siteCheckUrls().then(setLists).catch((e) => setError(e.message));
  }, []);

  useEffect(() => {
    if (mode !== 'search' || query.trim().length < 3) { setMatches([]); return undefined; }
    const t = setTimeout(() => api.siteCheckUrls(query.trim()).then((d) => setMatches(d.matches)).catch(() => {}), 350);
    return () => clearTimeout(t);
  }, [mode, query]);

  function targets() {
    if (!lists) return [];
    if (mode === 'main') return lists.main;
    if (mode === 'random') return pick(lists.profiles, Math.max(1, Math.min(500, Number(count) || 25)));
    if (mode === 'all') return lists.profiles;
    if (mode === 'search') return matches;
    return custom.split(/\s+/).map((s) => s.trim()).filter(Boolean)
      .map((s) => (s.startsWith('http') ? s : s.startsWith('/') ? s : `/${s}`));
  }

  async function run() {
    const urls = [...new Set(targets())];
    if (!urls.length) { setMsg('No pages to check.'); return; }
    setMsg(''); setError(''); setResults([]); setOpen(null);
    stop.current = false;
    setProgress({ done: 0, total: urls.length });
    let i = 0;
    let done = 0;
    // Two at a time: fast enough, and gentle on a busy server.
    const worker = async () => {
      while (!stop.current && i < urls.length) {
        const u = urls[i++];
        let r;
        try {
          r = await api.siteCheck(u);
        } catch (e) {
          r = { url: u.startsWith('/') ? SITE + u : u, status: 0, issues: [{ level: 'error', text: `Check failed: ${e.message}` }], images: { total: 0, broken: [] } };
        }
        setResults((prev) => [...prev, r]);
        setProgress({ done: ++done, total: urls.length });
      }
    };
    await Promise.all([worker(), worker()]);
    setProgress((p) => (p ? { ...p, finished: true, stopped: stop.current } : p));
  }

  // Same title on several pages hurts SEO: flag it across this run.
  const rows = useMemo(() => {
    const byTitle = {};
    results.forEach((r) => { const t = r.seo?.title; if (t) (byTitle[t] ||= []).push(r.url); });
    return results.map((r) => {
      const dups = r.seo?.title && byTitle[r.seo.title].length > 1 ? byTitle[r.seo.title].filter((u) => u !== r.url) : [];
      const issues = dups.length ? [...r.issues, { level: 'warn', text: `Same title as ${dups.length} other page${dups.length > 1 ? 's' : ''}: ${dups.slice(0, 3).map(short).join(', ')}` }] : r.issues || [];
      return { ...r, issues };
    });
  }, [results]);

  const stats = useMemo(() => ({
    ok: rows.filter((r) => ['ok', 'info'].includes(worst(r))).length,
    error: rows.filter((r) => worst(r) === 'error').length,
    change: rows.filter((r) => r.issues.some((i) => i.level === 'change')).length,
    warn: rows.filter((r) => worst(r) === 'warn').length,
    images: rows.reduce((n, r) => n + (r.images?.broken?.length || 0), 0),
  }), [rows]);

  const shown = onlyProblems ? rows.filter((r) => !['ok', 'info'].includes(worst(r))) : rows;
  const running = progress && !progress.finished;

  async function saveBaseline() {
    const pages = rows.filter((r) => r.status === 200 && r.seo).map((r) => ({ url: r.url, ...r.seo }));
    if (!pages.length) return;
    if (!window.confirm(`Save the current title, description, keywords, canonical and H1 of ${pages.length} page(s) as correct? Later checks will report any change against this.`)) return;
    try {
      const { saved } = await api.siteCheckBaseline(pages);
      setMsg(`Saved ${saved} page(s) as the baseline.`);
    } catch (e) { setError(e.message); }
  }

  function downloadCsv() {
    const esc = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;
    const lines = [['URL', 'Status', 'Seconds', 'Title', 'Description', 'Keywords', 'Canonical', 'H1', 'Images', 'Broken images', 'Issues'].map(esc).join(',')];
    rows.forEach((r) => lines.push([
      r.url, r.status, r.time, r.seo?.title, r.seo?.description, r.seo?.keywords, r.seo?.canonical, (r.seo?.h1 || []).join(' | '),
      r.images?.total, (r.images?.broken || []).map((b) => `${b.status} ${b.url}`).join(' ; '),
      r.issues.map((i) => `[${LEVELS[i.level]?.label || i.level}] ${i.text}`).join(' ; '),
    ].map(esc).join(',')));
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + lines.join('\n')], { type: 'text/csv' }));
    a.download = `fsia-site-check-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  }

  const MODES = [
    ['main', 'Main pages', lists ? `${lists.main.length} pages` : ''],
    ['random', 'Random profiles', 'a sample'],
    ['search', 'Find a profile', 'by name'],
    ['custom', 'My list', 'paste URLs'],
    ['all', 'All profiles', lists ? `${lists.profiles_total.toLocaleString()} pages` : ''],
  ];

  return (
    <>
      <PageHeader
        title="Site Check"
        description="Opens pages of www.fsia.in like a visitor and reports errors, broken images, SEO tags and what changed since the saved baseline."
        actions={rows.length > 0 && !running && (
          <>
            <button type="button" className="btn-ghost" onClick={downloadCsv}>⤓ Download report</button>
            <button type="button" className="btn-ghost" onClick={saveBaseline} title="Store today's SEO tags as correct">✓ Save as baseline</button>
          </>
        )}
      />
      {error && <p className="card mb-4 p-4 text-sm text-red-600">{error}</p>}

      <div className="card p-5">
        <div className="flex flex-wrap gap-2" role="tablist">
          {MODES.map(([key, label, note]) => (
            <button key={key} type="button" role="tab" aria-selected={mode === key} onClick={() => setMode(key)} disabled={running}
              className={`rounded-xl border px-3 py-2 text-left text-sm font-semibold transition ${mode === key ? 'border-gold bg-gold/15 text-navy' : 'border-slate-200 text-slate-600 hover:border-gold'}`}>
              {label}{note && <span className="block text-[11px] font-normal text-slate-500">{note}</span>}
            </button>
          ))}
        </div>

        <div className="mt-4">
          {mode === 'main' && lists && <p className="text-sm text-slate-600">{lists.main.join('  ·  ')}</p>}
          {mode === 'random' && (
            <label className="flex items-center gap-3 text-sm">
              <span>Check</span>
              <input type="number" min="1" max="500" className="input !w-24" value={count} onChange={(e) => setCount(e.target.value)} />
              <span>profiles picked at random from {lists?.profiles_total.toLocaleString() || '…'}.</span>
            </label>
          )}
          {mode === 'search' && (
            <div>
              <input className="input" placeholder="Type part of a name or address, e.g. nishita" value={query} onChange={(e) => setQuery(e.target.value)} />
              <p className="mt-1 text-xs text-slate-500">{query.trim().length < 3 ? 'At least 3 letters.' : `${matches.length} profile${matches.length === 1 ? '' : 's'} found${matches.length === 200 ? ' (first 200)' : ''}.`}</p>
              {matches.length > 0 && <p className="mt-2 max-h-28 overflow-auto text-xs text-slate-600">{matches.join('  ·  ')}</p>}
            </div>
          )}
          {mode === 'custom' && (
            <textarea className="input min-h-28 font-mono text-xs" placeholder={'One per line:\n/about\nhttps://www.fsia.in/miss-india-contestant-2025-nishita-tahilramani-aurangabad'} value={custom} onChange={(e) => setCustom(e.target.value)} />
          )}
          {mode === 'all' && (
            <p className="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
              Checks every profile address in the site&apos;s .htaccess. At about 2 seconds each this takes several hours and adds load to the server; prefer a random sample. Keep this tab open while it runs.
            </p>
          )}
        </div>

        <div className="mt-4 flex flex-wrap items-center gap-3">
          {!running ? (
            <button type="button" className="btn-gold" onClick={run} disabled={!lists}>▶ Start check</button>
          ) : (
            <button type="button" className="btn-danger" onClick={() => { stop.current = true; }}>■ Stop</button>
          )}
          {progress && (
            <div className="min-w-48 flex-1">
              <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                <div className="h-full bg-gold transition-all" style={{ width: `${(progress.done / progress.total) * 100}%` }} />
              </div>
              <p className="mt-1 text-xs text-slate-500">
                {progress.done} / {progress.total} checked{progress.finished ? (progress.stopped ? ' (stopped)' : ' (done)') : '…'}
              </p>
            </div>
          )}
        </div>
        {msg && <p className="mt-3 text-sm font-semibold text-emerald-700">{msg}</p>}
      </div>

      {rows.length > 0 && (
        <>
          <div className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
            {[
              ['OK', stats.ok, 'text-emerald-700'],
              ['With errors', stats.error, 'text-red-700'],
              ['SEO changed', stats.change, 'text-violet-700'],
              ['Warnings only', stats.warn, 'text-amber-700'],
              ['Broken images', stats.images, 'text-red-700'],
            ].map(([label, n, cls]) => (
              <div key={label} className="card p-4">
                <p className="text-[11px] font-bold uppercase tracking-wider text-slate-500">{label}</p>
                <p className={`mt-1 font-display text-3xl font-bold ${n ? cls : 'text-slate-400'}`}>{n}</p>
              </div>
            ))}
          </div>

          <label className="mt-4 flex items-center gap-2 text-sm font-semibold">
            <input type="checkbox" checked={onlyProblems} onChange={(e) => setOnlyProblems(e.target.checked)} />
            Show only pages with problems
          </label>

          <div className="mt-3 space-y-2">
            {shown.map((r) => {
              const w = worst(r);
              const isOpen = open === r.url;
              return (
                <div key={r.url} className="card overflow-hidden">
                  <button type="button" className="flex w-full flex-wrap items-center gap-3 p-4 text-left" onClick={() => setOpen(isOpen ? null : r.url)} aria-expanded={isOpen}>
                    <span className={`rounded-lg px-2 py-1 text-xs font-bold ${r.status === 200 ? 'bg-emerald-100 text-emerald-800' : r.status >= 300 && r.status < 400 ? 'bg-sky-100 text-sky-800' : 'bg-red-100 text-red-700'}`}>
                      {r.status || 'FAIL'}
                    </span>
                    <span className="min-w-0 flex-1">
                      <span className="block truncate text-sm font-semibold">{short(r.url)}</span>
                      <span className="block truncate text-xs text-slate-500">{r.seo?.title || '—'}</span>
                    </span>
                    {r.images?.total > 0 && (
                      <span className={`text-xs font-semibold ${r.images.broken.length ? 'text-red-700' : 'text-slate-500'}`}>
                        🖼 {r.images.total - r.images.broken.length}/{r.images.total}
                      </span>
                    )}
                    <span className={`rounded-full px-2.5 py-0.5 text-[11px] font-bold ${w === 'ok' ? 'bg-emerald-100 text-emerald-800' : LEVELS[w].cls}`}>
                      {w === 'ok' ? '✓ OK' : `${r.issues.filter((i) => i.level !== 'info').length || r.issues.length} ${LEVELS[w].label.toLowerCase()}`}
                    </span>
                  </button>
                  {r.issues.length > 0 && !isOpen && (
                    <p className="-mt-2 truncate px-4 pb-3 text-xs text-slate-600">{r.issues.filter((i) => i.level !== 'info').map((i) => i.text).join(' · ')}</p>
                  )}
                  {isOpen && <Details r={r} />}
                </div>
              );
            })}
          </div>
        </>
      )}
    </>
  );
}

function Details({ r }) {
  const s = r.seo;
  const field = (label, value, max) => (
    <div className="grid gap-1 sm:grid-cols-[120px_1fr]">
      <span className="text-xs font-bold uppercase tracking-wider text-slate-500">{label}</span>
      <span className="break-words text-sm">
        {value || <em className="text-red-600">missing</em>}
        {value && max && <span className={`ml-2 text-[11px] ${value.length > max ? 'text-amber-700' : 'text-slate-400'}`}>{value.length} chars</span>}
      </span>
    </div>
  );
  return (
    <div className="space-y-4 border-t border-slate-100 bg-cream/40 p-4">
      <a href={r.url} target="_blank" rel="noreferrer" className="text-sm font-semibold text-gold-dark underline">Open page ↗</a>
      {r.issues.length > 0 && (
        <ul className="space-y-1.5">
          {r.issues.map((i, k) => (
            <li key={k} className="flex gap-2 text-sm">
              <span className={`h-fit shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase ${LEVELS[i.level]?.cls}`}>{LEVELS[i.level]?.label}</span>
              <span className="break-words">{i.text}</span>
            </li>
          ))}
        </ul>
      )}
      {s && (
        <div className="space-y-2 rounded-xl bg-white p-3">
          {field('Title', s.title, 70)}
          {field('Description', s.description, 170)}
          {field('Keywords', s.keywords)}
          {field('Canonical', s.canonical)}
          {field('H1', s.h1.join(' | '))}
          {field('Share image', s.og_image)}
          <p className="text-xs text-slate-500">
            {s.words} words · loaded in {r.time} s
            {r.baseline ? ` · baseline saved ${r.baseline.saved_at}` : ' · no baseline saved yet'}
          </p>
        </div>
      )}
      {r.images?.broken?.length > 0 && (
        <div>
          <p className="label">Broken images</p>
          <ul className="space-y-1 text-xs">
            {r.images.broken.map((b) => (
              <li key={b.url} className="break-all">
                <span className="mr-2 font-bold text-red-700">{b.status || 'no answer'}{b.status === 200 ? ' (not an image)' : ''}</span>
                <a href={b.url} target="_blank" rel="noreferrer" className="underline">{b.url}</a>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
