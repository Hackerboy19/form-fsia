import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { api, assetUrl } from '../lib/api';
import { PageHeader } from '../components/Layout';
import { Field, Toggle } from '../components/Field';
import ImageUpload from '../components/ImageUpload';
import RichTextEditor from '../components/RichTextEditor';
import { useToast } from '../lib/toast';

const TYPES = ['All', 'Standard', 'Special', 'Top10'];
const TYPE_LABEL = { Standard: 'Standard', Special: 'Special Coverage', Top10: 'Top 10' };
const today = () => new Date().toISOString().slice(0, 10);
const slugify = (s) => s.normalize('NFKD').replace(/[^\w\s-]/g, '').trim().toLowerCase().replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 180);

// ---------------------------------------------------------------- list ----

export function NewsList() {
  const [type, setType] = useState('All');
  const [q, setQ] = useState('');
  const [page, setPage] = useState(1);
  const [data, setData] = useState(null);
  const notify = useToast();

  useEffect(() => {
    let cancelled = false;
    const t = setTimeout(() => {
      api.listNews({ type: type === 'All' ? '' : type, q, page, limit: 15 })
        .then((d) => !cancelled && setData(d))
        .catch((e) => notify(e.message, 'error'));
    }, q ? 250 : 0);
    return () => { cancelled = true; clearTimeout(t); };
  }, [type, q, page, notify]);

  const pages = data ? Math.max(1, Math.ceil(data.total / data.limit)) : 1;

  return (
    <>
      <PageHeader
        title="News Manager"
        description="Standard articles appear on news-coverage.php, Special Coverage on special-news-coverage.php."
        actions={<Link to="/news/new" className="btn-gold">+ New article</Link>}
      />
      <div className="mb-4 flex flex-wrap items-center gap-2">
        {TYPES.map((t) => (
          <button key={t} type="button" onClick={() => { setType(t); setPage(1); }}
            className={`rounded-full border px-3.5 py-1.5 text-sm font-semibold ${t === type ? 'border-navy bg-navy text-white' : 'border-slate-200 bg-white hover:border-gold'}`}>
            {t === 'All' ? 'All' : TYPE_LABEL[t]}
          </button>
        ))}
        <input className="input ml-auto !w-full !py-2 sm:!w-64" placeholder="Search titles…" value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }} aria-label="Search articles" />
      </div>

      <div className="card divide-y divide-slate-100">
        {data === null && <div className="h-40 animate-pulse bg-cream" />}
        {data && data.items.length === 0 && <p className="p-10 text-center text-slate-500">No articles yet.</p>}
        {data?.items.map((n) => {
          const scheduled = n.published_date > today();
          return (
            <Link key={n.id} to={`/news/${n.id}`} className="flex items-center gap-4 p-4 hover:bg-cream/60">
              <div className="h-14 w-20 flex-none overflow-hidden rounded-lg bg-cream-2">
                {n.featured_image_url && <img src={assetUrl(n.featured_image_url)} alt="" className="h-full w-full object-cover" loading="lazy" />}
              </div>
              <div className="min-w-0 flex-1">
                <p className="truncate font-semibold">{n.title}</p>
                <p className="truncate text-xs text-slate-500">/{n.slug}</p>
              </div>
              <span className={`hidden rounded-full px-2.5 py-1 text-xs font-semibold sm:inline ${n.news_type === 'Special' ? 'bg-gold text-navy' : 'bg-cream-2 text-gold-bronze'}`}>{TYPE_LABEL[n.news_type]}</span>
              <span className={`w-28 text-right text-xs tabular-nums ${scheduled ? 'font-semibold text-amber-600' : 'text-slate-500'}`}>{scheduled ? `Scheduled ${n.published_date}` : n.published_date}</span>
            </Link>
          );
        })}
      </div>

      {pages > 1 && (
        <div className="mt-4 flex items-center justify-center gap-3 text-sm">
          <button type="button" className="btn-ghost !py-1.5" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>← Prev</button>
          <span>Page {page} of {pages}</span>
          <button type="button" className="btn-ghost !py-1.5" disabled={page >= pages} onClick={() => setPage((p) => p + 1)}>Next →</button>
        </div>
      )}
    </>
  );
}

// -------------------------------------------------------------- editor ----

const BLANK = { title: '', slug: '', news_type: 'Standard', featured_image_url: '', content_html: '', published_date: today() };

export function NewsEditor() {
  const { id } = useParams();
  const isNew = id === 'new';
  const navigate = useNavigate();
  const notify = useToast();
  const [form, setForm] = useState(isNew ? BLANK : null);
  const [slugTouched, setSlugTouched] = useState(!isNew);
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (isNew) { setForm(BLANK); setSlugTouched(false); return undefined; }
    let cancelled = false;
    api.getNews(id)
      .then((n) => !cancelled && setForm({ ...BLANK, ...n }))
      .catch((e) => { notify(e.message, 'error'); navigate('/news'); });
    return () => { cancelled = true; };
  }, [id, isNew, navigate, notify]);

  const set = useCallback((k) => (v) => setForm((f) => ({ ...f, [k]: v })), []);
  const setTitle = (v) => setForm((f) => ({ ...f, title: v, slug: slugTouched ? f.slug : slugify(v) }));

  async function save(e) {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    const body = {
      title: form.title,
      slug: form.slug,
      news_type: form.news_type,
      featured_image_url: form.featured_image_url,
      content_html: form.content_html,
      published_date: form.published_date,
    };
    try {
      const saved = isNew ? await api.createNews(body) : await api.updateNews(id, body);
      notify(isNew ? 'Article published' : 'Article saved');
      if (isNew) navigate(`/news/${saved.id}`, { replace: true });
      else setForm({ ...BLANK, ...saved });
    } catch (err) {
      setErrors(err.fields || {});
      notify(err.message, 'error');
    } finally {
      setSaving(false);
    }
  }

  async function remove() {
    if (!window.confirm(`Delete "${form.title}"? This cannot be undone.`)) return;
    try {
      await api.deleteNews(id);
      notify('Article deleted');
      navigate('/news');
    } catch (e) {
      notify(e.message, 'error');
    }
  }

  if (!form) return <div className="card h-96 animate-pulse bg-cream" />;
  const isTop10 = form.news_type === 'Top10';

  return (
    <form onSubmit={save}>
      <PageHeader
        title={isNew ? 'New article' : 'Edit article'}
        description={<Link to="/news" className="text-gold-dark hover:underline">← All articles</Link>}
        actions={
          <>
            {!isNew && <button type="button" className="btn-danger" onClick={remove}>Delete</button>}
            <button type="submit" className="btn-gold" disabled={saving}>{saving ? 'Saving…' : isNew ? 'Publish' : 'Save changes'}</button>
          </>
        }
      />

      <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div className="space-y-5">
          <div className="card space-y-4 p-5">
            <Field label="Title *" value={form.title} onChange={setTitle} max={255} error={errors.title} required />
            <Field
              label="URL slug"
              value={form.slug}
              onChange={(v) => { setSlugTouched(true); set('slug')(slugify(v)); }}
              max={191}
              error={errors.slug}
              hint="Made from the title. Must be unique; the server adds -2, -3 if needed."
            />
          </div>
          <div>
            <span className="label">Article</span>
            <RichTextEditor value={form.content_html} onChange={set('content_html')} />
            {errors.content_html && <p className="mt-1 text-xs text-red-600">{errors.content_html}</p>}
          </div>
        </div>

        <aside className="space-y-5 lg:sticky lg:top-8 lg:self-start">
          <div className="card space-y-4 p-5">
            <Toggle
              checked={form.news_type === 'Special'}
              disabled={isTop10}
              onChange={(on) => set('news_type')(on ? 'Special' : 'Standard')}
              label="Special Coverage"
              description={form.news_type === 'Special' ? 'Shown on special-news-coverage.php' : 'Standard: shown on news-coverage.php'}
            />
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" className="h-4 w-4 accent-[#D4AF37]" checked={isTop10} onChange={(e) => set('news_type')(e.target.checked ? 'Top10' : 'Standard')} />
              Add to the Top 10 list instead
            </label>
            {errors.news_type && <p className="text-xs text-red-600">{errors.news_type}</p>}
            <Field label="Publish date" type="date" value={form.published_date} onChange={set('published_date')} error={errors.published_date}
              hint={form.published_date > today() ? 'Future date: hidden from the site until then.' : undefined} />
          </div>
          <div className="card p-5">
            <ImageUpload label="Featured image" value={form.featured_image_url} onChange={set('featured_image_url')} hint={errors.featured_image_url || '1200 × 630 recommended'} aspect="aspect-[1200/630]" />
          </div>
        </aside>
      </div>
    </form>
  );
}
