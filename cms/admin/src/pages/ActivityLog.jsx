import { useEffect, useState } from 'react';
import { api } from '../lib/api';
import { PageHeader } from '../components/Layout';

/* Endpoint -> what an editor calls that part of the admin. */
const AREAS = {
  'auth.php': 'Sign-in', 'sections.php': 'Page text', 'seo.php': 'SEO', 'teams.php': 'Team', 'news.php': 'News',
  'upload.php': 'Image upload', 'sitecheck.php': 'Site Check', 'changelog.php': 'Profile Changes',
  'cleanup.php': 'Server Cleanup', 'users.php': 'Users & Roles', 'audit.php': 'Activity Log',
};
const VERBS = { POST: 'Added / ran', PUT: 'Saved', DELETE: 'Deleted', GET: 'Opened' };

function result(status) {
  if (status === 401 || status === 403) return ['Refused', 'bg-red-100 text-red-700'];
  if (status >= 400) return ['Failed', 'bg-amber-100 text-amber-800'];
  return ['Done', 'bg-emerald-100 text-emerald-800'];
}

function what(r) {
  const area = AREAS[r.endpoint] || r.endpoint;
  if (r.endpoint === 'auth.php') return r.action === 'sign-in' ? 'Signed in' : 'Sign-in failed';
  const verb = r.action ? r.action.replace(/_/g, ' ') : (VERBS[r.method] || r.method);
  return `${area}: ${verb}`;
}

/** Everything done through this admin: who, when, what, and whether it was allowed. */
export default function ActivityLog() {
  const [filters, setFilters] = useState({ q: '', user: '', endpoint: '', result: '', days: '7' });
  const [page, setPage] = useState(1);
  const [list, setList] = useState(null);
  const [open, setOpen] = useState({});
  const [error, setError] = useState('');

  const key = JSON.stringify(filters);
  useEffect(() => { setPage(1); }, [key]);
  useEffect(() => {
    let alive = true;
    const t = setTimeout(() => {
      api.auditList({ ...filters, page }).then((d) => alive && setList(d)).catch((e) => alive && setError(e.message));
    }, filters.q ? 400 : 0);
    return () => { alive = false; clearTimeout(t); };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key, page]);

  const set = (k) => (e) => setFilters((p) => ({ ...p, [k]: e.target.value }));
  const pages = list ? Math.max(1, Math.ceil(list.total / list.page_size)) : 1;

  return (
    <>
      <PageHeader title="Activity Log" description="Every change made in this admin, every sign-in and every refused attempt. Passwords and tokens are never stored." />
      {error && <p className="card mb-4 p-4 text-sm text-red-600">{error}</p>}

      <div className="card p-5">
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
          <label className="lg:col-span-2">
            <span className="label">Search</span>
            <input className="input" placeholder="Page, title, file name…" value={filters.q} onChange={set('q')} />
          </label>
          <label>
            <span className="label">User</span>
            <select className="input" value={filters.user} onChange={set('user')}>
              <option value="">Everyone</option>
              {list?.users.map((u) => <option key={u} value={u}>{u}</option>)}
            </select>
          </label>
          <label>
            <span className="label">Area</span>
            <select className="input" value={filters.endpoint} onChange={set('endpoint')}>
              <option value="">All areas</option>
              {list?.endpoints.map((e) => <option key={e} value={e}>{AREAS[e] || e}</option>)}
            </select>
          </label>
          <label>
            <span className="label">When</span>
            <select className="input" value={filters.days} onChange={set('days')}>
              <option value="1">Last 24 hours</option>
              <option value="7">Last 7 days</option>
              <option value="30">Last 30 days</option>
              <option value="">Any time</option>
            </select>
          </label>
        </div>
        <div className="mt-3 flex flex-wrap gap-2 text-sm">
          {[['', 'All'], ['ok', 'Done'], ['failed', 'Failed or refused'], ['denied', 'Refused only']].map(([v, l]) => (
            <button key={v} type="button" onClick={() => setFilters((p) => ({ ...p, result: v }))}
              className={`rounded-full px-3 py-1 font-semibold ${filters.result === v ? 'bg-navy text-white' : 'bg-cream text-navy'}`}>{l}</button>
          ))}
        </div>

        <p className="mt-4 text-sm text-slate-600">{list ? `${list.total.toLocaleString()} entr${list.total === 1 ? 'y' : 'ies'}` : 'Loading…'}</p>

        <ul className="mt-2 space-y-2">
          {list?.rows.map((r) => {
            const [rl, rc] = result(r.status);
            return (
              <li key={r.id} className="rounded-xl border border-slate-100">
                <div className="flex flex-wrap items-start gap-3 p-3">
                  <p className="w-32 shrink-0 text-xs text-slate-500">{r.created_at.slice(0, 16)}</p>
                  <div className="min-w-0 flex-1">
                    <p className="text-sm">
                      <span className={`mr-2 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase ${rc}`}>{rl}</span>
                      <b>{what(r)}</b>
                      {r.target && <span className="ml-1 break-all text-xs text-slate-500">{readable(r.target)}</span>}
                    </p>
                    <p className="mt-0.5 text-xs text-slate-500">
                      by <b>{r.username || 'not signed in'}</b>{r.role && ` (${r.role})`} · {r.ip}
                    </p>
                  </div>
                  {r.summary && (
                    <button type="button" className="text-xs font-semibold text-gold-dark underline" aria-expanded={!!open[r.id]}
                      onClick={() => setOpen((o) => ({ ...o, [r.id]: !o[r.id] }))}>
                      {open[r.id] ? 'Hide details' : 'Details'}
                    </button>
                  )}
                </div>
                {open[r.id] && (
                  <pre className="max-h-72 overflow-auto whitespace-pre-wrap break-words border-t border-slate-100 bg-cream/40 p-3 text-[11px]">{pretty(r.summary)}</pre>
                )}
              </li>
            );
          })}
          {list && list.rows.length === 0 && (
            <li className="rounded-xl bg-cream/60 p-6 text-center text-sm text-slate-500">Nothing recorded for these filters yet.</li>
          )}
        </ul>

        {pages > 1 && (
          <div className="mt-3 flex items-center justify-center gap-3 text-sm">
            <button type="button" className="btn-ghost !py-1.5" disabled={page <= 1} onClick={() => setPage(page - 1)}>← Newer</button>
            <span>Page {page} of {pages}</span>
            <button type="button" className="btn-ghost !py-1.5" disabled={page >= pages} onClick={() => setPage(page + 1)}>Older →</button>
          </div>
        )}
      </div>
    </>
  );
}

function readable(t) {
  try { return decodeURIComponent(t.replace(/\+/g, ' ')); } catch { return t; }
}

function pretty(s) {
  try { return JSON.stringify(JSON.parse(s), null, 2); } catch { return s; }
}
