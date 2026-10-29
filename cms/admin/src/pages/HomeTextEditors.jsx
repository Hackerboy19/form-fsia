import { useState } from 'react';
import { assetUrl } from '../lib/api';
import { Field } from '../components/Field';
import ImageUpload from '../components/ImageUpload';
import ListEditor, { newId } from '../components/ListEditor';

/*
 * Editors for the homepage's text blocks (page_sections keys home_*). Each is
 * one object; the homepage merges it over its built-in text, field by field.
 */

const isLink = (u) => !u || /^(https?:\/\/|\/(?!\/)|#|mailto:|tel:)/i.test(u.trim());
const linkHint = 'https://…, /page.php, tel:+91… or mailto:…';

function Card({ title, hint, children }) {
  return (
    <section className="card p-5">
      <h3 className="font-display text-base font-bold">{title}</h3>
      {hint && <p className="mb-4 mt-0.5 text-xs text-slate-500">{hint}</p>}
      <div className={hint ? '' : 'mt-4'}>{children}</div>
    </section>
  );
}

/** A short list edited as rows of inputs (stats, contact cards ...). */
function Rows({ rows, onChange, columns, makeNew, addLabel, max = 12 }) {
  const set = (i, patch) => onChange(rows.map((r, j) => (j === i ? { ...r, ...patch } : r)));
  const move = (i, d) => {
    const j = i + d;
    if (j < 0 || j >= rows.length) return;
    const next = [...rows];
    [next[i], next[j]] = [next[j], next[i]];
    onChange(next);
  };
  return (
    <div className="space-y-3">
      {rows.map((r, i) => (
        <div key={i} className="rounded-xl border border-slate-200 bg-cream/40 p-3">
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {columns.map((c) => (
              <div key={c.name} className={c.wide ? 'sm:col-span-2' : ''}>
                <Field label={c.label} type={c.type || 'text'} value={r[c.name] ?? ''} placeholder={c.placeholder}
                  onChange={(v) => set(i, { [c.name]: c.type === 'number' ? (v === '' ? '' : Number(v)) : v })} />
              </div>
            ))}
          </div>
          <div className="mt-2 flex justify-end gap-3 text-xs font-semibold">
            <button type="button" className="text-slate-500 hover:text-navy disabled:opacity-30" onClick={() => move(i, -1)} disabled={i === 0}>▲ Up</button>
            <button type="button" className="text-slate-500 hover:text-navy disabled:opacity-30" onClick={() => move(i, 1)} disabled={i === rows.length - 1}>▼ Down</button>
            <button type="button" className="text-red-600 hover:text-red-800" onClick={() => onChange(rows.filter((_, j) => j !== i))}>Delete</button>
          </div>
        </div>
      ))}
      {rows.length < max && <button type="button" className="btn-ghost" onClick={() => onChange([...rows, makeNew()])}>+ {addLabel}</button>}
    </div>
  );
}

function SectionHeader({ c, set }) {
  return (
    <Card title="Section heading">
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Small label above the title" value={c.badge} onChange={(badge) => set({ badge })} max={80} />
        <Field label="Title" value={c.title} onChange={(title) => set({ title })} max={80} />
        <div className="sm:col-span-2"><Field label="Subtitle" type="textarea" rows={2} value={c.subtitle} onChange={(subtitle) => set({ subtitle })} max={300} /></div>
      </div>
    </Card>
  );
}

function ButtonFields({ c, set, n = '', label = 'Button' }) {
  const t = `button${n}Text`;
  const u = `button${n}Url`;
  return (
    <div className="grid gap-4 sm:grid-cols-2">
      <Field label={`${label} text`} value={c[t]} onChange={(v) => set({ [t]: v })} max={60} hint="Empty = button hidden" />
      <Field label={`${label} link`} value={c[u]} onChange={(v) => set({ [u]: v })} hint={linkHint} />
    </div>
  );
}

/* ---------------- Top text (hero) ---------------- */

export function HeroTextEditor({ content: c, setContent }) {
  const set = (p) => setContent({ ...c, ...p });
  return (
    <div className="space-y-5">
      <Card title="Heading" hint="The big three-line heading at the top of the homepage. The middle line is gold.">
        <div className="grid gap-4 sm:grid-cols-3">
          <Field label="Line 1" value={c.line1} onChange={(line1) => set({ line1 })} max={40} />
          <Field label="Line 2 (gold)" value={c.line2} onChange={(line2) => set({ line2 })} max={40} />
          <Field label="Line 3" value={c.line3} onChange={(line3) => set({ line3 })} max={40} />
        </div>
        <div className="mt-4 rounded-xl bg-[#FAF9F5] p-5">
          <span className="inline-flex items-center gap-2 border border-gold/50 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-[#7E591B]">● {c.eyebrow}</span>
          <p className="mt-3 font-display text-3xl font-bold leading-tight text-navy">
            {c.line1}<br /><span className="text-[#B8860B]">{c.line2}</span><br />{c.line3}
          </p>
        </div>
      </Card>
      <Card title="Text">
        <div className="space-y-4">
          <Field label="Small label above the heading" value={c.eyebrow} onChange={(eyebrow) => set({ eyebrow })} max={80} />
          <Field label="Introduction paragraph" type="textarea" rows={5} value={c.intro} onChange={(intro) => set({ intro })} max={1000}
            hint="Enter = new line, an empty line = new paragraph. Put **two stars** around words to make them bold, e.g. **4,000+ Indian cities**" />
        </div>
      </Card>
      <Card title="Buttons">
        <div className="space-y-4">
          <ButtonFields c={c} set={set} n="1" label="Main button" />
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Second button text" value={c.button2Text} onChange={(button2Text) => set({ button2Text })} max={60} hint="Empty = button hidden" />
            <Field label="Second button link" value={c.button2Url} onChange={(button2Url) => set({ button2Url })} hint="Empty = scrolls down to the event calendar" />
          </div>
        </div>
      </Card>
      <Card title="Facts strip" hint="Small facts under the buttons, e.g. “Reach: 4,000+ Cities Nationwide”.">
        <Rows rows={c.items || []} onChange={(items) => set({ items })} addLabel="Add fact" max={6}
          makeNew={() => ({ label: 'Label:', value: '' })}
          columns={[{ name: 'label', label: 'Bold part' }, { name: 'value', label: 'Text', wide: true }]} />
      </Card>
    </div>
  );
}

/* ---------------- Stats ---------------- */

export function StatsEditor({ content: c, setContent }) {
  const set = (p) => setContent({ ...c, ...p });
  return (
    <div className="space-y-5">
      <Card title="Numbers" hint="The counters under the hero (they count up when scrolled into view). 4 fit in one row.">
        <Rows rows={c.items || []} onChange={(items) => set({ items })} addLabel="Add number" max={8}
          makeNew={() => ({ label: 'New stat', value: 100, suffix: '+', subtext: '' })}
          columns={[
            { name: 'value', label: 'Number', type: 'number' },
            { name: 'suffix', label: 'After number', placeholder: '+' },
            { name: 'label', label: 'Title' },
            { name: 'subtext', label: 'Description' },
          ]} />
      </Card>
      <Card title="Line under the numbers">
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Bold part" value={c.footerTitle} onChange={(footerTitle) => set({ footerTitle })} max={60} />
          <Field label="Text" value={c.footerText} onChange={(footerText) => set({ footerText })} max={200} />
          <div className="sm:col-span-2"><Field label="Right side (source)" value={c.source} onChange={(source) => set({ source })} max={160} /></div>
        </div>
      </Card>
    </div>
  );
}

/* ---------------- CEO / Team ---------------- */

export function TeamEditor({ content: c, setContent }) {
  const set = (p) => setContent({ ...c, ...p });
  // Members need an id for the list editor; the built-in CEO entry has none.
  const items = (c.items || []).map((m, i) => (m.id ? m : { ...m, id: `member-${i}` }));
  const [selected, setSelected] = useState(items[0]?.id ?? null);
  return (
    <div className="space-y-5">
      <Card title="Featured profile(s)" hint="The big card on the homepage. Today only the Founder & CEO is shown; add a second person and they appear next to it.">
        <ListEditor
          items={items}
          selected={selected}
          onSelect={setSelected}
          onChange={(next) => set({ items: next })}
          addLabel="Add person"
          makeNew={() => ({ id: newId('member'), name: 'New person', role: '', designation: '', bio: '', image: '' })}
          describe={(m) => ({ title: m.name, subtitle: m.role, image: m.image })}
          renderForm={(m, upd) => (
            <div className="grid gap-5 md:grid-cols-[220px_minmax(0,1fr)]">
              <ImageUpload label="Photo" value={m.image} onChange={(image) => upd({ image })} size={{ w: 800, h: 800, fit: 'cover' }} hint="Square; face at the top." />
              <div className="space-y-4">
                <Field label="Name" value={m.name} onChange={(name) => upd({ name })} max={80} />
                <Field label="Role (small gold text)" value={m.role} onChange={(role) => upd({ role })} max={60} placeholder="Founder & CEO" />
                <Field label="Designation" value={m.designation} onChange={(designation) => upd({ designation })} max={120} />
                <Field label="Short bio" type="textarea" rows={4} value={m.bio} onChange={(bio) => upd({ bio })} max={400} hint="About 3 lines are shown on the card." />
              </div>
            </div>
          )}
        />
        {items[0] && (
          <div className="mt-5">
            <span className="label">Preview</span>
            <div className="mx-auto max-w-sm border border-[#EADBAC] bg-[#FAF9F5] p-8 text-center">
              <div className="mx-auto mb-4 h-40 w-40 overflow-hidden border-2 border-gold bg-neutral-900">
                {items[0].image && <img src={assetUrl(items[0].image)} alt="" className="h-full w-full object-cover object-top" />}
              </div>
              <p className="text-[10px] font-bold uppercase tracking-wider text-[#B8860B]">{items[0].role}</p>
              <p className="font-display text-2xl font-bold text-navy">{items[0].name}</p>
              <p className="mt-0.5 text-xs font-semibold text-[#7E591B]">{items[0].designation}</p>
              <p className="mt-3 line-clamp-3 text-xs text-slate-600">{items[0].bio}</p>
              <p className="mt-4 border-t border-[#EADBAC] pt-4 text-[10px] font-bold uppercase tracking-wider">{c.cardFooter}</p>
            </div>
          </div>
        )}
      </Card>
      <SectionHeader c={c} set={set} />
      <Card title="Card footer & button">
        <div className="space-y-4">
          <Field label="Text at the bottom of the card" value={c.cardFooter} onChange={(cardFooter) => set({ cardFooter })} max={60} />
          <ButtonFields c={c} set={set} label="Button" />
        </div>
      </Card>
    </div>
  );
}

/* ---------------- Partners ---------------- */

export function PartnersEditor({ content: c, setContent }) {
  const set = (p) => setContent({ ...c, ...p });
  const [selected, setSelected] = useState(c.items?.[0]?.id ?? null);
  return (
    <div className="space-y-5">
      <Card title="Partner logos">
        <ListEditor
          items={c.items || []}
          selected={selected}
          onSelect={setSelected}
          onChange={(items) => set({ items })}
          addLabel="Add partner"
          makeNew={() => ({ id: newId('partner'), name: 'New partner', label: 'Official FSIA Partner', logo: '' })}
          describe={(p) => ({ title: p.name, subtitle: p.label, image: p.logo })}
          renderForm={(p, upd) => (
            <div className="grid gap-5 md:grid-cols-[220px_minmax(0,1fr)]">
              <ImageUpload label="Logo" value={p.logo} onChange={(logo) => upd({ logo })} size={{ w: 600, h: 400, fit: 'contain' }} hint="Shown whole on white. Transparent PNG works best." />
              <div className="space-y-4">
                <Field label="Name" value={p.name} onChange={(name) => upd({ name })} max={80} />
                <Field label="Label under the name" value={p.label} onChange={(label) => upd({ label })} max={60} />
              </div>
            </div>
          )}
        />
      </Card>
      <SectionHeader c={c} set={set} />
      <Card title="Box under the logos">
        <div className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Title" value={c.noticeTitle} onChange={(noticeTitle) => set({ noticeTitle })} max={100} />
            <Field label="Text" value={c.noticeText} onChange={(noticeText) => set({ noticeText })} max={200} />
          </div>
          <ButtonFields c={c} set={set} label="Button" />
        </div>
      </Card>
    </div>
  );
}

/* ---------------- Final section ---------------- */

export function FinalCtaEditor({ content: c, setContent }) {
  const set = (p) => setContent({ ...c, ...p });
  return (
    <div className="space-y-5">
      <Card title="Text" hint="The dark section at the bottom of the homepage.">
        <div className="space-y-4">
          <Field label="Small label" value={c.badge} onChange={(badge) => set({ badge })} max={80} />
          <Field label="Heading" value={c.heading} onChange={(heading) => set({ heading })} max={80} />
          <Field label="Paragraph" type="textarea" rows={3} value={c.text} onChange={(text) => set({ text })} max={500} hint="Enter = new line. **two stars** around words = bold" />
        </div>
      </Card>
      <Card title="Buttons">
        <div className="space-y-4">
          <ButtonFields c={c} set={set} n="1" label="Gold button" />
          <ButtonFields c={c} set={set} n="2" label="Second button" />
        </div>
      </Card>
      <Card title="Contact boxes">
        <Rows rows={c.items || []} onChange={(items) => set({ items })} addLabel="Add box" max={6}
          makeNew={() => ({ label: '', value: '', link: '', note: '' })}
          columns={[
            { name: 'label', label: 'Small title' },
            { name: 'value', label: 'Main text' },
            { name: 'link', label: 'Link (optional)', placeholder: 'tel:+91… / mailto:…' },
            { name: 'note', label: 'Small note' },
          ]} />
      </Card>
      <Card title="Background photo" hint="Shown very faint behind the section.">
        <div className="max-w-md"><ImageUpload label="Image" value={c.background} onChange={(background) => set({ background })} size={{ w: 1920, h: 1080, fit: 'cover' }} /></div>
      </Card>
    </div>
  );
}

const named = (x, i, f) => `"${x?.[f] || `item ${i + 1}`}"`;

export const TEXT_VALIDATORS = {
  home_hero_text: (c) => [
    !String(c.line1 || c.line2 || c.line3 || '').trim() && 'The heading needs at least one line.',
    !isLink(c.button1Url) && 'Main button link must start with https://, /, tel: or mailto:.',
    !isLink(c.button2Url) && 'Second button link must start with https://, /, tel: or mailto:.',
  ].filter(Boolean),
  home_stats: (c) => (c.items || []).flatMap((s, i) => [
    !String(s.label || '').trim() && `Number ${i + 1} needs a title.`,
    !(Number(s.value) >= 0) && `Number ${named(s, i, 'label')} must be a number.`,
  ]).filter(Boolean),
  home_team: (c) => [
    ...(c.items || []).flatMap((m, i) => [!String(m.name || '').trim() && `Person ${i + 1} needs a name.`]),
    !isLink(c.buttonUrl) && 'Button link must start with https://, /, tel: or mailto:.',
  ].filter(Boolean),
  home_partners: (c) => [
    ...(c.items || []).flatMap((p, i) => [!String(p.name || '').trim() && `Partner ${i + 1} needs a name.`, !p.logo && `Partner ${named(p, i, 'name')} needs a logo.`]),
    !isLink(c.buttonUrl) && 'Button link must start with https://, /, tel: or mailto:.',
  ].filter(Boolean),
  home_final_cta: (c) => [
    !String(c.heading || '').trim() && 'The heading is empty.',
    !isLink(c.button1Url) && 'Gold button link must start with https://, /, tel: or mailto:.',
    !isLink(c.button2Url) && 'Second button link must start with https://, /, tel: or mailto:.',
    ...(c.items || []).flatMap((b, i) => [!isLink(b.link) && `Contact box ${named(b, i, 'label')}: link must start with https://, tel: or mailto:.`]),
  ].filter(Boolean),
};

/* ---------------- Section text of the list tabs ---------------- */

const SECTION_TEXT_FIELDS = {
  calendar_events: [
    { name: 'badge', label: 'Small label' }, { name: 'badge2', label: 'Second label (red)' },
    { name: 'title', label: 'Title', wide: true }, { name: 'subtitle', label: 'Description', type: 'textarea', wide: true },
    { rows: 'highlights', label: 'Key dates next to the title', hint: 'e.g. “Jan 8 · Grand Finale”. The dot colour is a colour code like #D4AF37.' },
    { name: 'helpBadge', label: 'Help box: small label' }, { name: 'helpTitle', label: 'Help box: heading' },
    { name: 'helpText', label: 'Help box: text', type: 'textarea', wide: true },
    { name: 'helpButton1Text', label: 'Help box: gold button text' }, { name: 'helpButton1Url', label: 'Help box: gold button link' },
    { name: 'helpButton2Text', label: 'Help box: second button text' }, { name: 'helpButton2Url', label: 'Help box: second button link' },
  ],
  social_profiles: [
    { name: 'badge', label: 'Small label' }, { name: 'title', label: 'Title' },
    { name: 'subtitle', label: 'Description', type: 'textarea', wide: true },
    { name: 'cardTag', label: 'Text at the bottom of each card' },
    { name: 'note', label: 'Note under the cards (empty = hidden)', type: 'textarea', wide: true },
  ],
  celebrities: [
    { name: 'badge', label: 'Small label' }, { name: 'title', label: 'Title' },
    { name: 'subtitle', label: 'Description', type: 'textarea', wide: true },
    { name: 'cardLeft', label: 'Card bottom: left text' }, { name: 'cardRight', label: 'Card bottom: right text' },
    { name: 'box1Title', label: 'Box 1 title' }, { name: 'box2Title', label: 'Box 2 title' },
    { name: 'box1Text', label: 'Box 1 text', type: 'textarea' }, { name: 'box2Text', label: 'Box 2 text', type: 'textarea' },
  ],
};

/**
 * Headings and fixed text of a list section, saved as content.header next to
 * the items. Only edited fields need saving; the homepage fills in the rest.
 */
export function SectionTextCard({ sectionKey, header, defaults, onChange }) {
  const [open, setOpen] = useState(false);
  const h = { ...defaults, ...(header || {}) };
  const set = (p) => onChange({ ...h, ...p });
  return (
    <section className="card mb-5 p-5">
      <button type="button" className="flex w-full items-center justify-between text-left" onClick={() => setOpen(!open)} aria-expanded={open}>
        <span>
          <span className="font-display text-base font-bold">Section heading &amp; text</span>
          <span className="block text-xs text-slate-500">{h.title} — title, description and the other fixed text of this section</span>
        </span>
        <span className="text-sm font-semibold text-gold-dark">{open ? 'Close ▲' : 'Edit ▼'}</span>
      </button>
      {open && (
        <div className="mt-5 grid gap-4 sm:grid-cols-2">
          {SECTION_TEXT_FIELDS[sectionKey].map((f) =>
            f.rows ? (
              <div key={f.rows} className="sm:col-span-2">
                <span className="label">{f.label}</span>
                {f.hint && <p className="mb-2 text-xs text-slate-500">{f.hint}</p>}
                <Rows rows={h[f.rows] || []} onChange={(v) => set({ [f.rows]: v })} addLabel="Add" max={5}
                  makeNew={() => ({ value: '', label: '', color: '#D4AF37' })}
                  columns={[{ name: 'value', label: 'Bold part' }, { name: 'label', label: 'Text' }, { name: 'color', label: 'Dot colour' }]} />
              </div>
            ) : (
              <div key={f.name} className={f.wide ? 'sm:col-span-2' : ''}>
                <Field label={f.label} type={f.type || 'text'} rows={3} value={h[f.name] ?? ''} onChange={(v) => set({ [f.name]: v })}
                  hint={/Url$/.test(f.name) ? linkHint : f.type === 'textarea' ? 'Enter = new line. **two stars** around words = bold' : undefined} />
              </div>
            ),
          )}
        </div>
      )}
    </section>
  );
}

export const headerProblems = (header) => {
  const h = header || {};
  return Object.entries(h).filter(([k, v]) => /Url$/.test(k) && !isLink(String(v || ''))).map(([k]) => `Section text: "${k}" must start with https://, /, tel: or mailto:.`);
};
