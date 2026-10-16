import { useEffect, useState } from 'react';
import { api, assetUrl } from '../lib/api';
import { useAuth } from '../lib/auth';
import { useToast } from '../lib/toast';
import { PageHeader } from '../components/Layout';
import { Field, SelectField } from '../components/Field';
import ImageUpload from '../components/ImageUpload';
import Modal from '../components/Modal';

const PHASE = {
  draft: ['Draft', 'bg-slate-100 text-slate-600'],
  scheduled: ['Scheduled', 'bg-sky-100 text-sky-800'],
  live: ['Live on the website', 'bg-emerald-100 text-emerald-800'],
  completed: ['Completed', 'bg-slate-100 text-slate-500'],
  cancelled: ['Cancelled', 'bg-red-100 text-red-700'],
};
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const day = (s) => { const d = String(s || '').slice(0, 10).split('-'); return d.length === 3 ? `${+d[2]} ${MONTHS[+d[1] - 1]} ${d[0]}` : ''; };
/** Same wording as /cms/promo.js: "26 Oct 2026", "26–28 Oct 2026", "30 Oct – 2 Nov 2026". */
const range = (s, e) => {
  const a = day(s), b = day(e);
  if (!b || a === b) return a;
  const [x, y] = [a.split(' '), b.split(' ')];
  if (x[2] === y[2] && x[1] === y[1]) return `${x[0]}–${b}`;
  return x[2] === y[2] ? `${x[0]} ${x[1]} – ${b}` : `${a} – ${b}`;
};
const local = (s) => (s ? String(s).replace(' ', 'T').slice(0, 16) : '');
const BLANK = {
  title: '', status: 'draft', starts_at: '', ends_at: '', venue: '', city: '', summary: '', description: '',
  banner_desktop: '', banner_mobile: '', guests: [], cta_label: 'Register now', cta_url: '', priority: 0,
  promo_start: '', promo_end: '', targets: ['all'], excludes: [],
};

/** The bar visitors see (same look as /cms/promo.js). */
function PromoPreview({ e }) {
  const when = range(e.starts_at, e.ends_at);
  const line = [when, [e.venue, e.city].filter(Boolean).join(', '), e.summary].filter(Boolean).join(' · ');
  const img = e.banner_desktop || e.banner_mobile;
  return (
    <div className="relative flex items-center gap-3 rounded-xl bg-navy px-3 py-2.5 pr-10 text-[#F6F2E8] shadow-lg">
      {img && <img src={assetUrl(img)} alt="" className="h-12 w-auto max-w-[140px] rounded-lg object-cover" />}
      <div className="min-w-0 flex-1">
        <p className="truncate font-bold text-gold">{e.title || 'Event title'}</p>
        {line && <p className="truncate text-xs text-slate-300">{line}</p>}
      </div>
      <span className="shrink-0 rounded-full bg-gold px-3.5 py-2 text-xs font-bold text-navy">{e.cta_label || 'Register now'}</span>
      <span className="absolute right-2.5 top-1/2 -translate-y-1/2 text-lg text-slate-400">×</span>
    </div>
  );
}

function RulesField({ label, value, onChange, groups, hint, error }) {
  const paths = value.filter((r) => r.startsWith('/'));
  const toggle = (g) => onChange(value.includes(g) ? value.filter((x) => x !== g) : [...value, g]);
  return (
    <div>
      <p className="label">{label}</p>
      <div className="flex flex-wrap gap-2">
        {Object.entries(groups).map(([k, l]) => (
          <label key={k} className={`cursor-pointer rounded-full border px-3 py-1.5 text-xs font-semibold ${value.includes(k) ? 'border-gold bg-gold/15 text-navy' : 'border-slate-200 text-slate-600'}`}>
            <input type="checkbox" className="sr-only" checked={value.includes(k)} onChange={() => toggle(k)} />{value.includes(k) ? '✓ ' : ''}{l}
          </label>
        ))}
      </div>
      <textarea className="input mt-2 font-mono text-xs" rows={2} placeholder={'/miss-assam.php\n/news*'} value={paths.join('\n')}
        onChange={(ev) => onChange([...value.filter((r) => !r.startsWith('/')), ...ev.target.value.split(/\n/).map((x) => x.trim()).filter(Boolean)])} />
      <p className="mt-1 text-[11px] text-slate-500">{hint}</p>
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  );
}

function EventForm({ initial, groups, onClose, onSaved }) {
  const notify = useToast();
  const [f, setF] = useState(initial);
  const [errors, setErrors] = useState({});
  const [busy, setBusy] = useState(false);
  const set = (k) => (v) => setF((p) => ({ ...p, [k]: v?.target ? v.target.value : v }));

  async function save(status) {
    setBusy(true); setErrors({});
    const body = { ...f, status: status || f.status, priority: Number(f.priority) || 0 };
    try {
      const saved = f.id ? await api.updateEvent(f.id, body) : await api.createEvent(body);
      notify(saved.phase === 'live' ? 'Saved. It is live on the website now.' : `Saved (${PHASE[saved.phase]?.[0] || saved.phase}).`);
      onSaved(saved);
    } catch (e) {
      setErrors(e.fields || {}); notify(e.message, 'error');
    } finally { setBusy(false); }
  }

  const guests = f.guests || [];
  const setGuest = (i, k, v) => set('guests')(guests.map((g, j) => (j === i ? { ...g, [k]: v } : g)));
  return (
    <Modal open title={f.id ? `Edit event: ${initial.title}` : 'New event'} onClose={onClose} width="max-w-5xl"
      footer={<>
        <span className="mr-auto self-center text-xs text-slate-500">Times are India time (IST).</span>
        <button type="button" className="btn-ghost" onClick={onClose}>Close</button>
        <button type="button" className="btn-ghost" disabled={busy} onClick={() => save('draft')}>Save as draft</button>
        <button type="button" className="btn-primary" disabled={busy} onClick={() => save('published')}>{busy ? 'Saving…' : 'Publish'}</button>
      </>}>
      <div className="space-y-6">
        <section>
          <p className="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Preview: what visitors see at the bottom of the page</p>
          <PromoPreview e={f} />
        </section>

        <section className="grid gap-4 md:grid-cols-2">
          <Field label="Event title *" value={f.title} onChange={set('title')} max={150} error={errors.title} />
          <Field label="Short line (optional)" value={f.summary} onChange={set('summary')} max={300} error={errors.summary} placeholder="e.g. Auditions open in 28 states" />
          <Field label="Starts *" type="datetime-local" value={local(f.starts_at)} onChange={set('starts_at')} error={errors.starts_at} />
          <Field label="Ends *" type="datetime-local" value={local(f.ends_at)} onChange={set('ends_at')} error={errors.ends_at} />
          <Field label="Venue" value={f.venue} onChange={set('venue')} max={200} error={errors.venue} />
          <Field label="City" value={f.city} onChange={set('city')} max={100} error={errors.city} />
          <Field label="Button text" value={f.cta_label} onChange={set('cta_label')} max={40} error={errors.cta_label} />
          <Field label="Button link (registration form) *" value={f.cta_url} onChange={set('cta_url')} max={512} error={errors.cta_url} placeholder="/quickapply or https://…" />
        </section>

        <section className="grid gap-4 md:grid-cols-2">
          <ImageUpload label="Banner (desktop)" value={f.banner_desktop} onChange={set('banner_desktop')} hint={errors.banner_desktop || 'Wide image; shown small in the bar, large on event pages.'} />
          <ImageUpload label="Banner (mobile)" value={f.banner_mobile} onChange={set('banner_mobile')} hint={errors.banner_mobile || 'Optional. Square or tall.'} />
        </section>

        <section className="rounded-xl bg-cream/60 p-4">
          <p className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Promotion on the website</p>
          <div className="grid gap-4 md:grid-cols-3">
            <Field label="Show from" type="datetime-local" value={local(f.promo_start)} onChange={set('promo_start')} error={errors.promo_start} hint="Empty = from now" />
            <Field label="Show until" type="datetime-local" value={local(f.promo_end)} onChange={set('promo_end')} error={errors.promo_end} hint="Empty = until the event ends" />
            <Field label="Priority (0–100)" type="number" min={0} value={String(f.priority ?? 0)} onChange={set('priority')} error={errors.priority} hint="When events overlap, the higher one shows" />
          </div>
          <div className="mt-4 grid gap-4 md:grid-cols-2">
            <RulesField label="Show on" value={f.targets} onChange={set('targets')} groups={groups} error={errors.targets}
              hint="Pick groups and/or add paths, one per line. * matches anything: /news* = all news pages." />
            <RulesField label="But never on" value={f.excludes} onChange={set('excludes')} groups={groups} error={errors.excludes}
              hint="Pages listed here never show it, even if picked above." />
          </div>
        </section>

        <section>
          <div className="mb-2 flex items-center justify-between">
            <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Guests & jury</p>
            <button type="button" className="btn-ghost !py-1 text-xs" onClick={() => set('guests')([...guests, { name: '', role: '', photo: '' }])}>+ Add guest</button>
          </div>
          {guests.length === 0 && <p className="text-sm text-slate-500">No guests added.</p>}
          <div className="space-y-2">
            {guests.map((g, i) => (
              <div key={i} className="grid gap-2 rounded-xl border border-slate-100 p-2 sm:grid-cols-[1fr_1fr_1.4fr_auto]">
                <input className="input" placeholder="Name" value={g.name} maxLength={100} onChange={(e) => setGuest(i, 'name', e.target.value)} />
                <input className="input" placeholder="Role, e.g. Jury" value={g.role} maxLength={120} onChange={(e) => setGuest(i, 'role', e.target.value)} />
                <input className="input" placeholder="Photo link (optional)" value={g.photo} onChange={(e) => setGuest(i, 'photo', e.target.value)} />
                <button type="button" className="btn-ghost !px-3 text-red-600" onClick={() => set('guests')(guests.filter((_, j) => j !== i))} aria-label="Remove guest">×</button>
              </div>
            ))}
          </div>
          {Object.keys(errors).filter((k) => k.startsWith('guests')).map((k) => <p key={k} className="mt-1 text-xs text-red-600">{errors[k]}</p>)}
        </section>

        <Field label="About the event (optional)" type="textarea" rows={4} value={f.description} onChange={set('description')} error={errors.description}
          hint="Simple HTML is kept (<p>, <strong>, lists, links); scripts are removed." />
      </div>
    </Modal>
  );
}

/** Events and the website-wide promotion bar built from them. */
export default function Events() {
  const { can } = useAuth();
  const notify = useToast();
  const editable = can('events.manage');
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [editing, setEditing] = useState(null);
  const [testPath, setTestPath] = useState('/');
  const [match, setMatch] = useState(null);

  const load = () => api.listEvents().then(setData).catch((e) => setError(e.message));
  useEffect(() => { load(); }, []);

  async function checkPath() {
    try { setMatch(await api.matchPromotion(testPath)); } catch (e) { setError(e.message); }
  }
  async function duplicate(ev) {
    try { const d = await api.duplicateEvent(ev.id); notify('Copied as a draft'); await load(); setEditing(d); } catch (e) { notify(e.message, 'error'); }
  }
  async function remove(ev) {
    if (!window.confirm(`Delete "${ev.title}"? This cannot be undone. To stop it showing, Cancel it instead.`)) return;
    try { await api.deleteEvent(ev.id); notify('Deleted'); load(); } catch (e) { notify(e.message, 'error'); }
  }
  async function cancel(ev) {
    if (!window.confirm(`Cancel "${ev.title}"? It is taken off the website at once.`)) return;
    try { await api.updateEvent(ev.id, { ...ev, status: 'cancelled' }); notify('Cancelled and removed from the website'); load(); } catch (e) { notify(e.message, 'error'); }
  }

  const live = data?.events.filter((e) => e.phase === 'live') || [];
  return (
    <>
      <PageHeader title="Events & Promotions" description="Create events once; the promotion bar on the website follows their dates, pages and priority. One bar per page, never stacked."
        actions={editable && <button type="button" className="btn-gold" onClick={() => setEditing({ ...BLANK })}>+ New event</button>} />
      {error && <p className="card mb-4 p-4 text-sm text-red-600">{error}</p>}

      <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div className="card overflow-hidden">
          {!data && <div className="h-40 animate-pulse bg-cream" />}
          {data && data.events.length === 0 && <p className="p-6 text-center text-sm text-slate-500">No events yet. Click “New event”.</p>}
          {data && data.events.length > 0 && (
            <ul className="divide-y divide-slate-100">
              {data.events.map((ev) => {
                const [label, cls] = PHASE[ev.phase] || [ev.phase, ''];
                return (
                  <li key={ev.id} className="flex flex-wrap items-center gap-3 p-4">
                    {ev.banner_desktop || ev.banner_mobile
                      ? <img src={assetUrl(ev.banner_desktop || ev.banner_mobile)} alt="" className="h-12 w-20 rounded-lg object-cover" />
                      : <div className="grid h-12 w-20 place-items-center rounded-lg bg-cream text-xs text-slate-400">no banner</div>}
                    <div className="min-w-[12rem] flex-1">
                      <p className="font-semibold">{ev.title}</p>
                      <p className="text-xs text-slate-500">{range(ev.starts_at, ev.ends_at)}{ev.city ? ` · ${ev.city}` : ''} · priority {ev.priority}</p>
                      <p className="text-[11px] text-slate-400">Shows on: {ev.targets.map((t) => data.groups[t] || t).join(', ') || '—'}{ev.excludes.length ? ` · not on ${ev.excludes.map((t) => data.groups[t] || t).join(', ')}` : ''}</p>
                    </div>
                    <span className={`rounded-full px-2.5 py-1 text-xs font-bold ${cls}`}>{label}</span>
                    {editable && (
                      <div className="flex flex-wrap gap-1">
                        <button type="button" className="btn-ghost !px-2.5 !py-1 text-xs" onClick={() => setEditing(ev)}>Edit</button>
                        <button type="button" className="btn-ghost !px-2.5 !py-1 text-xs" onClick={() => duplicate(ev)}>Duplicate</button>
                        {ev.status === 'published' && ev.phase !== 'completed' && <button type="button" className="btn-ghost !px-2.5 !py-1 text-xs text-red-600" onClick={() => cancel(ev)}>Cancel</button>}
                        <button type="button" className="btn-ghost !px-2.5 !py-1 text-xs text-red-600" onClick={() => remove(ev)} aria-label={`Delete ${ev.title}`}>Delete</button>
                      </div>
                    )}
                  </li>
                );
              })}
            </ul>
          )}
        </div>

        <aside className="space-y-4">
          <div className="card p-4">
            <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Live right now</p>
            {live.length ? live.map((e) => <p key={e.id} className="mt-1 text-sm font-semibold text-emerald-700">● {e.title}</p>) : <p className="mt-1 text-sm text-slate-500">Nothing is promoted at the moment.</p>}
            {data && <p className="mt-2 text-[11px] text-slate-400">India time now: {data.now.slice(0, 16)}</p>}
          </div>
          <div className="card p-4">
            <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Which bar shows on a page?</p>
            <div className="mt-2 flex gap-2">
              <input className="input" value={testPath} onChange={(e) => setTestPath(e.target.value)} aria-label="Page path" placeholder="/miss-assam.php" />
              <button type="button" className="btn-ghost" onClick={checkPath}>Check</button>
            </div>
            {match && <p className="mt-2 text-sm">{match.promotion ? <>On <code>{match.path}</code>: <b>{match.promotion.title}</b></> : <>Nothing shows on <code>{match.path}</code>.</>}</p>}
          </div>
          <div className="card p-4 text-xs text-slate-600">
            <p className="font-bold text-navy">Turn the bar on for the website</p>
            <p className="mt-1">Add this line once to the shared footer (e.g. <code>footer1806.php</code>), before <code>&lt;/body&gt;</code>:</p>
            <pre className="mt-2 overflow-x-auto rounded-lg bg-navy p-2 text-[11px] text-slate-100">{'<script src="/cms/promo.js" defer></script>'}</pre>
          </div>
        </aside>
      </div>

      {editing && data && (
        <EventForm initial={{ ...BLANK, ...editing }} groups={data.groups} onClose={() => setEditing(null)}
          onSaved={() => { setEditing(null); load(); }} />
      )}
    </>
  );
}
