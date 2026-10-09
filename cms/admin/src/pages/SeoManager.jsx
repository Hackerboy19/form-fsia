import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { api, assetUrl } from '../lib/api';
import { PAGES } from '../lib/pages';
import { PageHeader } from '../components/Layout';
import { Field } from '../components/Field';
import ImageUpload from '../components/ImageUpload';
import { useToast } from '../lib/toast';

const EMPTY = { meta_title: '', meta_description: '', meta_keywords: '', og_title: '', og_description: '', og_image_url: '', canonical_url: '' };

export default function SeoManager() {
  const [params, setParams] = useSearchParams();
  const page = params.get('page') || PAGES[0].slug;
  const [form, setForm] = useState(EMPTY);
  const [saved, setSaved] = useState(EMPTY);
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const notify = useToast();

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setErrors({});
    api.getSeo(page)
      .then((row) => {
        if (cancelled) return;
        const clean = Object.fromEntries(Object.keys(EMPTY).map((k) => [k, row[k] ?? '']));
        setForm(clean);
        setSaved(clean);
      })
      .catch((e) => notify(e.message, 'error'))
      .finally(() => !cancelled && setLoading(false));
    return () => { cancelled = true; };
  }, [page, notify]);

  const dirty = JSON.stringify(form) !== JSON.stringify(saved);
  const set = (k) => (v) => setForm((f) => ({ ...f, [k]: v }));

  async function save(e) {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    try {
      const row = await api.saveSeo(page, form);
      const clean = Object.fromEntries(Object.keys(EMPTY).map((k) => [k, row[k] ?? '']));
      setForm(clean);
      setSaved(clean);
      notify('SEO saved');
    } catch (err) {
      setErrors(err.fields || {});
      notify(err.message, 'error');
    } finally {
      setSaving(false);
    }
  }

  function switchPage(slug) {
    if (dirty && !window.confirm('You have unsaved changes. Switch page anyway?')) return;
    setParams({ page: slug });
  }

  const meta = PAGES.find((p) => p.slug === page);
  const shareTitle = form.og_title || form.meta_title;
  const shareDesc = form.og_description || form.meta_description;

  return (
    <>
      <PageHeader title="Global SEO Manager" description="Search and social-sharing tags for each page." />

      <div className="mb-6 flex flex-wrap gap-2" role="tablist">
        {PAGES.map((p) => (
          <button
            key={p.slug}
            type="button"
            role="tab"
            aria-selected={p.slug === page}
            onClick={() => switchPage(p.slug)}
            className={`rounded-full border px-4 py-2 text-sm font-semibold transition ${
              p.slug === page ? 'border-navy bg-navy text-white' : 'border-slate-200 bg-white hover:border-gold'
            }`}
          >
            {p.label} <span className="ml-1 text-xs opacity-60">{p.file}</span>
          </button>
        ))}
      </div>

      <form onSubmit={save} className="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div className={`space-y-6 ${loading ? 'pointer-events-none opacity-50' : ''}`}>
          <section className="card space-y-4 p-5">
            <h2 className="font-display text-lg font-bold">Search engines</h2>
            <Field label="Meta title" value={form.meta_title} onChange={set('meta_title')} max={255} recommended={60} error={errors.meta_title} hint="Shown as the blue link in Google. Aim for 50–60 characters." />
            <Field label="Meta description" type="textarea" rows={3} value={form.meta_description} onChange={set('meta_description')} max={500} recommended={160} error={errors.meta_description} hint="The grey text under the link. Aim for 120–160 characters." />
            <Field label="Meta keywords" value={form.meta_keywords} onChange={set('meta_keywords')} max={500} error={errors.meta_keywords} hint="Comma separated. Google ignores these, but other tools still read them." />
            <Field label="Canonical URL" type="url" value={form.canonical_url} onChange={set('canonical_url')} max={512} error={errors.canonical_url} placeholder={`https://www.fsia.in/${meta?.file === 'index.php' ? '' : meta?.file}`} />
          </section>

          <section className="card space-y-4 p-5">
            <h2 className="font-display text-lg font-bold">Social sharing (Open Graph)</h2>
            <Field label="OG title" value={form.og_title} onChange={set('og_title')} max={255} recommended={70} error={errors.og_title} hint="Leave empty to reuse the meta title." />
            <Field label="OG description" type="textarea" rows={3} value={form.og_description} onChange={set('og_description')} max={500} recommended={200} error={errors.og_description} hint="Leave empty to reuse the meta description." />
            <ImageUpload label="OG image" value={form.og_image_url} onChange={set('og_image_url')} hint="1200 × 630 works best on WhatsApp, Facebook and X." aspect="aspect-[1200/630]" />
            {errors.og_image_url && <p className="text-xs text-red-600">{errors.og_image_url}</p>}
          </section>
        </div>

        <aside className="space-y-4 lg:sticky lg:top-8 lg:self-start">
          <div className="card p-5">
            <p className="label">Google preview</p>
            <p className="truncate text-xs text-emerald-700">{form.canonical_url || 'https://www.fsia.in/'}</p>
            <p className="mt-1 line-clamp-1 text-lg text-[#1a0dab]">{form.meta_title || 'Page title'}</p>
            <p className="line-clamp-2 text-sm text-slate-600">{form.meta_description || 'Page description appears here.'}</p>
          </div>
          <div className="card overflow-hidden">
            <p className="label px-5 pt-4">Share preview</p>
            <div className="aspect-[1200/630] bg-cream">
              {form.og_image_url && <img src={assetUrl(form.og_image_url)} alt="" className="h-full w-full object-cover" />}
            </div>
            <div className="bg-slate-50 px-4 py-3">
              <p className="text-[11px] uppercase text-slate-500">fsia.in</p>
              <p className="line-clamp-1 font-semibold">{shareTitle || 'Share title'}</p>
              <p className="line-clamp-2 text-sm text-slate-500">{shareDesc || 'Share description'}</p>
            </div>
          </div>
          <div className="flex gap-2">
            <button type="submit" className="btn-gold flex-1" disabled={saving || !dirty || loading}>{saving ? 'Saving…' : 'Save SEO'}</button>
            <button type="button" className="btn-ghost" disabled={!dirty || saving} onClick={() => { setForm(saved); setErrors({}); }}>Reset</button>
          </div>
        </aside>
      </form>
    </>
  );
}
