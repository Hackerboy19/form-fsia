import { useEffect, useState } from 'react';
import { api } from '../lib/api';
import { PageHeader } from '../components/Layout';
import Modal from '../components/Modal';

/* Database column -> what an editor calls it. Unknown columns show their own name. */
const FIELD_LABELS = {
  first_name: 'First name', last_name: 'Last name', url: 'Profile link', status: 'Status', regtype: 'Category',
  verification: 'Verified', suspended: 'Suspended', meta_title: 'SEO title', meta_desc: 'SEO description', meta_keyword: 'SEO keywords',
  fullbio: 'Biography', fullbio_2: 'Success story', fullbio_3: 'Journey', shortbio: 'Short bio', newbio: 'Bio', bio: 'Bio', comment: 'Bio',
  image: 'Photos', setdefault: 'Main photo', portfolio: 'Portfolio', workimage: 'Work photos',
  nimage1: 'Mobile banner', simage1: 'Mobile banner', image1: 'Mobile banner', nimage2: 'Desktop banner', simage2: 'Desktop banner', image2: 'Desktop banner',
  ptitle: 'Title', ptitle1: 'State title', ptitle2: 'National title', email: 'Email', mobile: 'Mobile', password: 'Password',
  city: 'City', state: 'State', cityfsia: 'City', statefsia: 'State', profession: 'Profession', h1: 'H1 heading',
};
const label = (f) => FIELD_LABELS[f] || f;
const ACTIONS = {
  update: ['Edited', 'bg-sky-100 text-sky-800'],
  create: ['New profile', 'bg-emerald-100 text-emerald-800'],
  delete: ['Deleted', 'bg-red-100 text-red-700'],
};

/* Pasted into the site's config.php, after the database connection. */
const SNIPPET = `/* FSIA CMS change log: tells the database which page and login made a change. */
if (isset($connect) && $connect instanceof mysqli) {
    try {
        $fsiaWho = [];
        foreach (['admin', 'admin_name', 'adminname', 'admin_id', 'username', 'user_name', 'userid', 'user_id'] as $fsiaKey) {
            if (!empty($_SESSION[$fsiaKey]) && is_scalar($_SESSION[$fsiaKey])) $fsiaWho[] = $fsiaKey . '=' . $_SESSION[$fsiaKey];
        }
        $fsiaActor = ($_SERVER['HTTP_HOST'] ?? 'cron') . ($_SERVER['SCRIPT_NAME'] ?? '') . ($fsiaWho ? ' · ' . implode(', ', $fsiaWho) : '');
        mysqli_query($connect, "SET @fsia_actor = '" . mysqli_real_escape_string($connect, substr($fsiaActor, 0, 250)) . "'");
    } catch (Throwable $fsiaErr) {
        // never stop the page because of the change log
    }
}`;

const ago = (s) => {
  const ms = Date.now() - new Date(s.replace(' ', 'T')).getTime();
  const m = Math.round(ms / 60000);
  if (Number.isNaN(m)) return '';
  if (m < 1) return 'just now';
  if (m < 60) return `${m} min ago`;
  const h = Math.round(m / 60);
  if (h < 24) return `${h} h ago`;
  const d = Math.round(h / 24);
  return d === 1 ? 'yesterday' : `${d} days ago`;
};

/** Every change to a profile: when, what (before -> after) and from which page or login. */
export default function ProfileChanges() {
  const [status, setStatus] = useState(null);
  const [filters, setFilters] = useState({ q: '', days: '7', type: '', field: '' });
  const [page, setPage] = useState(1);
  const [list, setList] = useState(null);
  const [open, setOpen] = useState({});
  const [confirm, setConfirm] = useState(null); // 'on' | 'off'
  const [busy, setBusy] = useState(false);
  const [msg, setMsg] = useState('');
  const [error, setError] = useState('');
  const [copied, setCopied] = useState(false);

  const loadStatus = () => api.changelogStatus().then(setStatus).catch((e) => setError(e.message));
  useEffect(() => { loadStatus(); }, []);

  const key = JSON.stringify(filters);
  useEffect(() => { setPage(1); }, [key]);
  useEffect(() => {
    let alive = true;
    const t = setTimeout(() => {
      api.changelogList({ ...filters, page }).then((d) => alive && setList(d)).catch((e) => alive && setError(e.message));
    }, filters.q ? 400 : 0);
    return () => { alive = false; clearTimeout(t); };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key, page, status?.on]);

  async function toggle() {
    setBusy(true); setError(''); setMsg('');
    try {
      if (confirm === 'on') {
        const r = await api.changelogInstall();
        setMsg(`✓ Change log is on. ${r.tracked_columns} profile fields are now recorded on every change.`);
      } else {
        await api.changelogUninstall();
        setMsg('Change log is off. Changes already recorded stay in the list.');
      }
      await loadStatus();
    } catch (e) { setError(e.message); }
    finally { setBusy(false); setConfirm(null); }
  }

  function copySnippet() {
    navigator.clipboard?.writeText(SNIPPET).then(() => { setCopied(true); setTimeout(() => setCopied(false), 2000); }).catch(() => {});
  }

  const set = (k) => (e) => setFilters((p) => ({ ...p, [k]: e.target.value }));
  const pages = list ? Math.max(1, Math.ceil(list.total / list.page_size)) : 1;

  return (
    <>
      <PageHeader
        title="Profile Changes"
        description="Every change to a profile: when it happened, what changed (before → after) and from which page or login."
      />
      {error && <p className="card mb-4 p-4 text-sm text-red-600">{error}</p>}
      {msg && <p className="card mb-4 border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{msg}</p>}

      {status && (
        <div className="grid gap-3 lg:grid-cols-2">
          <div className={`card p-5 ${status.on ? 'border-emerald-200' : 'border-amber-200 bg-amber-50/50'}`}>
            <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Step 1 · Recording</p>
            {status.on ? (
              <>
                <p className="mt-1 text-lg font-bold text-emerald-700">● Recording changes</p>
                <p className="mt-1 text-sm text-slate-600">
                  {status.count.toLocaleString()} change{status.count === 1 ? '' : 's'} recorded{status.last ? `, latest ${ago(status.last)}` : ''}.
                  Every edit is caught: the old admin panel, profile owners, starindia, phpMyAdmin.
                </p>
                <button type="button" className="btn-ghost mt-3 !py-1.5" onClick={() => setConfirm('off')}>Turn off</button>
              </>
            ) : (
              <>
                <p className="mt-1 text-lg font-bold text-amber-800">○ Not recording yet</p>
                <p className="mt-1 text-sm text-slate-600">
                  Turn it on to keep a record of every profile change from now on. Changes made before that cannot be recovered.
                </p>
                <button type="button" className="btn-gold mt-3" onClick={() => setConfirm('on')}>Turn on change log</button>
              </>
            )}
          </div>

          <div className="card p-5">
            <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Step 2 · Who made the change</p>
            <p className="mt-1 text-sm text-slate-600">
              The database knows <b>what</b> changed and <b>when</b>. To also see <b>which page and login</b> made it
              (old admin panel, the profile owner, starindia), paste this once into the website&apos;s <code className="rounded bg-cream px-1">config.php</code>, right after the database connection.
            </p>
            {status.on && status.count > 0 && (
              <p className={`mt-2 text-xs font-semibold ${status.with_actor ? 'text-emerald-700' : 'text-amber-700'}`}>
                {status.with_actor ? `✓ Working: ${status.with_actor.toLocaleString()} changes show who made them.` : 'Not added yet: changes show “Unknown page”.'}
              </p>
            )}
            <details className="mt-3">
              <summary className="cursor-pointer text-sm font-semibold text-gold-dark">Show the code for config.php</summary>
              <div className="relative mt-2">
                <pre className="max-h-64 overflow-auto rounded-xl bg-navy p-3 text-[11px] leading-relaxed text-slate-100">{SNIPPET}</pre>
                <button type="button" className="absolute right-2 top-2 rounded-lg bg-white/10 px-2 py-1 text-[11px] font-semibold text-white hover:bg-white/20" onClick={copySnippet}>
                  {copied ? 'Copied' : 'Copy'}
                </button>
              </div>
              <p className="mt-2 text-xs text-slate-500">It only reads the login from the session and never stops a page if something goes wrong. Keep a copy of config.php before editing it.</p>
            </details>
          </div>
        </div>
      )}

      <div className="card mt-4 p-5">
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <label>
            <span className="label">Profile</span>
            <input className="input" placeholder="Name, link or ID (e.g. 105512)" value={filters.q} onChange={set('q')} />
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
          <label>
            <span className="label">Type</span>
            <select className="input" value={filters.type} onChange={set('type')}>
              <option value="">All changes</option>
              <option value="update">Edited</option>
              <option value="create">New profiles</option>
              <option value="delete">Deleted</option>
            </select>
          </label>
          <label>
            <span className="label">Field</span>
            <select className="input" value={filters.field} onChange={set('field')}>
              <option value="">Any field</option>
              {['meta_title', 'meta_desc', 'meta_keyword', 'fullbio', 'shortbio', 'image', 'setdefault', 'nimage1', 'nimage2', 'url', 'status', 'first_name', 'password'].map((f) => (
                <option key={f} value={f}>{label(f)}</option>
              ))}
            </select>
          </label>
        </div>

        <p className="mt-4 text-sm text-slate-600">{list ? `${list.total.toLocaleString()} change${list.total === 1 ? '' : 's'}` : 'Loading…'}</p>

        <ul className="mt-2 space-y-2">
          {list?.rows.map((r) => {
            const [actLabel, actCls] = ACTIONS[r.action] || [r.action, 'bg-slate-100 text-slate-600'];
            const isOpen = !!open[r.id];
            const entries = Object.entries(r.changes || {});
            return (
              <li key={r.id} className="rounded-xl border border-slate-100">
                <div className="flex flex-wrap items-start gap-3 p-3">
                  <div className="w-28 shrink-0 text-xs">
                    <p className="font-semibold text-navy">{ago(r.at)}</p>
                    <p className="text-slate-500">{r.at.slice(0, 16)}</p>
                  </div>
                  <div className="min-w-0 flex-1">
                    <p className="text-sm">
                      <span className={`mr-2 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase ${actCls}`}>{actLabel}</span>
                      {r.link ? <a href={r.link} target="_blank" rel="noreferrer" className="font-semibold underline decoration-gold/50">{r.name}</a> : <b>{r.name}</b>}
                      <span className="ml-1 text-xs text-slate-400">#{r.profile_id}</span>
                    </p>
                    <p className="mt-0.5 break-words text-xs text-slate-500">by {r.who}</p>
                    {r.fields.length > 0 && (
                      <p className="mt-1.5 flex flex-wrap gap-1">
                        {[...new Set(r.fields.map(label))].map((f) => <span key={f} className="rounded-full bg-cream px-2 py-0.5 text-[11px] font-semibold text-navy">{f}</span>)}
                      </p>
                    )}
                  </div>
                  {entries.length > 0 && (
                    <button type="button" className="text-xs font-semibold text-gold-dark underline" onClick={() => setOpen((o) => ({ ...o, [r.id]: !o[r.id] }))} aria-expanded={isOpen}>
                      {isOpen ? 'Hide details' : 'Show before → after'}
                    </button>
                  )}
                </div>
                {isOpen && (
                  <div className="overflow-x-auto border-t border-slate-100 bg-cream/40">
                    <table className="w-full min-w-[520px] text-left text-xs">
                      <thead className="text-[10px] uppercase tracking-wider text-slate-500">
                        <tr><th className="p-2 pl-3">Field</th>{r.action === 'update' ? <><th className="p-2">Before</th><th className="p-2">After</th></> : <th className="p-2">Value</th>}</tr>
                      </thead>
                      <tbody>
                        {entries.map(([f, v]) => (
                          <tr key={f} className="border-t border-slate-100 align-top">
                            <td className="whitespace-nowrap p-2 pl-3 font-semibold">{label(f)}</td>
                            {Array.isArray(v) ? (
                              <>
                                <td className="max-w-xs break-words p-2 text-red-700/80 line-through decoration-red-300">{v[0] ?? <em className="text-slate-400 no-underline">empty</em>}</td>
                                <td className="max-w-xs break-words p-2 text-emerald-800">{v[1] ?? <em className="text-slate-400">empty</em>}</td>
                              </>
                            ) : <td className="break-words p-2">{String(v ?? '')}</td>}
                          </tr>
                        ))}
                      </tbody>
                    </table>
                    <p className="px-3 pb-2 text-[10px] text-slate-400">Long texts show their first 300 characters. Email, mobile and password changes are noted without their values.</p>
                  </div>
                )}
              </li>
            );
          })}
          {list && list.rows.length === 0 && (
            <li className="rounded-xl bg-cream/60 p-6 text-center text-sm text-slate-500">
              {status?.on ? 'No changes in this period.' : 'Turn on the change log (Step 1) to start recording profile changes.'}
            </li>
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

      <Modal open={!!confirm} title={confirm === 'on' ? 'Turn on the change log?' : 'Turn off the change log?'} onClose={() => !busy && setConfirm(null)} width="max-w-lg"
        footer={(
          <>
            <button type="button" className="btn-ghost" onClick={() => setConfirm(null)} disabled={busy}>Cancel</button>
            <button type="button" className={confirm === 'on' ? 'btn-gold' : 'btn-danger'} onClick={toggle} disabled={busy}>
              {busy ? 'Working…' : confirm === 'on' ? 'Turn on' : 'Turn off'}
            </button>
          </>
        )}>
        {confirm === 'on' ? (
          <ul className="space-y-2 text-sm text-slate-700">
            <li>📝 A small recorder is added to the database. From now on, each time a profile is edited, created or deleted, a line is written to the change log.</li>
            <li>🧪 It is tested first on a temporary copy of the profiles table, so it cannot break saving profiles.</li>
            <li>🔒 No profile is changed. Email, mobile and passwords are never written into the log.</li>
            <li>↩ You can turn it off here at any time.</li>
          </ul>
        ) : (
          <p className="text-sm text-slate-700">New changes will no longer be recorded. The changes already in the list stay there.</p>
        )}
      </Modal>
    </>
  );
}
