import { useEffect, useRef, useState } from 'react';
import { api, assetUrl, SITE } from '../lib/api';
import { useAuth } from '../lib/auth';
import { useToast } from '../lib/toast';
import { PageHeader } from '../components/Layout';
import { Field, SelectField } from '../components/Field';
import ImageUpload from '../components/ImageUpload';
import Modal from '../components/Modal';
import { promoLib } from '../lib/promoBar';

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
  theme: 'gold', badge: '', whatsapp: '', whatsapp_text: '', popup: false, solo: false,
};

/* Bar colours: same keys as THEMES in /cms/promo.js and EVENT_THEMES in backend/lib/events.php. */
const THEMES = [
  { key: 'gold', label: 'Gold & Navy', bg: 'linear-gradient(100deg,#0A0F1C,#111A2E 55%,#1C1A12)', dot: '#D4AF37' },
  { key: 'royal', label: 'Royal Red', bg: 'linear-gradient(100deg,#2A0509,#5B0E16 55%,#3A0A0F)', dot: '#D4AF37' },
  { key: 'rose', label: 'Pink Pageant', bg: 'linear-gradient(100deg,#3B0A2A,#7A1450 55%,#4A0D33)', dot: '#F06BA8' },
  { key: 'emerald', label: 'Emerald', bg: 'linear-gradient(100deg,#03241B,#0B3D2E 55%,#1A2E12)', dot: '#D4AF37' },
  { key: 'ivory', label: 'Ivory (light)', bg: 'linear-gradient(100deg,#FFFDF7,#FBF4E4 60%,#F4E7C8)', dot: '#111A2E' },
];
const BADGES = ['Early Bird ₹500 OFF', 'Last 3 Days', 'Few Seats Left', 'Free Entry', 'New', 'Registrations Open'];

/** "10:00 AM – 6:00 PM IST" -> ["10:00", "18:00"] */
function calTimes(t) {
  const m = [...String(t || '').matchAll(/(\d{1,2})(?::(\d{2}))?\s*([AaPp][Mm])?/g)].filter((x) => x[2] || x[3]);
  const to24 = (x) => {
    let h = +x[1];
    if (x[3]) h = (h % 12) + (/p/i.test(x[3]) ? 12 : 0);
    return `${String(Math.min(h, 23)).padStart(2, '0')}:${x[2] || '00'}`;
  };
  return [m[0] ? to24(m[0]) : '10:00', m[1] ? to24(m[1]) : '18:00'];
}
const esc = (x) => String(x).replace(/[<>&]/g, (ch) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[ch]));
/** A calendar event (Homepage → Event Calendar) as Events & Promotions fields. */
function fromCalendar(c) {
  const [t1, t2] = calTimes(c.time);
  const desc = [c.description, c.eligibility && `Eligibility: ${c.eligibility}`].filter(Boolean).map((x) => `<p>${esc(x)}</p>`).join('');
  return {
    title: String(c.title || '').slice(0, 150), summary: String(c.category || '').slice(0, 300),
    starts_at: `${c.date}T${t1}`, ends_at: `${c.endDate || c.date}T${t2}`,
    venue: String(c.venue || '').slice(0, 200), city: String(c.city || '').slice(0, 100),
    cta_label: String(c.ctaText || 'Register now').slice(0, 40), cta_url: c.ctaUrl || '',
    banner_desktop: c.image || '', description: desc,
  };
}
const pct = (a, b) => (b ? `${((a / b) * 100).toFixed(1)}%` : '–');
const num = (n) => Number(n || 0).toLocaleString('en-IN');
/** What /cms/promo.js gets for an event being edited. */
const promoData = (e) => ({
  id: e.id || 0, title: e.title, summary: e.summary, starts_at: e.starts_at ? String(e.starts_at).replace('T', ' ') : '',
  ends_at: e.ends_at ? String(e.ends_at).replace('T', ' ') : '', venue: e.venue, city: e.city,
  banner_desktop: e.banner_desktop, banner_mobile: e.banner_mobile, cta_label: e.cta_label, cta_url: e.cta_url || '#',
  guests: barGuests(e.guests).map((g) => ({ name: g.name, role: g.role, photo: g.photo })),
  theme: e.theme, badge: e.badge, whatsapp: e.whatsapp, whatsapp_text: e.whatsapp_text,
});

const BAR_GUESTS = 2; // same as EVENT_BAR_GUESTS in backend/lib/events.php
const barGuests = (guests) => (guests || []).filter((g) => g.featured && String(g.name || '').trim()).slice(0, BAR_GUESTS);
const GUEST_ROLES = ['Celebrity Chief Guest', 'Chief Guest', 'Guest of Honour', 'Special Guest', 'Jury', 'Host'];

/** The bar visitors see: built by /cms/promo.js itself. `mobile` = phone card. */
function PromoPreview({ e, mobile = false }) {
  const holder = useRef(null);
  const data = promoData(e);
  const [narrow, setNarrow] = useState(false);
  useEffect(() => {
    const ro = new ResizeObserver(([x]) => setNarrow(x.contentRect.width < 1300));
    if (holder.current) ro.observe(holder.current);
    return () => ro.disconnect();
  }, []);
  const key = JSON.stringify(data) + mobile + narrow;
  useEffect(() => {
    const box = holder.current;
    if (!box) return;
    box.replaceChildren(promoLib().build(data, { mobile, narrow, preview: true, asset: assetUrl }));
  }, [key]); // eslint-disable-line react-hooks/exhaustive-deps
  return <div ref={holder} />;
}

/** The popup visitors see once a day, built by /cms/promo.js. */
function PopupPreview({ e, mobile = false }) {
  const holder = useRef(null);
  const data = promoData(e);
  const key = JSON.stringify(data) + mobile;
  useEffect(() => {
    const box = holder.current;
    if (!box) return;
    box.replaceChildren(promoLib().buildPopup(data, { mobile, preview: true, asset: assetUrl }));
  }, [key]); // eslint-disable-line react-hooks/exhaustive-deps
  return <div ref={holder} />;
}

/** Pick an event from Homepage → Event Calendar to fill the form. */
function CalendarImport({ onPick }) {
  const [items, setItems] = useState(null);
  const [open, setOpen] = useState(false);
  const [err, setErr] = useState('');
  useEffect(() => {
    if (!open || items) return;
    api.getSections('index')
      .then((s) => setItems(((s.calendar_events?.content?.items) || []).filter((c) => c && c.title && c.date)))
      .catch((e) => setErr(e.message));
  }, [open, items]);
  const today = new Date(Date.now() + 5.5 * 3600e3).toISOString().slice(0, 10);
  const list = (items || []).slice().sort((a, b) => String(a.date).localeCompare(String(b.date)));
  return (
    <section className="rounded-xl border border-dashed border-gold/70 bg-gold/5 p-3">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div>
          <p className="text-sm font-bold text-navy">📅 Import from Event Calendar</p>
          <p className="text-[11px] text-slate-500">Pick an event from Homepage → Event Calendar: title, dates, venue, city, button and image are filled in for you.</p>
        </div>
        <button type="button" className="btn-ghost !py-1 text-xs" onClick={() => setOpen((o) => !o)}>{open ? 'Hide list' : 'Choose event'}</button>
      </div>
      {open && (
        <div className="mt-3 max-h-64 space-y-1 overflow-y-auto">
          {err && <p className="text-xs text-red-600">{err}</p>}
          {!items && !err && <p className="text-xs text-slate-500">Loading calendar…</p>}
          {items && list.length === 0 && <p className="text-xs text-slate-500">The Event Calendar has no events yet.</p>}
          {list.map((c) => {
            const past = (c.endDate || c.date) < today;
            return (
              <button key={c.id || c.title + c.date} type="button" onClick={() => { onPick(fromCalendar(c)); setOpen(false); }}
                className={`flex w-full items-center gap-3 rounded-lg border border-slate-100 bg-white p-2 text-left hover:border-gold ${past ? 'opacity-50' : ''}`}>
                {c.image ? <img src={assetUrl(c.image)} alt="" className="h-9 w-14 rounded object-cover" /> : <span className="grid h-9 w-14 place-items-center rounded bg-cream text-xs">📅</span>}
                <span className="min-w-0 flex-1">
                  <span className="block truncate text-sm font-semibold">{c.title}</span>
                  <span className="block text-[11px] text-slate-500">{range(c.date, c.endDate || c.date)}{c.city ? ` · ${c.city}` : ''}{past ? ' · over' : ''}</span>
                </span>
                <span className="text-xs font-semibold text-gold-dark">Use →</span>
              </button>
            );
          })}
        </div>
      )}
    </section>
  );
}

/** Views and clicks for one event: totals, last 7 days, today and the last 30 days. */
function StatsModal({ ev, onClose, onReset }) {
  const s = ev.stats || { views: 0, clicks: 0, wa_clicks: 0, today: {}, week: {}, days: [] };
  const max = Math.max(1, ...s.days.map((d) => d.views));
  const tile = (label, v, sub) => (
    <div className="rounded-xl bg-cream/70 p-3"><p className="text-[11px] font-bold uppercase tracking-wider text-slate-500">{label}</p><p className="mt-1 text-2xl font-extrabold text-navy">{v}</p>{sub && <p className="text-[11px] text-slate-500">{sub}</p>}</div>
  );
  return (
    <Modal open title={`Views & clicks: ${ev.title}`} onClose={onClose} width="max-w-2xl"
      footer={<>
        <button type="button" className="btn-ghost mr-auto text-red-600" onClick={onReset}>Reset to 0</button>
        <button type="button" className="btn-primary" onClick={onClose}>Close</button>
      </>}>
      <div className="space-y-4">
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
          {tile('Views', num(s.views), `Today ${num(s.today?.views)} · 7 days ${num(s.week?.views)}`)}
          {tile('Register clicks', num(s.clicks), `Today ${num(s.today?.clicks)} · 7 days ${num(s.week?.clicks)}`)}
          {tile('Click rate', pct(s.clicks, s.views), 'clicks ÷ views')}
          {tile('WhatsApp', num(s.wa_clicks), `Today ${num(s.today?.wa_clicks)}`)}
        </div>
        <div>
          <p className="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Last 30 days</p>
          {s.days.length === 0 ? <p className="text-sm text-slate-500">No views yet. Numbers start once the event is live on the website.</p> : (
            <div className="space-y-1">
              {s.days.slice().reverse().map((d) => (
                <div key={d.day} className="grid grid-cols-[84px_1fr_130px] items-center gap-2 text-xs">
                  <span className="text-slate-500">{day(d.day)}</span>
                  <span className="relative h-4 overflow-hidden rounded bg-slate-100">
                    <span className="absolute inset-y-0 left-0 rounded bg-navy/80" style={{ width: `${(d.views / max) * 100}%` }} />
                    <span className="absolute inset-y-0 left-0 rounded bg-gold" style={{ width: `${(d.clicks / max) * 100}%` }} />
                  </span>
                  <span className="text-right tabular-nums text-slate-600">{num(d.views)} 👁 · {num(d.clicks)} 👆{d.wa_clicks ? ` · ${d.wa_clicks} 💬` : ''}</span>
                </div>
              ))}
              <p className="pt-1 text-[11px] text-slate-500"><span className="mr-1 inline-block h-2 w-3 rounded bg-navy/80" />views <span className="ml-3 mr-1 inline-block h-2 w-3 rounded bg-gold" />Register clicks</p>
            </div>
          )}
        </div>
        <p className="text-[11px] text-slate-500">A view = one visitor seeing the bar or popup (counted once a day per visitor). India time.</p>
      </div>
    </Modal>
  );
}

/* Same page rules as backend/lib/events.php (events_norm_path, events_rule_matches). */
const MAIN_PATHS = ['/about', '/about.php', '/contact', '/contact.php', '/our-teams.php', '/quickapply', '/news-coverage',
  '/special-news-coverage', '/faq.php', '/fashion-designer.php', '/makeup-artist.php', '/channel-partner.php', '/socialmediaprofile.php'];
function normPath(p) {
  let x = String(p || '').trim();
  try { if (/^https?:\/\//i.test(x)) x = new URL(x).pathname; } catch { /* keep as typed */ }
  x = '/' + x.split(/[?#]/)[0].toLowerCase().replace(/^\/+|\/+$/g, '');
  return ['/', '/index.php', '/index.html'].includes(x) ? '/' : x;
}
function ruleMatches(rule, path) {
  if (rule === 'all') return true;
  if (rule === 'home') return path === '/';
  if (rule === 'pageants') return /^\/(miss|mrs)-[a-z0-9-]+\.php$/.test(path);
  if (rule === 'profiles') return path !== '/' && !path.slice(1).includes('/') && !path.endsWith('.php') && !MAIN_PATHS.includes(path);
  if (!rule.startsWith('/')) return false;
  const star = rule.endsWith('*');
  const base = normPath(rule.replace(/\*+$/, ''));
  const re = new RegExp('^' + (base + (star ? '*' : '')).replace(/[.+?^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*') + '$');
  return re.test(path);
}
/** Example pages for what is picked in "Show on" / "But never on". */
function samplePages(targets, excludes) {
  const out = [];
  const add = (path, label, kind) => { if (!out.some((o) => o.path === path)) out.push({ path, label, kind }); };
  const fromRules = (rules, kind) => rules.forEach((r) => {
    if (r === 'all') { add('/', 'Homepage', kind); add('/miss-assam.php', 'State page', kind); add('/about', 'About', kind); }
    else if (r === 'home') add('/', 'Homepage', kind);
    else if (r === 'pageants') add('/miss-assam.php', 'State page (Miss Assam)', kind);
    else if (r === 'profiles') add('', 'Profile page (paste a link)', kind);
    else if (r.startsWith('/')) { const p = r.replace(/\*+$/, '') || '/'; add(p, r, kind); }
  });
  fromRules(targets, 'show');
  fromRules(excludes, 'hide');
  return out;
}

const VIEW = { desktop: { w: 1280, h: 760 }, mobile: { w: 390, h: 760 } };

/** The real website page with this event's bar on it, before anything is published. */
function PagePreview({ e }) {
  const samples = samplePages(e.targets || [], e.excludes || []);
  const [path, setPath] = useState(samples[0]?.path ?? '/');
  const [typed, setTyped] = useState(samples[0]?.path ?? '/');
  const [device, setDevice] = useState('desktop');
  const [box, setBox] = useState(0);
  const [nonce, setNonce] = useState(0);
  const [now, setNow] = useState(null);
  const holder = useRef(null);

  useEffect(() => {
    const ro = new ResizeObserver(([x]) => setBox(x.contentRect.width));
    if (holder.current) ro.observe(holder.current);
    return () => ro.disconnect();
  }, []);
  // What the website shows on this page right now (published events only).
  useEffect(() => {
    if (!path) { setNow(null); return undefined; }
    let live = true;
    const t = setTimeout(() => api.matchPromotion(path).then((m) => live && setNow(m)).catch(() => live && setNow(null)), 300);
    return () => { live = false; clearTimeout(t); };
  }, [path]);

  const go = (p) => { setTyped(p); setPath(p.trim() ? normPath(p) : ''); };
  const np = path ? normPath(path) : '';
  const shown = np !== '' && (e.targets || []).some((r) => ruleMatches(r, np));
  const hidden = np !== '' && (e.excludes || []).some((r) => ruleMatches(r, np));
  const willShow = shown && !hidden;
  const other = now?.promotion && now.promotion.id !== e.id ? now.promotion : null;

  const { w, h } = VIEW[device];
  const scale = box ? Math.min(1, box / w) : 1;
  const left = box > w ? (box - w) / 2 : 0;
  const onLoad = (ev) => {
    try {
      const d = ev.target.contentDocument;
      if (!d) return;
      // Hide the bar the live page may already show, and keep clicks inside the preview.
      const st = d.createElement('style');
      st.textContent = '#fsia-promo{display:none!important}';
      d.head.appendChild(st);
      d.addEventListener('click', (x) => { if (x.target.closest('a')) x.preventDefault(); }, true);
    } catch { /* other address: show the page as it is */ }
  };

  return (
    <section className="card overflow-hidden">
      <div className="space-y-3 border-b border-gold-light/70 p-4">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <div>
            <p className="text-sm font-bold">Page preview</p>
            <p className="text-[11px] text-slate-500">The real website page with this bar on it. Nothing is published until you press Publish.</p>
          </div>
          <div className="flex items-center gap-1">
            {Object.keys(VIEW).map((d) => (
              <button key={d} type="button" onClick={() => setDevice(d)}
                className={`rounded-lg px-2.5 py-1 text-xs font-semibold ${device === d ? 'bg-navy text-white' : 'bg-slate-100 text-slate-600'}`}>
                {d === 'desktop' ? '🖥 Desktop' : '📱 Mobile'}
              </button>
            ))}
            <button type="button" className="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600" onClick={() => setNonce((n) => n + 1)} title="Reload the page">↻</button>
          </div>
        </div>
        {samples.length > 0 && (
          <div className="flex flex-wrap gap-1.5">
            {samples.map((s) => (
              <button key={s.kind + s.path + s.label} type="button" onClick={() => go(s.path)}
                className={`rounded-full border px-2.5 py-1 text-[11px] font-semibold ${s.path === path && s.path !== '' ? 'border-navy bg-navy text-white' : s.kind === 'hide' ? 'border-red-200 text-red-700' : 'border-slate-200 text-slate-600'}`}>
                {s.kind === 'hide' ? '⊘ ' : ''}{s.label}
              </button>
            ))}
          </div>
        )}
        <form className="flex gap-2" onSubmit={(x) => { x.preventDefault(); go(typed); }}>
          <input className="input font-mono text-xs" aria-label="Page to preview" placeholder="/miss-assam.php or a full www.fsia.in link"
            value={typed} onChange={(x) => setTyped(x.target.value)} />
          <button type="submit" className="btn-ghost !py-1 text-xs">Show</button>
        </form>
        {np !== '' && (
          <div className="space-y-1 text-xs">
            {willShow && <p className="font-semibold text-emerald-700">✓ The bar shows on {np} (while the event is promoted).</p>}
            {hidden && <p className="font-semibold text-red-700">⊘ Not on {np}: it is in "But never on".</p>}
            {!shown && !hidden && <p className="font-semibold text-amber-700">Not on {np}: this page is not picked in "Show on".</p>}
            {willShow && other && <p className="text-amber-700">Right now this page shows "{other.title}". If both are live they take turns, higher priority first (unless one is set to show alone).</p>}
          </div>
        )}
      </div>
      <div ref={holder} className="relative overflow-hidden bg-slate-100" style={{ height: np === '' ? 120 : h * scale }}>
        {np === '' ? (
          <p className="p-6 text-center text-sm text-slate-500">Paste a page link above, e.g. a profile page, to see it here.</p>
        ) : (
          <div style={{ width: w, height: h, transform: `scale(${scale})`, transformOrigin: 'top left', marginLeft: left, position: 'relative' }}>
            <iframe key={`${np}-${device}-${nonce}`} src={SITE + np} title="Website page preview" onLoad={onLoad}
              style={{ width: w, height: h, border: 0, background: '#fff' }} />
            {willShow && (
              <div style={{ position: 'absolute', left: 0, right: 0, bottom: 0 }}>
                <PromoPreview e={e} mobile={device === 'mobile'} />
              </div>
            )}
          </div>
        )}
      </div>
    </section>
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
        <CalendarImport onPick={(c) => { setF((p) => ({ ...p, ...c })); notify('Filled from the Event Calendar. Check and press Publish.'); }} />
        <section>
          <p className="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Preview: what visitors see at the bottom of the page</p>
          <div className="space-y-3">
            <div><p className="mb-1 text-[11px] font-semibold text-slate-500">🖥 Computer</p><div className="overflow-hidden rounded-xl"><PromoPreview e={f} /></div></div>
            <div className="max-w-[390px]"><p className="mb-1 text-[11px] font-semibold text-slate-500">📱 Phone</p><div className="rounded-2xl bg-slate-200/70 pt-3"><PromoPreview e={f} mobile /></div></div>
          </div>
          {f.popup && (
            <div className="mt-4">
              <p className="mb-1 text-[11px] font-semibold text-slate-500">🪟 Popup (first visit of the day, then the bar)</p>
              <div className="grid items-start gap-3 md:grid-cols-[minmax(0,1fr)_340px]"><PopupPreview e={f} /><PopupPreview e={f} mobile /></div>
            </div>
          )}
        </section>

        <section className="rounded-xl bg-cream/60 p-4">
          <p className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Look & extras</p>
          <p className="label">Colour theme</p>
          <div className="flex flex-wrap gap-2">
            {THEMES.map((t) => (
              <button key={t.key} type="button" onClick={() => set('theme')(t.key)} aria-pressed={f.theme === t.key}
                className={`flex items-center gap-2 rounded-xl border-2 bg-white p-1 pr-3 text-xs font-semibold ${f.theme === t.key ? 'border-navy' : 'border-transparent hover:border-slate-200'}`}>
                <span className="relative h-8 w-14 rounded-lg" style={{ background: t.bg }}>
                  <span className="absolute bottom-1.5 right-1.5 h-2.5 w-5 rounded-full" style={{ background: t.dot }} />
                </span>
                {t.label}{f.theme === t.key ? ' ✓' : ''}
              </button>
            ))}
          </div>
          <div className="mt-4 grid gap-4 md:grid-cols-2">
            <div>
              <Field label="Offer badge (optional)" value={f.badge} onChange={set('badge')} max={40} error={errors.badge} placeholder="e.g. Early Bird ₹500 OFF" hint="A small shining tag next to “Upcoming event”." />
              <div className="mt-1.5 flex flex-wrap gap-1.5">
                {BADGES.map((b) => <button key={b} type="button" className="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-600 hover:border-gold" onClick={() => set('badge')(b)}>{b}</button>)}
                {f.badge && <button type="button" className="rounded-full px-2 py-0.5 text-[11px] font-semibold text-red-600" onClick={() => set('badge')('')}>Remove</button>}
              </div>
            </div>
            <div className="space-y-2">
              <Field label="WhatsApp number (optional)" value={f.whatsapp} onChange={set('whatsapp')} max={20} error={errors.whatsapp} placeholder="98290 12345" hint="Adds a green WhatsApp button next to the main button." />
              {f.whatsapp && <Field label="WhatsApp message" value={f.whatsapp_text} onChange={set('whatsapp_text')} max={200} error={errors.whatsapp_text} placeholder={`Hi FSIA, I want to know more about ${f.title || 'this event'}.`} hint="Typed for the visitor in WhatsApp. Empty = the grey text shown here." />}
            </div>
          </div>
          <div className="mt-4 grid gap-3 md:grid-cols-2">
            <label className="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 bg-white p-3 text-sm">
              <input type="checkbox" className="mt-0.5" checked={!!f.popup} onChange={(x) => set('popup')(x.target.checked)} />
              <span><b className="text-navy">Popup on first visit</b><br /><span className="text-xs text-slate-500">A big banner in the middle of the screen, once a day per visitor. After it is closed, the bar shows as usual.</span></span>
            </label>
            <label className="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 bg-white p-3 text-sm">
              <input type="checkbox" className="mt-0.5" checked={!!f.solo} onChange={(x) => set('solo')(x.target.checked)} />
              <span><b className="text-navy">Show alone (no rotation)</b><br /><span className="text-xs text-slate-500">Normally all live events on a page take turns every 7 seconds (highest priority first). Tick this to show only this event when it has the top priority.</span></span>
            </label>
          </div>
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
            <Field label="Priority (0–100)" type="number" min={0} value={String(f.priority ?? 0)} onChange={set('priority')} error={errors.priority} hint="When events overlap, the higher one shows first" />
          </div>
          <div className="mt-4 grid gap-4 md:grid-cols-2">
            <RulesField label="Show on" value={f.targets} onChange={set('targets')} groups={groups} error={errors.targets}
              hint="Pick groups and/or add paths, one per line. * matches anything: /news* = all news pages." />
            <RulesField label="But never on" value={f.excludes} onChange={set('excludes')} groups={groups} error={errors.excludes}
              hint="Pages listed here never show it, even if picked above." />
          </div>
        </section>

        <PagePreview e={f} />

        <section>
          <div className="mb-2 flex items-center justify-between gap-2">
            <div>
              <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Celebrity chief guest, guests & jury</p>
              <p className="text-[11px] text-slate-500">Tick "Show on website bar" for up to {BAR_GUESTS} guests: their photo, role and name appear on the bar.</p>
            </div>
            <button type="button" className="btn-ghost shrink-0 !py-1 text-xs"
              onClick={() => set('guests')([...guests, guests.length === 0
                ? { name: '', role: 'Celebrity Chief Guest', photo: '', featured: true }
                : { name: '', role: '', photo: '', featured: false }])}>+ Add guest</button>
          </div>
          {guests.length === 0 && <p className="text-sm text-slate-500">No guests added. The first guest you add is set up as the celebrity chief guest.</p>}
          <datalist id="guest-roles">{GUEST_ROLES.map((r) => <option key={r} value={r} />)}</datalist>
          <div className="grid gap-3 md:grid-cols-2">
            {guests.map((g, i) => {
              const full = !g.featured && barGuests(guests).length >= BAR_GUESTS;
              return (
                <div key={i} className={`relative space-y-2 rounded-xl border p-3 ${g.featured ? 'border-gold bg-gold/5' : 'border-slate-100'}`}>
                  <button type="button" className="absolute right-2 top-2 rounded-lg px-2 text-lg text-red-600 hover:bg-red-50" onClick={() => set('guests')(guests.filter((_, j) => j !== i))} aria-label={`Remove guest ${i + 1}`}>×</button>
                  <div className="grid gap-2 pr-8 sm:grid-cols-2">
                    <input className="input" aria-label={`Guest ${i + 1} name`} placeholder="Name, e.g. the celebrity's name" value={g.name} maxLength={100} onChange={(e) => setGuest(i, 'name', e.target.value)} />
                    <input className="input" aria-label={`Guest ${i + 1} role`} list="guest-roles" placeholder="Role, e.g. Celebrity Chief Guest" value={g.role} maxLength={120} onChange={(e) => setGuest(i, 'role', e.target.value)} />
                  </div>
                  <ImageUpload label="Photo" aspect="aspect-square w-32" value={g.photo} onChange={(v) => setGuest(i, 'photo', v)} hint="Square photo, face in the middle: it shows in a round frame." />
                  <label className={`flex items-center gap-2 text-sm font-semibold ${full ? 'text-slate-400' : 'text-navy'}`}>
                    <input type="checkbox" checked={!!g.featured} disabled={full} onChange={(e) => setGuest(i, 'featured', e.target.checked)} />
                    Show on website bar{full ? ` (only ${BAR_GUESTS})` : ''}
                  </label>
                </div>
              );
            })}
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
  const [statsFor, setStatsFor] = useState(null);

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
      <PageHeader title="Events & Promotions" description="Create events once; the promotion bar on the website follows their dates, pages and priority. One bar per page: when several events are live they take turns."
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
                      <p className="mt-1 flex flex-wrap gap-x-3 text-[11px] font-semibold text-slate-600">
                        <span title="Views">👁 {num(ev.stats?.views)} views</span>
                        <span title="Register clicks">👆 {num(ev.stats?.clicks)} clicks</span>
                        <span title="Clicks ÷ views">📈 {pct(ev.stats?.clicks || 0, ev.stats?.views || 0)}</span>
                        {ev.whatsapp && <span title="WhatsApp clicks">💬 {num(ev.stats?.wa_clicks)}</span>}
                        {ev.stats?.today?.views > 0 && <span className="text-emerald-700">today {num(ev.stats.today.views)} / {num(ev.stats.today.clicks)}</span>}
                      </p>
                      <p className="text-[11px] text-slate-400">Shows on: {ev.targets.map((t) => data.groups[t] || t).join(', ') || '—'}{ev.excludes.length ? ` · not on ${ev.excludes.map((t) => data.groups[t] || t).join(', ')}` : ''}</p>
                    </div>
                    <span className={`rounded-full px-2.5 py-1 text-xs font-bold ${cls}`}>{label}</span>
                    {editable && (
                      <div className="flex flex-wrap gap-1">
                        <button type="button" className="btn-ghost !px-2.5 !py-1 text-xs" onClick={() => setEditing(ev)}>Edit</button>
                        <button type="button" className="btn-ghost !px-2.5 !py-1 text-xs" onClick={() => setStatsFor(ev)}>📊 Stats</button>
                        <button type="button" className="btn-ghost !px-2.5 !py-1 text-xs" onClick={() => duplicate(ev)} title="Copy as a new draft: change the dates and publish">⧉ Duplicate</button>
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

      {statsFor && (
        <StatsModal ev={statsFor} onClose={() => setStatsFor(null)}
          onReset={async () => {
            if (!window.confirm(`Set views and clicks of "${statsFor.title}" back to 0?`)) return;
            try { await api.resetEventStats(statsFor.id); notify('Views and clicks reset'); setStatsFor(null); load(); } catch (e) { notify(e.message, 'error'); }
          }} />
      )}
      {editing && data && (
        <EventForm initial={{ ...BLANK, ...editing }} groups={data.groups} onClose={() => setEditing(null)}
          onSaved={() => { setEditing(null); load(); }} />
      )}
    </>
  );
}
