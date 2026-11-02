import { useEffect, useState } from 'react';
import { api } from '../lib/api';

const PROBLEMS = [
  ['bio', 'Bio missing', 'bg-orange-100 text-orange-800'],
  ['banner', 'Banner / hero missing', 'bg-sky-100 text-sky-800'],
  ['image', 'Image missing', 'bg-red-100 text-red-700'],
  ['seo', 'SEO issue', 'bg-violet-100 text-violet-700'],
];
const BANDS = [
  ['excellent', 'Excellent', 'bg-emerald-100 text-emerald-800', 'bg-emerald-500'],
  ['good', 'Good', 'bg-sky-100 text-sky-800', 'bg-sky-500'],
  ['fair', 'Fair', 'bg-amber-100 text-amber-800', 'bg-amber-500'],
  ['poor', 'Poor', 'bg-red-100 text-red-700', 'bg-red-500'],
];
const bandOf = (k) => BANDS.find((b) => b[0] === k) || BANDS[3];
const STARINDIA_ADMIN = 'https://starindia.fsia.in/admin/';

/** Section of the starindia admin profile editor that fixes each kind of problem. */
const FIX_TAB = { seo: 'seo', banner: 'hero', bio: 'identity', image: 'gallery' };
const TAB_LABEL = { seo: 'SEO', hero: 'Hero', identity: 'Identity', gallery: 'Photo Gallery' };

/** Link that opens this profile, at the right section, in the starindia admin (see starindia-admin/admin-enhancements.js). */
function starindiaUrl(r, tab = 'identity') {
  const slug = (r.link || '').replace(/\/+$/, '').split('/').pop() || '';
  const p = new URLSearchParams({ 'fsia-open': String(r.id), q: slug, n: r.name || '', year: (r.date || '').slice(0, 4), tab });
  return `${STARINDIA_ADMIN}#${p.toString()}`;
}

function FixLink({ r }) {
  return (
    <a href={starindiaUrl(r)} target="_blank" rel="noreferrer" title={`Opens profile #${r.id} in the starindia admin`}
      className="mt-1.5 inline-block rounded-lg bg-navy px-2.5 py-1 text-[11px] font-bold text-white hover:bg-navy/80">
      Fix in starindia admin ↗
    </a>
  );
}

function ScoreCell({ r }) {
  const [, label, cls, bar] = bandOf(r.band);
  if (r.score === null) {
    return <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-500" title={r.unchecked || ''}>Not checked</span>;
  }
  if (r.score === undefined) return <span className="text-slate-400">—</span>;
  return (
    <details className="group">
      <summary className="cursor-pointer list-none">
        <span className={`inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-bold ${cls}`}>
          {r.score}<span className="font-semibold opacity-80">{label}</span>
        </span>
        <span className="ml-1 text-[11px] text-gold-dark underline group-open:hidden">why?</span>
      </summary>
      <ul className="mt-2 w-56 space-y-1.5">
        {r.score_parts.map((p) => (
          <li key={p.key} title={p.how}>
            <div className="flex justify-between text-[11px]"><span>{p.label}</span><span className="font-semibold">{p.got}/{p.max}</span></div>
            <div className="h-1.5 rounded-full bg-slate-100"><div className={`h-1.5 rounded-full ${bar}`} style={{ width: `${(p.got / p.max) * 100}%` }} /></div>
            {p.got < p.max && <p className="text-[10px] leading-snug text-slate-400">{p.how}</p>}
          </li>
        ))}
      </ul>
    </details>
  );
}

const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const LIVE_LIMIT = 200;

/**
 * Profiles straight from the database, filtered by category / year / month and by
 * what is missing (bio, banner, photos on the server, SEO fields). The matching
 * pages can then be opened live by the Site Check.
 */
export default function ProfileData({ onCheckLive, disabled }) {
  const [f, setF] = useState({ category: '', year: '', month: '', status: 'all', verification: '', q: '', sort: '', changed_days: '' });
  const [problems, setProblems] = useState([]);
  const [bands, setBands] = useState([]);
  const [match, setMatch] = useState('any');
  const [page, setPage] = useState(1);
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [reload, setReload] = useState(0);
  const [busy, setBusy] = useState([]);          // profile ids being fixed / checked
  const [bulk, setBulk] = useState(null);        // {done, total} while auto fixing many
  const [batches, setBatches] = useState([]);    // recent auto fixes, for Undo
  const [note, setNote] = useState('');

  const params = { ...f, problem: problems.join(','), match, band: bands.join(',') };
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
  }, [key, page, reload]);

  const loadBatches = () => api.profileFixes().then((d) => setBatches(d.batches || [])).catch(() => {});
  useEffect(() => { loadBatches(); }, []);

  /** Puts freshly read rows in place of the old ones (same position in the list). */
  function replaceRows(rows) {
    const byId = new Map((rows || []).map((r) => [r.id, r]));
    setData((d) => (d ? { ...d, rows: d.rows.map((r) => byId.get(r.id) || r) } : d));
  }
  const isBusy = (id) => busy.includes(id);
  async function withBusy(ids, fn) {
    setBusy((b) => [...b, ...ids]);
    try { await fn(); } catch (e) { setError(e.message); } finally { setBusy((b) => b.filter((x) => !ids.includes(x))); }
  }

  const recheck = (ids) => withBusy(ids, async () => {
    const d = await api.profileRecheck(ids);
    replaceRows(d.rows);
    setNote(`Checked ${ids.length === 1 ? 'the profile' : `${ids.length} profiles`} again.`);
  });

  const fixOne = (r) => withBusy([r.id], async () => {
    const d = await api.profileFix([r.id]);
    replaceRows(d.rows);
    setNote(d.fields ? `${r.name}: ${d.fields} SEO field${d.fields === 1 ? '' : 's'} filled in, then checked again.` : `${r.name}: nothing to fix.`);
    loadBatches();
  });

  async function fixAll() {
    const ids = data?.fix_ids || [];
    if (!ids.length) return;
    if (!window.confirm(`Fill in the missing meta title, description and keywords for ${ids.length.toLocaleString()} profile${ids.length === 1 ? '' : 's'} in this list (and shorten ones that are too long)?\n\nBio, photos, banners and profile links are not touched. You can undo this afterwards.`)) return;
    setError('');
    let profiles = 0;
    let fields = 0;
    setBulk({ done: 0, total: ids.length });
    try {
      let batch;
      for (let i = 0; i < ids.length; i += 250) {
        const d = await api.profileFix(ids.slice(i, i + 250), batch);
        batch = d.batch;
        profiles += d.profiles;
        fields += d.fields;
        setBulk({ done: Math.min(i + 250, ids.length), total: ids.length });
      }
      setNote(`Auto fix done: ${fields.toLocaleString()} SEO fields filled in on ${profiles.toLocaleString()} profiles. The list below is checked again.`);
    } catch (e) {
      setError(`Stopped: ${e.message} (${profiles.toLocaleString()} profiles were fixed before that; they can be undone below).`);
    } finally {
      setBulk(null);
      loadBatches();
      setReload((n) => n + 1);
    }
  }

  async function undo(b) {
    if (!window.confirm(`Undo this auto fix (${b.profiles.toLocaleString()} profile${b.profiles === 1 ? '' : 's'})? The old SEO text is put back.`)) return;
    try {
      const d = await api.profileUndo(b.batch);
      replaceRows(d.rows);
      setNote(`Undone: ${d.restored.toLocaleString()} field${d.restored === 1 ? '' : 's'} put back${d.kept ? `, ${d.kept} left as they were changed again after the fix` : ''}.`);
      loadBatches();
    } catch (e) { setError(e.message); }
  }

  const set = (k) => (e) => setF((p) => ({ ...p, [k]: e.target.value }));
  const toggle = (k) => setProblems((p) => (p.includes(k) ? p.filter((x) => x !== k) : [...p, k]));
  const toggleBand = (k) => setBands((p) => (p.includes(k) ? p.filter((x) => x !== k) : [...p, k]));
  const pages = data ? Math.max(1, Math.ceil(data.total / data.page_size)) : 1;

  async function downloadCsv() {
    try {
      const all = await api.siteCheckProfiles({ ...params, all: 1 });
      const esc = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;
      const lines = [['ID', 'Name', 'Category', 'Date', 'Verification', 'Score', 'Band', 'Last changed', 'Changed by', 'Link', 'Photos', 'Bio', 'Banner', 'Image', 'SEO'].map(esc).join(',')];
      all.rows.forEach((r) => lines.push([
        r.id, r.name, r.category, r.date, r.verified ? 'Verified Member' : 'Pending / Guest', r.score, r.band, r.last_changed, r.changed_by, r.link, r.photos,
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
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
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
          <span className="label">Verification</span>
          <select className="input" value={f.verification} onChange={set('verification')}>
            <option value="">All Members{data?.verification_counts ? ` (${(data.verification_counts.verified + data.verification_counts.pending).toLocaleString()})` : ''}</option>
            <option value="verified">Verified Member{data?.verification_counts ? ` (${data.verification_counts.verified.toLocaleString()})` : ''}</option>
            <option value="pending">Pending / Guest{data?.verification_counts ? ` (${data.verification_counts.pending.toLocaleString()})` : ''}</option>
          </select>
        </label>
        <label>
          <span className="label">Changed</span>
          <select className="input" value={f.changed_days} onChange={set('changed_days')}>
            <option value="">Any time</option>
            <option value="1">In the last 24 hours</option>
            <option value="7">In the last 7 days</option>
            <option value="30">In the last 30 days</option>
          </select>
        </label>
        <label>
          <span className="label">Sort</span>
          <select className="input" value={f.sort} onChange={set('sort')}>
            <option value="">Newest joined first</option>
            <option value="changed">Recently changed first</option>
            <option value="score">Lowest score first</option>
            <option value="score_desc">Highest score first</option>
          </select>
        </label>
        <label>
          <span className="label">Name / ID</span>
          <input className="input" placeholder="e.g. azra or 86294" value={f.q} onChange={set('q')} />
        </label>
      </div>

      {data?.bands && (
        <div className="mt-4 rounded-xl bg-cream/60 p-3">
          <div className="flex flex-wrap items-center gap-2">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Quality score:</span>
            {data.avg_score !== null && <span className="text-sm">average <b>{data.avg_score}</b>/100</span>}
            {BANDS.map(([k, label, cls]) => {
              const on = bands.includes(k);
              return (
                <button key={k} type="button" onClick={() => toggleBand(k)} aria-pressed={on}
                  className={`rounded-full border px-3 py-1.5 text-xs font-bold transition ${on ? `${cls} border-transparent ring-2 ring-gold` : 'border-slate-200 bg-white text-slate-600 hover:border-gold'}`}>
                  {on ? '✓ ' : ''}{label} {k === 'poor' ? `< ${data.band_min.fair}` : `${data.band_min[k]}+`} ({data.bands[k].toLocaleString()})
                </button>
              );
            })}
            {data.bands.unchecked > 0 && (
              <button type="button" onClick={() => toggleBand('unchecked')} aria-pressed={bands.includes('unchecked')}
                className={`rounded-full border px-3 py-1.5 text-xs font-bold transition ${bands.includes('unchecked') ? 'border-transparent bg-slate-200 text-slate-700 ring-2 ring-gold' : 'border-slate-200 bg-white text-slate-600 hover:border-gold'}`}>
                {bands.includes('unchecked') ? '✓ ' : ''}Not checked ({data.bands.unchecked.toLocaleString()})
              </button>
            )}
            {bands.length > 0 && <button type="button" className="text-xs font-semibold text-gold-dark underline" onClick={() => setBands([])}>Clear</button>}
          </div>
          {data.scanned > 0 && (
            <div className="mt-2 flex h-2 overflow-hidden rounded-full bg-white" aria-hidden="true">
              {BANDS.map(([k, , , bar]) => <div key={k} className={bar} style={{ width: `${(data.bands[k] / data.scanned) * 100}%` }} />)}
            </div>
          )}
          <p className="mt-2 text-[11px] text-slate-500">
            Photos 30 · Biography 25 · Banners (or hero image) 20 · SEO 25. Profiles on www.fsia.in are checked from the database and server files;
            profiles edited in the starindia admin are checked from that admin&apos;s own data, with its limits (title 60, description 160).
            {data.starindia?.profiles > 0 && ` ${data.starindia.checked.toLocaleString()} of ${data.starindia.profiles.toLocaleString()} starindia profiles in this list could be read.`}
          </p>
        </div>
      )}

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
      {note && !error && (
        <p className="mt-3 flex items-start justify-between gap-3 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">
          <span>✓ {note}</span>
          <button type="button" className="text-xs font-semibold underline" onClick={() => setNote('')}>Close</button>
        </p>
      )}
      {bulk && (
        <div className="mt-3 rounded-xl bg-cream/60 p-3 text-sm">
          <p className="font-semibold">⚡ Auto fixing… {bulk.done.toLocaleString()} of {bulk.total.toLocaleString()} profiles</p>
          <div className="mt-2 h-2 rounded-full bg-white"><div className="h-2 rounded-full bg-gold" style={{ width: `${(bulk.done / bulk.total) * 100}%` }} /></div>
        </div>
      )}
      {batches.length > 0 && (
        <details className="mt-3 rounded-xl border border-slate-100 p-3 text-sm">
          <summary className="cursor-pointer font-semibold">Auto fixes made ({batches.length}) <span className="font-normal text-slate-500">· undo any of them</span></summary>
          <ul className="mt-2 divide-y divide-slate-100">
            {batches.map((b) => (
              <li key={b.batch} className="flex flex-wrap items-center justify-between gap-2 py-2 text-xs">
                <span>
                  <b>{b.at.slice(0, 16)}</b> · {b.profiles.toLocaleString()} profile{b.profiles === 1 ? '' : 's'}, {b.fields.toLocaleString()} SEO field{b.fields === 1 ? '' : 's'}
                  {b.by && <span className="text-slate-500"> · by {b.by}</span>}
                </span>
                {b.undone
                  ? <span className="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-500">Undone</span>
                  : <button type="button" className="btn-ghost !py-1 text-xs" onClick={() => undo(b)}>↶ Undo</button>}
              </li>
            ))}
          </ul>
        </details>
      )}
      {data && !data.change_log && (f.sort === 'changed' || f.changed_days) && (
        <p className="mt-3 rounded-xl bg-amber-50 p-3 text-xs text-amber-800">
          Change dates come from <b>Tools → Profile Changes</b>. Turn it on there; from then on every profile edit is recorded with its date and who made it.
        </p>
      )}

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
            <button type="button" className="btn-ghost !py-1.5" disabled={!!bulk || !data.rows.length || data.rows.some((r) => isBusy(r.id))}
              onClick={() => recheck(data.rows.map((r) => r.id))}
              title="Read the profiles on this page from the database again">
              ↻ Check this page again
            </button>
            <button type="button" className="btn-gold !py-1.5" disabled={!!bulk || !data.fix_ids?.length} onClick={fixAll}
              title="Fill in missing meta title, description and keywords (and shorten ones that are too long) for every profile in this list">
              ⚡ Auto fix SEO ({(data.fix_ids?.length || 0).toLocaleString()})
            </button>
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
          <table className="w-full min-w-[860px] text-left text-sm">
            <thead className="bg-cream/60 text-[11px] uppercase tracking-wider text-slate-500">
              <tr><th className="p-3">Profile</th><th className="p-3">Score</th><th className="p-3">Category</th><th className="p-3">Joined</th><th className="p-3">Last changed</th><th className="p-3">What is missing</th></tr>
            </thead>
            <tbody>
              {data.rows.map((r) => (
                <tr key={r.id} className="border-t border-slate-100 align-top">
                  <td className="p-3">
                    {r.link ? <a href={r.link} target="_blank" rel="noreferrer" className="font-semibold underline decoration-gold/50 hover:text-gold-dark">{r.name}</a> : <span className="font-semibold">{r.name}</span>}
                    <span className="block text-[11px] text-slate-400">#{r.id} · {r.photos} photo{r.photos === 1 ? '' : 's'}{r.status === 3 ? ' · title holder' : ''}</span>
                    {r.verified !== null && r.verified !== undefined && (
                      <span className={`mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold ${r.verified ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                        {r.verified ? `✓ Verified Member${r.verified_on ? ` · ${r.verified_on}` : ''}` : 'Pending / Guest'}
                      </span>
                    )}
                    {r.suspended && <span className="ml-1 mt-1 inline-block rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700">Suspended</span>}
                    <span className="mt-1 block text-[10px] text-slate-400">Checked from: {r.source === 'starindia' ? 'starindia admin data' : 'www database'}</span>
                    <FixLink r={r} />
                    <button type="button" disabled={isBusy(r.id) || !!bulk} onClick={() => recheck([r.id])}
                      className="mt-1.5 block text-[11px] font-semibold text-gold-dark underline disabled:opacity-50">
                      {isBusy(r.id) ? 'Checking…' : '↻ Check again'}
                    </button>
                  </td>
                  <td className="p-3"><ScoreCell r={r} /></td>
                  <td className="p-3 text-slate-600">{r.category}</td>
                  <td className="whitespace-nowrap p-3 text-slate-600">{r.date}</td>
                  <td className="p-3 text-xs text-slate-600">
                    {r.last_changed ? (
                      <>
                        <span className="whitespace-nowrap font-semibold text-navy">{r.last_changed.slice(0, 16)}</span>
                        {r.changed_by && <span className="mt-0.5 block max-w-[14rem] break-words text-[11px] text-slate-500">by {r.changed_by}</span>}
                      </>
                    ) : <span className="text-slate-400">—</span>}
                  </td>
                  <td className="p-3">
                    {r.unchecked && <p className="mb-1 rounded bg-slate-100 px-2 py-1 text-[11px] text-slate-600">Bio, hero and SEO not checked: {r.unchecked}</p>}
                    {Object.keys(r.problems).length === 0 && !r.unchecked ? (
                      <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800">✓ Complete</span>
                    ) : (
                      <ul className="space-y-1">
                        {PROBLEMS.filter(([k]) => r.problems[k]).map(([k, label, cls]) => (
                          <li key={k} className="text-xs">
                            <span className={`mr-1.5 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase ${cls}`}>{label.split(' ')[0]}</span>
                            {r.problems[k].join(', ')}
                            {(() => {
                              const tab = k === 'image' && r.problems[k].some((t) => /logo/i.test(t)) ? 'identity' : FIX_TAB[k];
                              return <a href={starindiaUrl(r, tab)} target="_blank" rel="noreferrer" className="ml-1.5 whitespace-nowrap font-semibold text-gold-dark underline" title={`Open the ${TAB_LABEL[tab]} section of this profile in the starindia admin`}>Fix ↗</a>;
                            })()}
                            {k === 'seo' && r.auto_fix?.length > 0 && (
                              <button type="button" disabled={isBusy(r.id) || !!bulk} onClick={() => fixOne(r)}
                                title={`Fills in: ${r.auto_fix.map((x) => ({ meta_title: 'meta title', meta_desc: 'meta description', meta_keyword: 'meta keywords' }[x] || x)).join(', ')}`}
                                className="ml-1.5 whitespace-nowrap rounded bg-gold/20 px-1.5 py-0.5 text-[11px] font-bold text-gold-dark hover:bg-gold/40 disabled:opacity-50">
                                {isBusy(r.id) ? 'Fixing…' : '⚡ Auto fix'}
                              </button>
                            )}
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
