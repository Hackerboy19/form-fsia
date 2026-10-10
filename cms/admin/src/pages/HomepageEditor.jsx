import { useEffect, useState } from 'react';
import { api, assetUrl } from '../lib/api';
import { PageHeader } from '../components/Layout';
import { Field, SelectField, Toggle } from '../components/Field';
import ImageUpload from '../components/ImageUpload';
import ListEditor, { newId, scheduleStatus } from '../components/ListEditor';
import { useToast } from '../lib/toast';
import DEFAULTS from '../lib/homeDefaults.json';

/*
 * Homepage (index.php) dynamic sections. Each tab is one page_sections row
 * for page "index"; the homepage reads them from window.__FSIA_CMS__.
 *   hero_slides / calendar_events / social_profiles / celebrities -> { items: [...] }
 *   advertisement -> { enabled, label, rotateSeconds, items: [...] }
 * Until a tab is saved, the homepage keeps its built-in content, which is what
 * the editor starts from.
 */

const TABS = [
  { key: 'hero_slides', label: 'Hero Slider', icon: '▶' },
  { key: 'calendar_events', label: 'Event Calendar', icon: '▦' },
  { key: 'advertisement', label: 'Advertisement', icon: '▭' },
  { key: 'social_profiles', label: 'Social Media', icon: '◎' },
  { key: 'celebrities', label: 'Celebrity Jury & Guests', icon: '★' },
];

const AD_DEFAULTS = { enabled: true, label: 'Advertisement', rotateSeconds: 6, items: [] };

function defaultsFor(key) {
  return key === 'advertisement' ? structuredClone(AD_DEFAULTS) : { items: structuredClone(DEFAULTS[key] || []) };
}

export default function HomepageEditor() {
  const [tab, setTab] = useState('hero_slides');
  const [sections, setSections] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    api.getSections('index').then(setSections).catch((e) => setError(e.message));
  }, []);

  return (
    <>
      <PageHeader
        title="Homepage"
        description="Hero slider, event calendar, advertisement, social profiles and celebrity guests on www.fsia.in. Each tab saves separately and is live on the next page load."
      />

      <div className="mb-5 flex gap-2 overflow-x-auto pb-1" role="tablist">
        {TABS.map((t) => (
          <button
            key={t.key}
            type="button"
            role="tab"
            aria-selected={tab === t.key}
            onClick={() => setTab(t.key)}
            className={`flex-none rounded-xl px-4 py-2.5 text-sm font-semibold transition ${tab === t.key ? 'bg-navy text-white' : 'border border-slate-200 bg-white text-navy hover:border-gold'}`}
          >
            <span className="mr-1.5" aria-hidden="true">{t.icon}</span>{t.label}
            {sections && !sections[t.key] && <span className="ml-2 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">default</span>}
          </button>
        ))}
      </div>

      {error && <div className="card border-red-200 p-5 text-sm text-red-700">{error}</div>}
      {!sections && !error && <div className="card h-96 animate-pulse bg-cream" />}
      {sections && (
        <SectionTab
          key={tab}
          sectionKey={tab}
          saved={sections[tab]}
          onSaved={(row) => setSections((s) => ({ ...s, [tab]: row }))}
        />
      )}
    </>
  );
}

/** Load/save wrapper shared by all tabs. */
function SectionTab({ sectionKey, saved, onSaved }) {
  const initial = saved ? { ...defaultsFor(sectionKey), ...saved.content } : defaultsFor(sectionKey);
  const [content, setContent] = useState(initial);
  const [clean, setClean] = useState(JSON.stringify(initial));
  const [saving, setSaving] = useState(false);
  const [problems, setProblems] = useState([]);
  const notify = useToast();
  const dirty = JSON.stringify(content) !== clean;
  const tab = TABS.find((t) => t.key === sectionKey);

  useEffect(() => {
    const warn = (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } };
    window.addEventListener('beforeunload', warn);
    return () => window.removeEventListener('beforeunload', warn);
  }, [dirty]);

  async function save() {
    const list = VALIDATORS[sectionKey](content);
    setProblems(list);
    if (list.length) {
      notify('Please fix the highlighted items first', 'error');
      return;
    }
    setSaving(true);
    try {
      const row = await api.saveSection('index', sectionKey, { content, image_url: '' });
      const next = { ...defaultsFor(sectionKey), ...row.content };
      setContent(next);
      setClean(JSON.stringify(next));
      onSaved(row);
      notify(`${tab.label} saved — live on www.fsia.in`);
    } catch (e) {
      notify(e.fields?.content || e.message, 'error');
    } finally {
      setSaving(false);
    }
  }

  function resetToDefaults() {
    if (!window.confirm(`Replace the editor contents with the original built-in ${tab.label.toLowerCase()}? Nothing changes on the website until you save.`)) return;
    setContent(defaultsFor(sectionKey));
  }

  const Editor = EDITORS[sectionKey];
  return (
    <div>
      <div className="card mb-5 flex flex-wrap items-center justify-between gap-3 p-4">
        <div className="text-sm">
          {saved ? (
            <span className="text-slate-600">Last saved <strong>{saved.updated_at}</strong></span>
          ) : (
            <span className="text-amber-700">Not saved yet: the homepage shows its built-in content, loaded below. Edit and save to take control of it.</span>
          )}
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {dirty && <span className="text-xs font-semibold text-amber-600">Unsaved changes</span>}
          <button type="button" className="btn-ghost" onClick={resetToDefaults}>Load defaults</button>
          {dirty && <button type="button" className="btn-ghost" onClick={() => setContent(JSON.parse(clean))}>Discard</button>}
          <button type="button" className="btn-primary" onClick={save} disabled={saving || (!dirty && saved)}>
            {saving ? 'Saving…' : 'Save & publish'}
          </button>
        </div>
      </div>

      {problems.length > 0 && (
        <div className="card mb-5 border-red-200 bg-red-50 p-4 text-sm text-red-700">
          <p className="font-semibold">Cannot save yet:</p>
          <ul className="mt-1 list-disc pl-5">{problems.map((p) => <li key={p}>{p}</li>)}</ul>
        </div>
      )}

      <Editor content={content} setContent={setContent} />
    </div>
  );
}

const named = (it, i, field = 'title') => `"${it[field] || `item ${i + 1}`}"`;
const isUrl = (u) => !u || /^(https?:\/\/|\/(?!\/)|#|mailto:|tel:)/i.test(u.trim());

const VALIDATORS = {
  hero_slides: ({ items }) => [
    ...(items.length ? [] : ['Add at least one slide.']),
    ...items.flatMap((s, i) => [
      !s.image && `Slide ${named(s, i)} needs an image.`,
      !isUrl(s.ctaUrl) && `Slide ${named(s, i)}: the redirect URL must start with https:// or /.`,
      s.startDate && s.endDate && s.startDate > s.endDate && `Slide ${named(s, i)}: the end date is before the start date.`,
    ]).filter(Boolean),
  ],
  calendar_events: ({ items }) =>
    items.flatMap((e, i) => [
      !e.title?.trim() && `Event ${i + 1} needs a title.`,
      !/^\d{4}-\d{2}-\d{2}$/.test(e.date || '') && `Event ${named(e, i)} needs a date.`,
      e.endDate && e.endDate < e.date && `Event ${named(e, i)}: the end date is before the start date.`,
      !isUrl(e.ctaUrl) && `Event ${named(e, i)}: the button link must start with https:// or /.`,
    ]).filter(Boolean),
  advertisement: ({ items }) =>
    items.flatMap((a, i) => [
      !a.image && `Ad ${named(a, i)} needs a desktop image.`,
      !isUrl(a.link) && `Ad ${named(a, i)}: the link must start with https:// or /.`,
    ]).filter(Boolean),
  social_profiles: ({ items }) =>
    items.flatMap((p, i) => [
      !p.name?.trim() && `Profile ${i + 1} needs a platform name.`,
      !/^https?:\/\/[^\s/]+\.[^\s]+$/i.test((p.url || '').trim()) && `Profile ${named(p, i, 'name')} needs a full https:// link.`,
    ]).filter(Boolean),
  celebrities: ({ items }) =>
    items.flatMap((c, i) => [
      !c.name?.trim() && `Guest ${i + 1} needs a name.`,
      !c.image && `Guest ${named(c, i, 'name')} needs a photo.`,
    ]).filter(Boolean),
};

/* ------------------------------------------------------------------ */
/* Hero slider                                                         */
/* ------------------------------------------------------------------ */

const FIT_MODES = [
  { value: 'auto', label: 'Auto-Adaptive', hint: 'Posters fit whole; photos fill on desktop' },
  { value: 'contain', label: 'Full (Contain)', hint: 'Whole image visible, no cropping' },
  { value: 'cover', label: 'Fill (Cover)', hint: 'Fills the frame, edges may crop' },
];

function HeroSlidesEditor({ content, setContent }) {
  const items = content.items;
  const [selected, setSelected] = useState(items[0]?.id ?? null);
  const setItems = (next) => setContent({ ...content, items: next });

  return (
    <ListEditor
      items={items}
      selected={selected}
      onSelect={setSelected}
      onChange={setItems}
      addLabel="Add slide"
      makeNew={() => ({
        id: newId('slide'), image: '', title: 'New slide', categoryTag: '', venue: '', subtitle: '', badge: '', alt: '',
        ctaText: 'Quick Apply', ctaUrl: 'https://www.fsia.in/quickapply', fitMode: 'auto',
        startDate: '', endDate: '', timerDuration: 6, showCountdown: true,
      })}
      describe={(s) => ({
        title: s.title,
        subtitle: `${s.timerDuration || 5}s • ${FIT_MODES.find((f) => f.value === s.fitMode)?.label || 'Auto'}`,
        image: s.image,
        badge: scheduleStatus(s.startDate, s.endDate),
      })}
      renderForm={(s, set) => <SlideForm key={s.id} slide={s} set={set} />}
    />
  );
}

function SlideForm({ slide: s, set }) {
  const [source, setSource] = useState('upload');
  return (
    <div className="space-y-6">
      <section>
        <h3 className="mb-3 font-display text-base font-bold">1. Slide image</h3>
        <div className="mb-3 flex flex-wrap gap-2">
          {[['upload', 'Upload / URL'], ['library', 'FSIA library']].map(([v, l]) => (
            <button key={v} type="button" onClick={() => setSource(v)} className={`rounded-lg px-3 py-1.5 text-xs font-bold ${source === v ? 'bg-navy text-white' : 'bg-slate-100 text-slate-600'}`}>{l}</button>
          ))}
        </div>
        {source === 'upload' ? (
          <ImageUpload label="Image" value={s.image} onChange={(image) => set({ image })} hint="Wide images work best (about 1600×900). Posters with text: use Full (Contain)." />
        ) : (
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            {DEFAULTS.library_presets.map((p) => (
              <button
                key={p.url}
                type="button"
                onClick={() => set({ image: p.url, fitMode: p.fitMode || s.fitMode })}
                className={`overflow-hidden rounded-xl border-2 text-left transition ${s.image === p.url ? 'border-gold' : 'border-transparent hover:border-gold/50'}`}
              >
                <img src={p.url} alt="" className="aspect-video w-full bg-cream object-cover" loading="lazy" />
                <span className="block truncate px-2 py-1 text-[11px] font-semibold">{p.name}</span>
                <span className="block px-2 pb-1.5 text-[10px] text-slate-500">{p.tag}</span>
              </button>
            ))}
          </div>
        )}
        <div className="mt-4">
          <span className="label">Display mode</span>
          <div className="grid gap-2 sm:grid-cols-3">
            {FIT_MODES.map((f) => (
              <button key={f.value} type="button" onClick={() => set({ fitMode: f.value })}
                className={`rounded-xl border p-3 text-left transition ${s.fitMode === f.value ? 'border-gold bg-gold/10' : 'border-slate-200 hover:border-gold/50'}`}>
                <span className="block text-sm font-semibold">{f.label}</span>
                <span className="block text-[11px] text-slate-500">{f.hint}</span>
              </button>
            ))}
          </div>
        </div>
        {s.image && (
          <div className="mt-4">
            <span className="label">Preview</span>
            <div className="relative aspect-[16/9] overflow-hidden rounded-xl bg-navy">
              <img src={assetUrl(s.image)} alt="" className={`h-full w-full ${s.fitMode === 'contain' ? 'object-contain' : 'object-cover'}`} />
              <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-navy/90 to-transparent p-4 text-white">
                {s.categoryTag && <span className="text-[10px] font-bold uppercase tracking-widest text-gold">{s.categoryTag}</span>}
                <p className="font-display text-lg font-bold leading-tight">{s.title}</p>
                {s.venue && <p className="text-xs text-slate-300">{s.venue}</p>}
              </div>
              {s.badge && <span className="absolute left-3 top-3 rounded-full bg-gold px-2.5 py-1 text-[10px] font-bold uppercase text-navy">{s.badge}</span>}
            </div>
          </div>
        )}
      </section>

      <section>
        <h3 className="mb-3 font-display text-base font-bold">2. Schedule & timer</h3>
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Start date & time" type="datetime-local" value={s.startDate} onChange={(startDate) => set({ startDate })} hint="Slide is hidden before this. Leave empty to start now." />
          <Field label="End date & time" type="datetime-local" value={s.endDate} onChange={(endDate) => set({ endDate })} hint="Slide is hidden after this. Leave empty to never expire." />
        </div>
        <div className="mt-4">
          <div className="flex items-baseline justify-between">
            <label className="label" htmlFor="slide-duration">Slide duration</label>
            <span className="text-sm font-bold text-gold-dark">{s.timerDuration || 5} seconds</span>
          </div>
          <input id="slide-duration" type="range" min="3" max="15" step="1" value={s.timerDuration || 5}
            onChange={(e) => set({ timerDuration: Number(e.target.value) })} className="w-full accent-[#D4AF37]" />
          <div className="flex justify-between text-[10px] text-slate-400"><span>3s</span><span>15s</span></div>
        </div>
        <div className="mt-4">
          <Toggle checked={!!s.showCountdown} onChange={(showCountdown) => set({ showCountdown })} label="Show countdown" description="Displays “Closes in 12d 4h” on the slide, counting to the end date." />
        </div>
      </section>

      <section>
        <h3 className="mb-3 font-display text-base font-bold">3. Button</h3>
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Redirect URL" value={s.ctaUrl} onChange={(ctaUrl) => set({ ctaUrl })} placeholder="https://www.fsia.in/quickapply" />
          <Field label="Button label" value={s.ctaText} onChange={(ctaText) => set({ ctaText })} max={40} placeholder="Quick Apply 2026" />
        </div>
      </section>

      <section>
        <h3 className="mb-3 font-display text-base font-bold">4. Text on the slide</h3>
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Category tag" value={s.categoryTag} onChange={(categoryTag) => set({ categoryTag })} max={60} placeholder="Auditions Open • Season 2026" />
          <Field label="Venue" value={s.venue} onChange={(venue) => set({ venue })} max={80} placeholder="Zee Studio Arena • Jaipur" />
          <div className="sm:col-span-2"><Field label="Headline" value={s.title} onChange={(title) => set({ title })} max={120} /></div>
          <div className="sm:col-span-2"><Field label="Description" type="textarea" rows={3} value={s.subtitle} onChange={(subtitle) => set({ subtitle })} max={400} /></div>
          <Field label="Badge" value={s.badge} onChange={(badge) => set({ badge })} max={30} placeholder="Auditions 2026" />
          <Field label="Image alt text (SEO)" value={s.alt} onChange={(alt) => set({ alt })} max={160} />
        </div>
      </section>
    </div>
  );
}

/* ------------------------------------------------------------------ */
/* Event calendar                                                      */
/* ------------------------------------------------------------------ */

const EVENT_TYPES = [
  { value: 'audition', label: 'Audition / Screening', color: '#059669' },
  { value: 'workshop', label: 'Masterclass / Workshop', color: '#0d9488' },
  { value: 'pageant', label: 'Pageant Finale', color: '#9F1239' },
  { value: 'award', label: 'National Award', color: '#D4AF37' },
  { value: 'ceremony', label: 'Coronation Gala', color: '#7c2d12' },
];
const EVENT_STATUS = [
  { value: 'upcoming', label: 'Upcoming' },
  { value: 'open', label: 'Registrations open' },
  { value: 'active', label: 'Happening now' },
  { value: 'closing_soon', label: 'Closing soon' },
  { value: 'concluded', label: 'Concluded' },
];
const typeColor = (t) => EVENT_TYPES.find((x) => x.value === t)?.color || '#94a3b8';
const fmtDate = (d) => (d ? new Date(`${d}T00:00`).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : 'No date');

function CalendarEditor({ content, setContent }) {
  const [selected, setSelected] = useState(content.items[0]?.id ?? null);
  const [sortByDate, setSortByDate] = useState(true);
  const items = sortByDate ? [...content.items].sort((a, b) => (a.date || '').localeCompare(b.date || '')) : content.items;

  return (
    <>
      <div className="card mb-5 flex flex-wrap items-center gap-x-5 gap-y-2 p-4 text-xs">
        <span className="font-bold uppercase tracking-wider text-slate-500">Calendar colours</span>
        {EVENT_TYPES.map((t) => (
          <span key={t.value} className="inline-flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-full" style={{ background: t.color }} />{t.label}</span>
        ))}
        <span className="inline-flex items-center gap-1.5"><span className="text-gold-dark">★</span>Featured (Season Peak)</span>
        <label className="ml-auto inline-flex items-center gap-2">
          <input type="checkbox" checked={sortByDate} onChange={(e) => setSortByDate(e.target.checked)} /> Sort by date
        </label>
      </div>
      <ListEditor
        items={items}
        selected={selected}
        onSelect={setSelected}
        onChange={(next) => setContent({ ...content, items: next })}
        addLabel="Add event"
        makeNew={() => ({
          id: newId('evt'), title: 'New event', date: new Date().toISOString().slice(0, 10), endDate: '', time: '10:00 AM – 6:00 PM IST',
          type: 'audition', category: '', venue: '', city: '', description: '', eligibility: '', highlights: [], status: 'upcoming',
          ctaText: 'Register Now', ctaUrl: 'https://www.fsia.in/quickapply', image: '', keywords: [], highlight: false,
        })}
        describe={(e) => ({
          title: `${e.highlight ? '★ ' : ''}${e.title}`,
          subtitle: `${fmtDate(e.date)}${e.city ? ` • ${e.city}` : ''}`,
          image: e.image,
          dot: typeColor(e.type),
          badge: { text: EVENT_STATUS.find((s) => s.value === e.status)?.label || e.status, tone: e.status === 'concluded' ? 'bg-slate-100 text-slate-500' : 'bg-emerald-100 text-emerald-700' },
        })}
        renderForm={(e, set) => <EventForm key={e.id} ev={e} set={set} />}
      />
    </>
  );
}

const splitList = (s, sep) => s.split(sep).map((x) => x.trim()).filter(Boolean);

function EventForm({ ev: e, set }) {
  // Keep the raw text while typing so commas/new lines are not eaten.
  const [keywords, setKeywords] = useState((e.keywords || []).join(', '));
  const [highlights, setHighlights] = useState((e.highlights || []).join('\n'));

  return (
    <div className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="sm:col-span-2"><Field label="Event title" value={e.title} onChange={(title) => set({ title })} max={120} /></div>
        <SelectField label="Type (calendar colour + filter)" value={e.type} options={EVENT_TYPES} onChange={(type) => set({ type })} />
        <SelectField label="Status" value={e.status} options={EVENT_STATUS} onChange={(status) => set({ status })} />
        <Field label="Date" type="date" value={e.date} onChange={(date) => set({ date })} />
        <Field label="End date (optional)" type="date" value={e.endDate} onChange={(endDate) => set({ endDate })} hint="For events spanning several days" />
        <Field label="Time" value={e.time} onChange={(time) => set({ time })} placeholder="10:00 AM – 6:00 PM IST" />
        <Field label="Category / title name" value={e.category} onChange={(category) => set({ category })} placeholder="Forever Miss India" />
        <Field label="Venue" value={e.venue} onChange={(venue) => set({ venue })} placeholder="Zee Studio Arena" />
        <Field label="City" value={e.city} onChange={(city) => set({ city })} placeholder="Jaipur, Rajasthan" />
        <div className="sm:col-span-2"><Field label="Description" type="textarea" rows={3} value={e.description} onChange={(description) => set({ description })} max={600} /></div>
        <div className="sm:col-span-2"><Field label="Eligibility (optional)" value={e.eligibility} onChange={(eligibility) => set({ eligibility })} /></div>
        <div className="sm:col-span-2">
          <Field label="Programme highlights (one per line)" type="textarea" rows={3} value={highlights}
            onChange={(v) => { setHighlights(v); set({ highlights: splitList(v, '\n') }); }} />
        </div>
      </div>

      <div className="rounded-xl border border-gold-light bg-cream/60 p-4">
        <h3 className="mb-3 font-display text-base font-bold">Highlighting</h3>
        <div className="space-y-4">
          <Toggle checked={!!e.highlight} onChange={(highlight) => set({ highlight })} label="Featured event" description="Shows a ★ on the calendar day; the “Season Peak” button jumps to the next featured event." />
          <Field label="Search keywords" value={keywords} onChange={(v) => { setKeywords(v); set({ keywords: splitList(v, ',') }); }}
            placeholder="miss india, jaipur, finale" hint="Comma separated. When a visitor searches one of these words (or picks this type's filter), matching days get a gold ring and other days fade." />
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Button label" value={e.ctaText} onChange={(ctaText) => set({ ctaText })} max={40} />
        <Field label="Button link" value={e.ctaUrl} onChange={(ctaUrl) => set({ ctaUrl })} />
      </div>
      <ImageUpload label="Event image (optional)" value={e.image} onChange={(image) => set({ image })} />
    </div>
  );
}

/* ------------------------------------------------------------------ */
/* Advertisement                                                       */
/* ------------------------------------------------------------------ */

function AdvertisementEditor({ content, setContent }) {
  const [selected, setSelected] = useState(content.items[0]?.id ?? null);
  const patch = (p) => setContent({ ...content, ...p });

  return (
    <>
      <div className="card mb-5 grid gap-4 p-5 sm:grid-cols-3">
        <Toggle checked={!!content.enabled} onChange={(enabled) => patch({ enabled })} label="Show advertisement section" description="Off hides the whole block on the homepage." />
        <Field label="Label (top-right corner)" value={content.label} onChange={(label) => patch({ label })} max={30} />
        <Field label="Rotate every (seconds)" type="number" value={content.rotateSeconds}
          onChange={(v) => patch({ rotateSeconds: Math.max(3, Math.min(60, Number(v) || 6)) })} hint="Used when 2+ ads are active" />
      </div>
      {content.items.length === 0 && (
        <p className="mb-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
          No ads yet. While this section has never been saved the homepage shows an empty 728×90 placeholder; once saved with no active ads, the block is hidden.
        </p>
      )}
      <ListEditor
        items={content.items}
        selected={selected}
        onSelect={setSelected}
        onChange={(items) => patch({ items })}
        addLabel="Add advertisement"
        makeNew={() => ({
          id: newId('ad'), title: 'New advertisement', image: '', mobileImage: '', link: '', alt: '',
          sizeMode: 'auto', width: 728, height: 90, mobileWidth: '', mobileHeight: '', startDate: '', endDate: '', active: true,
        })}
        describe={(a) => ({
          title: a.title,
          subtitle: a.sizeMode === 'manual' ? `Manual ${a.width || '–'}×${a.height || '–'} px` : 'Auto size',
          image: a.image,
          badge: a.active === false ? { text: 'Paused', tone: 'bg-slate-100 text-slate-500' } : scheduleStatus(a.startDate, a.endDate),
        })}
        renderForm={(a, set) => <AdForm ad={a} set={set} label={content.label} />}
      />
    </>
  );
}

function AdForm({ ad: a, set, label }) {
  const manual = a.sizeMode === 'manual';
  const num = (v) => (v === '' ? '' : Math.max(1, Math.min(2000, Number(v) || 0)));
  return (
    <div className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Name (for you)" value={a.title} onChange={(title) => set({ title })} max={80} />
        <Field label="Click-through link" value={a.link} onChange={(link) => set({ link })} placeholder="https://sponsor.example.com" />
        <Field label="Alt text" value={a.alt} onChange={(alt) => set({ alt })} max={160} />
        <div className="self-end pb-1"><Toggle checked={a.active !== false} onChange={(active) => set({ active })} label="Active" /></div>
        <Field label="Show from (optional)" type="datetime-local" value={a.startDate} onChange={(startDate) => set({ startDate })} />
        <Field label="Show until (optional)" type="datetime-local" value={a.endDate} onChange={(endDate) => set({ endDate })} />
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <ImageUpload label="Desktop image" value={a.image} onChange={(image) => set({ image })} aspect="aspect-[728/180]" hint="e.g. 728×90 or 970×250" />
        <ImageUpload label="Mobile image (optional)" value={a.mobileImage} onChange={(mobileImage) => set({ mobileImage })} aspect="aspect-[6/5]" hint="e.g. 300×250. Empty = desktop image" />
      </div>

      <div className="rounded-xl border border-gold-light bg-cream/60 p-4">
        <span className="label">Size</span>
        <div className="mb-4 grid gap-2 sm:grid-cols-2">
          {[['auto', 'Auto-adjust', 'Fills the slot width, keeps its proportions'], ['manual', 'Set manually', 'Exact width × height in pixels']].map(([v, l, h]) => (
            <button key={v} type="button" onClick={() => set({ sizeMode: v })}
              className={`rounded-xl border p-3 text-left transition ${a.sizeMode === v || (!a.sizeMode && v === 'auto') ? 'border-gold bg-white' : 'border-slate-200 hover:border-gold/50'}`}>
              <span className="block text-sm font-semibold">{l}</span>
              <span className="block text-[11px] text-slate-500">{h}</span>
            </button>
          ))}
        </div>
        {manual && (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <Field label="Desktop width" type="number" value={a.width} onChange={(v) => set({ width: num(v) })} />
            <Field label="Desktop height" type="number" value={a.height} onChange={(v) => set({ height: num(v) })} />
            <Field label="Mobile width" type="number" value={a.mobileWidth} onChange={(v) => set({ mobileWidth: num(v) })} />
            <Field label="Mobile height" type="number" value={a.mobileHeight} onChange={(v) => set({ mobileHeight: num(v) })} />
            <p className="col-span-full text-[11px] text-slate-500">Pixels. Empty mobile size = same as desktop. Never wider than the screen; the image is cropped to fill the box.</p>
          </div>
        )}
      </div>

      <div>
        <span className="label">Live preview</span>
        <div className="space-y-4 rounded-xl bg-slate-100 p-4">
          <AdPreview ad={a} label={label} mobile={false} />
          <AdPreview ad={a} label={label} mobile />
        </div>
      </div>
    </div>
  );
}

/** Same box and sizing rules as the homepage's AdvertisementSection. */
function AdPreview({ ad: a, label, mobile }) {
  const manual = a.sizeMode === 'manual';
  const src = assetUrl(mobile && a.mobileImage ? a.mobileImage : a.image);
  const w = mobile ? a.mobileWidth || a.width : a.width;
  const h = mobile ? a.mobileHeight || a.height : a.height;
  return (
    <div>
      <p className="mb-1 text-[11px] font-bold uppercase tracking-wider text-slate-500">{mobile ? 'Mobile (360 px screen)' : 'Desktop'}</p>
      <div className={`mx-auto bg-white ${mobile ? 'w-[360px] max-w-full rounded-[20px] border-[6px] border-navy px-4 py-6' : 'w-full max-w-[760px] p-1'}`}>
        <div className="relative w-full border border-[#EADBAC] bg-[#FAF9F5] px-4 py-6">
          <span className="absolute right-3 top-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#9AA3B2]">{label || 'Advertisement'}</span>
          <div className="mt-3 flex justify-center">
            {src ? (
              <img src={src} alt="" className={`mx-auto block ${manual ? '' : 'h-auto w-full'}`}
                style={manual ? { width: w ? `${w}px` : undefined, height: h ? `${h}px` : undefined, maxWidth: '100%', objectFit: w && h ? 'cover' : undefined } : undefined} />
            ) : (
              <div className="grid h-[90px] w-full place-items-center border border-dashed border-gold/50 text-xs text-slate-400">No image</div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

/* ------------------------------------------------------------------ */
/* Social media                                                        */
/* ------------------------------------------------------------------ */

const ICONS = [
  { value: 'instagram', label: 'Instagram', color: '#E1306C' },
  { value: 'facebook', label: 'Facebook', color: '#1877F2' },
  { value: 'youtube', label: 'YouTube', color: '#FF0000' },
  { value: 'linkedin', label: 'LinkedIn', color: '#0A66C2' },
  { value: 'twitter', label: 'X / Twitter', color: '#0f172a' },
  { value: 'pinterest', label: 'Pinterest', color: '#E60023' },
];

function SocialEditor({ content, setContent }) {
  const [selected, setSelected] = useState(content.items[0]?.id ?? null);
  return (
    <ListEditor
      items={content.items}
      selected={selected}
      onSelect={setSelected}
      onChange={(items) => setContent({ ...content, items })}
      addLabel="Add profile"
      makeNew={() => ({ id: newId('social'), name: 'Instagram', profileName: '@', iconName: 'instagram', url: 'https://', description: '', badge: '', thumbnail: '' })}
      describe={(p) => ({ title: p.name, subtitle: p.profileName, image: p.thumbnail, dot: ICONS.find((i) => i.value === p.iconName)?.color })}
      renderForm={(p, set) => (
        <div className="space-y-5">
          <div className="grid gap-4 sm:grid-cols-2">
            <SelectField label="Icon" value={p.iconName} options={ICONS} onChange={(iconName) => set({ iconName, name: p.name || ICONS.find((i) => i.value === iconName)?.label })} />
            <Field label="Platform name" value={p.name} onChange={(name) => set({ name })} max={40} />
            <Field label="Profile / handle" value={p.profileName} onChange={(profileName) => set({ profileName })} max={60} placeholder="@fsia_forever" />
            <Field label="Profile link" value={p.url} onChange={(url) => set({ url })} placeholder="https://www.instagram.com/fsia_forever/" />
            <Field label="Badge" value={p.badge} onChange={(badge) => set({ badge })} max={40} placeholder="Official Pageant Feed" />
            <div className="sm:col-span-2"><Field label="Description" type="textarea" rows={3} value={p.description} onChange={(description) => set({ description })} max={300} /></div>
          </div>
          <ImageUpload label="Profile image (card thumbnail)" value={p.thumbnail} onChange={(thumbnail) => set({ thumbnail })} hint="Shown at the top of the card. Optional." />
        </div>
      )}
    />
  );
}

/* ------------------------------------------------------------------ */
/* Celebrity jury & guests                                             */
/* ------------------------------------------------------------------ */

function CelebritiesEditor({ content, setContent }) {
  const [selected, setSelected] = useState(content.items[0]?.id ?? null);
  return (
    <ListEditor
      items={content.items}
      selected={selected}
      onSelect={setSelected}
      onChange={(items) => setContent({ ...content, items })}
      addLabel="Add guest"
      makeNew={() => ({ id: newId('celeb'), name: 'New guest', role: '', description: '', image: '', event: '' })}
      describe={(c) => ({ title: c.name, subtitle: c.role, image: c.image })}
      renderForm={(c, set) => (
        <div className="grid gap-5 md:grid-cols-[240px_minmax(0,1fr)]">
          <ImageUpload label="Photo" value={c.image} onChange={(image) => set({ image })} aspect="aspect-[4/5]" hint="Portrait works best" />
          <div className="space-y-4">
            <Field label="Name" value={c.name} onChange={(name) => set({ name })} max={80} />
            <Field label="Role" value={c.role} onChange={(role) => set({ role })} max={100} placeholder="Celebrity Chief Guest & Jury" />
            <Field label="Event" value={c.event} onChange={(event) => set({ event })} max={120} placeholder="Grand Finale • Zee Studio Jaipur" />
            <Field label="Description" type="textarea" rows={4} value={c.description} onChange={(description) => set({ description })} max={400} />
          </div>
        </div>
      )}
    />
  );
}

const EDITORS = {
  hero_slides: HeroSlidesEditor,
  calendar_events: CalendarEditor,
  advertisement: AdvertisementEditor,
  social_profiles: SocialEditor,
  celebrities: CelebritiesEditor,
};
