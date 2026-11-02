import { useEffect, useRef, useState } from 'react';
import { api } from '../lib/api';
import Modal from './Modal';
import { Field } from './Field';
import ImageUpload from './ImageUpload';
import RichTextEditor from './RichTextEditor';

const SEO = [
  ['meta_title', 'Meta title', 'input', 60, 70],
  ['meta_desc', 'Meta description', 'textarea', 160, 170],
  ['meta_keyword', 'Meta keywords', 'textarea', 0, 0],
  ['h1', 'H1 heading', 'input', 0, 0],
];

/**
 * "Edit here" for one profile: the real biography, photos, banners and SEO text
 * from the database, saved straight back to the same fields the profile page
 * reads. Empty SEO boxes start with text made from the profile's own data.
 */
export default function ProfileEditor({ id, onClose, onSaved }) {
  const [p, setP] = useState(null);
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);
  const [bio, setBio] = useState('');
  const [bioDirty, setBioDirty] = useState(false); // only a typed change is saved (the editor tidies HTML on load)
  const [photos, setPhotos] = useState([]);        // existing paths, in order (first = main)
  const [added, setAdded] = useState([]);          // newly uploaded URLs
  const [banner, setBanner] = useState({ desktop: '', mobile: '' });
  const [seo, setSeo] = useState({});
  const [filled, setFilled] = useState([]);        // SEO boxes prefilled with a suggestion
  const fileInput = useRef(null);
  const [uploading, setUploading] = useState(false);

  useEffect(() => {
    let alive = true;
    api.profileGet(id).then((d) => {
      if (!alive) return;
      setP(d);
      setBio(d.bio.value || '');
      setPhotos(d.photos.map((x) => x.path));
      setBanner({ desktop: d.banners.desktop.url, mobile: d.banners.mobile.url });
      const s = {};
      const pre = [];
      Object.keys(d.seo).forEach((k) => {
        s[k] = d.seo[k];
        if (!s[k].trim() && d.suggest[k]) { s[k] = d.suggest[k]; pre.push(k); }
      });
      setSeo(s);
      setFilled(pre);
    }).catch((e) => alive && setError(e.message));
    return () => { alive = false; };
  }, [id]);

  async function addPhoto(file) {
    if (!file) return;
    setUploading(true);
    try {
      const { url } = await api.upload(file);
      setAdded((a) => [...a, url]);
    } catch (e) { setError(e.message); } finally {
      setUploading(false);
      if (fileInput.current) fileInput.current.value = '';
    }
  }

  const move = (i, to) => setPhotos((list) => {
    const l = [...list];
    const [x] = l.splice(i, 1);
    l.splice(to, 0, x);
    return l;
  });

  async function save() {
    setSaving(true);
    setError('');
    try {
      const body = { id };
      if (bioDirty) body.bio = bio;
      if (added.length || photos.join(',') !== p.photos.map((x) => x.path).join(',')) Object.assign(body, { photos, add_photos: added });
      ['desktop', 'mobile'].forEach((k) => {
        if (banner[k] && banner[k] !== p.banners[k].url) body[k] = banner[k];
        if (!banner[k] && p.banners[k].value) body[`${k}_remove`] = true;
      });
      Object.keys(seo).forEach((k) => { body[k] = seo[k]; });
      const d = await api.profileSave(body);
      onSaved(d);
    } catch (e) { setError(e.message); } finally { setSaving(false); }
  }

  const photoInfo = (path) => p?.photos.find((x) => x.path === path);

  return (
    <Modal open title={p ? `Edit ${p.name}` : 'Edit profile'} onClose={onClose} width="max-w-4xl"
      footer={p && !p.starindia && (
        <>
          <button type="button" className="btn-ghost" onClick={onClose}>Cancel</button>
          <button type="button" className="btn-gold" onClick={save} disabled={saving || uploading}>{saving ? 'Saving…' : 'Save and check again'}</button>
        </>
      )}>
      {error && <p className="mb-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      {!p && !error && <p className="text-sm text-slate-500">Loading the profile…</p>}
      {p && (
        <div className="space-y-6">
          <div className="rounded-xl bg-cream/70 p-3 text-sm">
            <b>#{p.id} {p.name}</b>
            {p.title && <> · {p.title}</>}
            {' · '}{p.category}
            {p.city && <> · {p.city}{p.state ? `, ${p.state}` : ''}</>}
            {' · joined '}{p.joined}
            {p.link && <a href={p.link} target="_blank" rel="noreferrer" className="ml-2 font-semibold text-gold-dark underline">Open live page ↗</a>}
            <p className="mt-1 text-xs text-slate-500">Everything below is this profile&apos;s real data from the database. Saving changes the live profile page.</p>
          </div>

          {p.starindia ? (
            <p className="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
              This profile is edited in the starindia admin, and its page shows that admin&apos;s data. Use “Fix in starindia admin” for it.
            </p>
          ) : (
            <>
              <section>
                <h3 className="mb-2 font-display text-base font-bold">Biography</h3>
                <RichTextEditor value={bio} onChange={(v) => { setBio(v); setBioDirty(true); }} placeholder="Write about this member…" />
              </section>

              <section>
                <h3 className="mb-2 font-display text-base font-bold">Photos <span className="text-xs font-normal text-slate-500">· the first one is the main photo</span></h3>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                  {photos.map((path, i) => {
                    const x = photoInfo(path);
                    return (
                      <div key={path} className="overflow-hidden rounded-xl border border-slate-200">
                        <div className="relative aspect-[3/4] bg-cream">
                          {x?.ok ? <img src={x.url} alt="" className="h-full w-full object-cover" /> : <div className="grid h-full place-items-center p-2 text-center text-xs text-red-600">File missing on the server</div>}
                          {i === 0 && <span className="absolute left-1.5 top-1.5 rounded-full bg-gold px-2 py-0.5 text-[10px] font-bold text-navy">Main</span>}
                        </div>
                        <div className="flex flex-wrap gap-1 p-1.5 text-[11px]">
                          {i > 0 && <button type="button" className="font-semibold text-gold-dark underline" onClick={() => move(i, 0)}>Make main</button>}
                          <button type="button" className="ml-auto font-semibold text-red-600 underline" onClick={() => setPhotos((l) => l.filter((y) => y !== path))}>Remove</button>
                        </div>
                      </div>
                    );
                  })}
                  {added.map((url) => (
                    <div key={url} className="overflow-hidden rounded-xl border border-gold">
                      <div className="relative aspect-[3/4] bg-cream">
                        <img src={url} alt="" className="h-full w-full object-cover" />
                        <span className="absolute left-1.5 top-1.5 rounded-full bg-emerald-500 px-2 py-0.5 text-[10px] font-bold text-white">New</span>
                      </div>
                      <div className="p-1.5 text-right text-[11px]">
                        <button type="button" className="font-semibold text-red-600 underline" onClick={() => setAdded((a) => a.filter((y) => y !== url))}>Remove</button>
                      </div>
                    </div>
                  ))}
                  <button type="button" onClick={() => fileInput.current?.click()} disabled={uploading}
                    className="grid aspect-[3/4] place-items-center rounded-xl border-2 border-dashed border-slate-200 bg-cream text-sm font-semibold text-slate-500 hover:border-gold">
                    {uploading ? 'Uploading…' : '+ Add photo'}
                  </button>
                </div>
                <input ref={fileInput} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={(e) => addPhoto(e.target.files?.[0])} />
              </section>

              <section className="grid gap-4 sm:grid-cols-2">
                <ImageUpload label="Desktop banner" value={banner.desktop} onChange={(v) => setBanner((b) => ({ ...b, desktop: v }))}
                  hint={p.banners.desktop.ok === false ? 'The current file is missing on the server.' : ''} />
                <ImageUpload label="Mobile banner" value={banner.mobile} onChange={(v) => setBanner((b) => ({ ...b, mobile: v }))} aspect="aspect-[4/5]"
                  hint={p.banners.mobile.ok === false ? 'The current file is missing on the server.' : ''} />
              </section>

              <section>
                <h3 className="mb-1 font-display text-base font-bold">SEO</h3>
                {filled.length > 0 && (
                  <p className="mb-3 rounded-lg bg-gold/10 p-2 text-xs text-[#7E591B]">
                    Boxes marked ✦ were empty: they start with text made from this profile&apos;s name, title, category, city and bio. Change it if you like.
                  </p>
                )}
                <div className="grid gap-3">
                  {SEO.filter(([k]) => k in seo).map(([k, label, type, rec, max]) => (
                    <div key={k}>
                      <Field label={`${filled.includes(k) ? '✦ ' : ''}${label}`} type={type} rows={type === 'textarea' ? 2 : undefined}
                        value={seo[k]} max={max || undefined} recommended={rec || undefined}
                        onChange={(v) => { setSeo((s) => ({ ...s, [k]: v })); setFilled((f) => f.filter((x) => x !== k)); }} />
                      {p.suggest[k] && p.suggest[k] !== seo[k] && (
                        <button type="button" className="mt-1 text-left text-[11px] text-gold-dark" onClick={() => setSeo((s) => ({ ...s, [k]: p.suggest[k] }))}>
                          Use: <span className="underline">{p.suggest[k]}</span>
                        </button>
                      )}
                    </div>
                  ))}
                </div>
                <p className="mt-3 text-xs text-slate-500">
                  Profile link: {p.link ? <b>{p.link}</b> : 'none yet'}. A new link needs a page set up in the admin, so it is not changed here.
                </p>
              </section>
            </>
          )}
        </div>
      )}
    </Modal>
  );
}
