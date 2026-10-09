import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { api } from '../lib/api';
import { PAGES, SECTION_SCHEMAS } from '../lib/pages';
import { PageHeader } from '../components/Layout';
import { Field } from '../components/Field';
import ImageUpload from '../components/ImageUpload';
import { useToast } from '../lib/toast';

/** One editable block (hero, story ...) with its own Save button. */
function SectionCard({ page, schema, initial }) {
  const blank = { content: Object.fromEntries(schema.fields.map((f) => [f.name, ''])), image_url: '' };
  const start = initial
    ? { content: { ...blank.content, ...initial.content }, image_url: initial.image_url || '' }
    : blank;

  const [form, setForm] = useState(start);
  const [saved, setSaved] = useState(start);
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);
  const notify = useToast();
  const dirty = JSON.stringify(form) !== JSON.stringify(saved);

  const setField = (name) => (v) => setForm((f) => ({ ...f, content: { ...f.content, [name]: v } }));

  async function save() {
    const missing = schema.fields.filter((f) => f.required && !String(form.content[f.name] || '').trim());
    const tooLong = schema.fields.filter((f) => f.max && String(form.content[f.name] || '').length > f.max);
    const local = Object.fromEntries([
      ...missing.map((f) => [f.name, 'Required']),
      ...tooLong.map((f) => [f.name, `At most ${f.max} characters`]),
    ]);
    if (Object.keys(local).length) {
      setErrors(local);
      return;
    }
    setSaving(true);
    setErrors({});
    try {
      const res = await api.saveSection(page, schema.key, form);
      const next = { content: { ...blank.content, ...res.content }, image_url: res.image_url || '' };
      setForm(next);
      setSaved(next);
      notify(`${schema.title} saved`);
    } catch (e) {
      setErrors({ ...(e.fields || {}), ...(e.fields?.content ? { _: e.fields.content } : {}) });
      notify(e.message, 'error');
    } finally {
      setSaving(false);
    }
  }

  return (
    <section className="card p-5">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="font-display text-lg font-bold">{schema.title}</h2>
          <p className="text-xs text-slate-400">section key: <code>{schema.key}</code>{initial?.updated_at && ` · last saved ${initial.updated_at}`}</p>
        </div>
        <div className="flex items-center gap-2">
          {dirty && <span className="text-xs font-semibold text-amber-600">Unsaved changes</span>}
          <button type="button" className="btn-ghost !py-2" disabled={!dirty || saving} onClick={() => { setForm(saved); setErrors({}); }}>Reset</button>
          <button type="button" className="btn-primary !py-2" disabled={!dirty || saving} onClick={save}>{saving ? 'Saving…' : 'Save'}</button>
        </div>
      </div>
      {errors._ && <p className="mb-3 text-sm text-red-600">{errors._}</p>}

      <div className={`grid gap-5 ${schema.image ? 'md:grid-cols-[1fr_280px]' : ''}`}>
        <div className="space-y-4">
          {schema.fields.map((f) => (
            <Field
              key={f.name}
              label={f.label + (f.required ? ' *' : '')}
              type={f.type === 'textarea' ? 'textarea' : f.type === 'url' ? 'url' : 'text'}
              rows={f.rows}
              value={form.content[f.name]}
              onChange={setField(f.name)}
              max={f.max}
              placeholder={f.placeholder}
              error={errors[f.name]}
            />
          ))}
        </div>
        {schema.image && (
          <div>
            <ImageUpload label={schema.image.label} hint={schema.image.hint || errors.image_url} value={form.image_url} onChange={(v) => setForm((f) => ({ ...f, image_url: v }))} />
          </div>
        )}
      </div>
    </section>
  );
}

export default function PageEditor() {
  const { slug } = useParams();
  const page = PAGES.find((p) => p.slug === slug);
  const schemas = SECTION_SCHEMAS[slug] || [];
  const [sections, setSections] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let cancelled = false;
    setSections(null);
    api.getSections(slug)
      .then((s) => !cancelled && setSections(s || {}))
      .catch((e) => !cancelled && setError(e.message));
    return () => { cancelled = true; };
  }, [slug]);

  if (!page || !schemas.length) return <p className="card p-5">This page has no editable sections.</p>;

  return (
    <>
      <PageHeader title={`${page.label} page`} description={`Text and images for ${page.file}. Each section saves on its own.`} />
      {error && <p className="card mb-4 p-4 text-sm text-red-600">{error}</p>}
      <div className="space-y-6">
        {sections === null
          ? schemas.map((s) => <div key={s.key} className="card h-64 animate-pulse bg-cream" />)
          : schemas.map((s) => <SectionCard key={`${slug}-${s.key}`} page={slug} schema={s} initial={sections[s.key]} />)}
      </div>
    </>
  );
}
