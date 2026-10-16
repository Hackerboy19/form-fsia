import { useEffect, useState } from 'react';
import { api } from '../lib/api';
import { useAuth } from '../lib/auth';
import { useToast } from '../lib/toast';
import Modal from '../components/Modal';

const BAND_CLS = {
  excellent: 'bg-emerald-100 text-emerald-800', good: 'bg-sky-100 text-sky-800',
  fair: 'bg-amber-100 text-amber-800', poor: 'bg-red-100 text-red-700',
};
const short = (v) => (v ? (v.length > 90 ? `${v.slice(0, 90)}…` : v) : 'empty');

/** Fix one profile: SEO fields, bio sections and banners, with undo. Writes to the live profile. */
export default function ProfileFix({ profile, onClose, onSaved }) {
  const { can } = useAuth();
  const notify = useToast();
  const editable = can('profiles.edit');
  const [data, setData] = useState(null);
  const [form, setForm] = useState({});
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});
  const [confirm, setConfirm] = useState(false);
  const [busy, setBusy] = useState('');

  function take(d) {
    setData(d);
    setForm(Object.fromEntries(d.fields.map((f) => [f.key, f.value])));
    onSaved?.(d.report);
  }
  useEffect(() => {
    api.getProfile(profile.id).then((d) => { setData(d); setForm(Object.fromEntries(d.fields.map((f) => [f.key, f.value]))); })
      .catch((e) => setError(e.message));
  }, [profile.id]);

  const changed = data ? data.fields.filter((f) => form[f.key] !== f.value) : [];

  async function save() {
    setBusy('save'); setError(''); setFieldErrors({});
    try {
      const d = await api.saveProfile(profile.id, Object.fromEntries(changed.map((f) => [f.key, form[f.key]])));
      take(d);
      notify(d.saved.length ? `Saved ${d.saved.length} field${d.saved.length > 1 ? 's' : ''} on the live profile` : 'Nothing changed');
    } catch (e) {
      setError(e.message); setFieldErrors(e.fields || {});
    } finally { setBusy(''); setConfirm(false); }
  }

  async function banner(which, file) {
    if (!file) return;
    setBusy(which); setError('');
    try {
      take(await api.uploadBanner(profile.id, which, file));
      notify(`New ${which} banner is live`);
    } catch (e) { setError(e.message); } finally { setBusy(''); }
  }

  async function undo(batch) {
    if (!window.confirm('Put the old values of this save back on the live profile?')) return;
    setBusy(batch); setError('');
    try {
      take(await api.undoProfile(batch));
      notify('Undone');
    } catch (e) { setError(e.message); } finally { setBusy(''); }
  }

  const r = data?.report;
  return (
    <Modal open title={`Fix profile: ${profile.name}`} onClose={onClose} width="max-w-4xl"
      footer={editable && data && (
        <>
          <span className="mr-auto self-center text-xs text-slate-500">{changed.length ? `${changed.length} unsaved change${changed.length > 1 ? 's' : ''}` : 'No changes yet'}</span>
          <button type="button" className="btn-ghost" onClick={() => setForm(Object.fromEntries(data.fields.map((f) => [f.key, f.value])))} disabled={!changed.length || !!busy}>Reset</button>
          <button type="button" className="btn-primary" onClick={() => setConfirm(true)} disabled={!changed.length || !!busy}>Save to live profile</button>
        </>
      )}>
      {error && <p className="mb-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      {!data && !error && <div className="h-64 animate-pulse rounded-xl bg-cream" />}
      {data && (
        <div className="space-y-5">
          <div className="flex flex-wrap items-center gap-3 rounded-xl bg-cream/60 p-3 text-sm">
            <span className={`rounded-full px-2.5 py-1 text-sm font-bold ${BAND_CLS[r.band]}`}>{r.score}/100</span>
            <span className="text-slate-600">#{r.id} · {r.category} · {r.photos} photo{r.photos === 1 ? '' : 's'}</span>
            {r.link && <a href={r.link} target="_blank" rel="noreferrer" className="ml-auto font-semibold text-gold-dark underline">Open live page ↗</a>}
            {Object.keys(r.problems).length > 0 && (
              <p className="w-full text-xs text-slate-600">Missing: {Object.values(r.problems).flat().join(' · ')}</p>
            )}
          </div>
          {!editable && <p className="rounded-xl bg-amber-50 p-3 text-xs text-amber-800">You can look, but your role cannot change profiles.</p>}

          <section>
            <h3 className="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Banners</h3>
            <div className="grid gap-3 sm:grid-cols-2">
              {['desktop', 'mobile'].filter((w) => data.banners[w]).map((w) => {
                const b = data.banners[w];
                return (
                  <div key={w} className="rounded-xl border border-slate-100 p-3">
                    <p className="text-sm font-semibold capitalize">{w} banner</p>
                    {b.value && b.on_server ? (
                      <img src={b.url} alt="" className={`mt-2 w-full rounded-lg bg-slate-100 object-cover ${w === 'desktop' ? 'aspect-[3/1]' : 'aspect-[4/5] max-h-48 w-auto'}`} />
                    ) : (
                      <p className="mt-2 rounded-lg bg-red-50 p-3 text-xs text-red-700">{b.value ? `File missing on the server: ${b.value}` : 'No banner'}</p>
                    )}
                    {editable && (
                      <label className="btn-ghost mt-2 inline-flex cursor-pointer !py-1.5 text-xs">
                        {busy === w ? 'Uploading…' : b.value ? 'Replace banner' : 'Upload banner'}
                        <input type="file" accept="image/jpeg,image/png,image/webp" className="sr-only" disabled={!!busy}
                          onChange={(e) => { banner(w, e.target.files?.[0]); e.target.value = ''; }} />
                      </label>
                    )}
                    <p className="mt-1 text-[11px] text-slate-400">JPEG, PNG or WebP. {w === 'desktop' ? 'Wide image, e.g. 1920 × 600.' : 'Tall image, e.g. 800 × 1000.'} Goes live at once.</p>
                  </div>
                );
              })}
            </div>
          </section>

          <section className="space-y-4">
            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">SEO and bio</h3>
            {data.fields.map((f) => {
              const v = form[f.key] ?? '';
              const len = v.length;
              const over = f.soft && len > f.soft;
              const Tag = f.kind === 'line' ? 'input' : 'textarea';
              return (
                <label key={f.key} className="block">
                  <span className="label flex items-center justify-between gap-2">
                    <span>{f.label}{!f.value.trim() && <span className="ml-2 rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-red-700">missing</span>}{v !== f.value && <span className="ml-2 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-800">changed</span>}</span>
                    <span className={`text-[11px] font-normal ${over ? 'text-amber-700' : 'text-slate-400'}`}>{len}{f.soft ? ` / ${f.soft} recommended` : ''}</span>
                  </span>
                  <Tag className={`input ${f.kind === 'html' ? 'font-mono text-xs' : ''}`} rows={f.kind === 'html' ? 6 : 3} value={v} readOnly={!editable}
                    onChange={(e) => setForm((p) => ({ ...p, [f.key]: e.target.value }))} />
                  {f.kind === 'html' && <span className="mt-1 block text-[11px] text-slate-400">Simple HTML is kept (&lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, lists, links). Scripts and styles are removed.</span>}
                  {fieldErrors[f.key] && <span className="mt-1 block text-xs text-red-600">{fieldErrors[f.key]}</span>}
                </label>
              );
            })}
          </section>

          {data.history.length > 0 && (
            <section>
              <h3 className="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Recent fixes from this admin</h3>
              <ul className="space-y-2">
                {data.history.map((h) => (
                  <li key={h.batch} className="rounded-xl border border-slate-100 p-3 text-xs">
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="font-semibold">{h.at.slice(0, 16)}</span><span className="text-slate-500">by {h.by || 'admin'}</span>
                      {h.undone ? <span className="rounded bg-slate-100 px-1.5 py-0.5 font-bold text-slate-500">UNDONE</span>
                        : editable && <button type="button" className="ml-auto font-semibold text-gold-dark underline" disabled={!!busy} onClick={() => undo(h.batch)}>{busy === h.batch ? 'Undoing…' : 'Undo'}</button>}
                    </div>
                    <ul className="mt-1 space-y-0.5 text-slate-600">
                      {h.fields.map((x) => <li key={x.field} className="break-words"><b>{x.field}</b>: <span className="text-red-700/80 line-through">{short(x.before)}</span> → <span className="text-emerald-800">{short(x.after)}</span></li>)}
                    </ul>
                  </li>
                ))}
              </ul>
            </section>
          )}
        </div>
      )}

      <Modal open={confirm} title="Save to the live profile?" onClose={() => setConfirm(false)} width="max-w-lg"
        footer={<>
          <button type="button" className="btn-ghost" onClick={() => setConfirm(false)}>Cancel</button>
          <button type="button" className="btn-primary" onClick={save} disabled={busy === 'save'}>{busy === 'save' ? 'Saving…' : 'Save now'}</button>
        </>}>
        <p className="text-sm text-slate-600">These change on the live profile right away. The old values are kept, so you can undo.</p>
        <ul className="mt-3 space-y-2 text-xs">
          {changed.map((f) => (
            <li key={f.key} className="break-words rounded-lg bg-cream/60 p-2"><b>{f.label}</b><br /><span className="text-red-700/80 line-through">{short(f.value)}</span> → <span className="text-emerald-800">{short(form[f.key])}</span></li>
          ))}
        </ul>
      </Modal>
    </Modal>
  );
}
