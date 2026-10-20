import { useEffect, useRef, useState } from 'react';
import { api, assetUrl } from '../lib/api';

const ACCEPT = 'image/jpeg,image/png,image/webp';

function gcd(a, b) { return b ? gcd(b, a % b) : a; }
function ratioLabel(w, h) {
  const g = gcd(w, h);
  const [a, b] = [w / g, h / g];
  return a <= 21 && b <= 21 ? `${a}:${b}` : '';
}
const withRatio = (w, h) => (ratioLabel(w, h) ? ` (${ratioLabel(w, h)})` : '');

/**
 * Shows the current image, uploads a new one through /api/upload.php and
 * reports the stored URL via onChange. The URL can also be pasted directly.
 *
 * size = { w, h, fit } is the recommended size for this spot on the website:
 * shown above the box, used for the box's shape, and checked against the
 * chosen image. fit 'cover' = the site fills its box (a different shape gets
 * cut); 'contain' = shown whole (a different shape gets empty bands).
 */
export default function ImageUpload({ label = 'Image', value, onChange, hint, aspect, size }) {
  const input = useRef(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [dragging, setDragging] = useState(false);
  const [natural, setNatural] = useState(null); // { w, h } of the current image

  // Measure the current image (uploaded or pasted) to check it against `size`.
  useEffect(() => {
    setNatural(null);
    if (!value) return undefined;
    let alive = true;
    const img = new Image();
    img.referrerPolicy = 'no-referrer';
    img.onload = () => alive && setNatural({ w: img.naturalWidth, h: img.naturalHeight });
    img.onerror = () => alive && setNatural({ broken: true });
    img.src = assetUrl(value);
    return () => { alive = false; };
  }, [value]);

  async function handleFile(file) {
    if (!file) return;
    if (!ACCEPT.split(',').includes(file.type)) {
      setError('Please choose a JPEG, PNG or WebP image.');
      return;
    }
    setError('');
    setBusy(true);
    try {
      const { url } = await api.upload(file);
      onChange(url);
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
      if (input.current) input.current.value = '';
    }
  }

  const boxShape = aspect || (size ? '' : 'aspect-video');
  // Same shape as on the website, at most 360 px tall so portrait boxes stay compact.
  const boxStyle = size && !aspect
    ? { aspectRatio: `${size.w} / ${size.h}`, width: `min(100%, ${Math.round((360 * size.w) / size.h)}px)` }
    : undefined;

  let check = null;
  if (natural?.broken) {
    check = { tone: 'text-red-600', text: 'This image does not load. Upload it again, or check the link.' };
  } else if (natural && size) {
    const want = size.w / size.h;
    const got = natural.w / natural.h;
    const shapeOk = Math.abs(got - want) / want <= 0.04;
    const bigEnough = natural.w >= size.w * 0.6;
    const yours = `Your image: ${natural.w} × ${natural.h} px${withRatio(natural.w, natural.h)}.`;
    if (shapeOk && bigEnough) check = { tone: 'text-emerald-700', text: `✓ Right size. ${yours}` };
    else if (!shapeOk) {
      check = {
        tone: 'text-amber-700',
        text: `⚠ ${yours} Different shape from ${size.w} × ${size.h}: ${size.fit === 'cover' ? 'the edges will be cut off on the website' : 'it will show with empty bands around it'}.`,
      };
    } else check = { tone: 'text-amber-700', text: `⚠ ${yours} Too small; it may look blurry. Use at least ${Math.round(size.w * 0.6)} px wide.` };
  }

  return (
    <div>
      <div className="flex flex-wrap items-baseline justify-between gap-2">
        <span className="label">{label}</span>
        {size && (
          <span className="mb-1.5 rounded-full bg-gold/15 px-2.5 py-0.5 text-[11px] font-bold text-[#7E591B]">
            Size: {size.w} × {size.h} px{withRatio(size.w, size.h)}
          </span>
        )}
      </div>
      <div
        className={`relative overflow-hidden rounded-xl border-2 border-dashed ${dragging ? 'border-gold bg-gold/5' : 'border-slate-200 bg-cream'} ${boxShape}`}
        style={boxStyle}
        onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
        onDragLeave={() => setDragging(false)}
        onDrop={(e) => { e.preventDefault(); setDragging(false); handleFile(e.dataTransfer.files?.[0]); }}
      >
        {value && !natural?.broken ? (
          <img src={assetUrl(value)} alt="" className={`h-full w-full ${size?.fit === 'cover' ? 'object-cover' : 'object-contain'}`} />
        ) : (
          <div className="flex h-full flex-col items-center justify-center p-4 text-center text-sm text-slate-500">
            <span className="text-2xl" aria-hidden="true">🖼</span>
            {value ? 'Image not loading' : 'Drop an image here'}
            {size && <span className="mt-1 text-xs font-semibold">{size.w} × {size.h} px</span>}
          </div>
        )}
        {busy && <div className="absolute inset-0 grid place-items-center bg-white/80 text-sm font-semibold">Uploading…</div>}
      </div>
      {size && value && !natural?.broken && (
        <p className="mt-1 text-[11px] text-slate-500">
          {size.fit === 'cover' ? 'Shown exactly as it will be cut on the website.' : 'Shown whole, as on the website.'}
        </p>
      )}

      <div className="mt-2 flex flex-wrap gap-2">
        <button type="button" className="btn-ghost !py-1.5" onClick={() => input.current?.click()} disabled={busy}>
          {value ? 'Replace' : 'Upload'}
        </button>
        {value && (
          <button type="button" className="btn-danger !py-1.5" onClick={() => onChange('')} disabled={busy}>Remove</button>
        )}
      </div>
      <input ref={input} type="file" accept={ACCEPT} className="hidden" onChange={(e) => handleFile(e.target.files?.[0])} />
      <input
        className="input mt-2 !py-1.5 text-xs"
        placeholder="…or paste an image URL"
        value={value || ''}
        onChange={(e) => onChange(e.target.value)}
      />
      {check && <p className={`mt-1 text-xs font-semibold ${check.tone}`}>{check.text}</p>}
      {(error || hint) && <p className={`mt-1 text-xs ${error ? 'text-red-600' : 'text-slate-500'}`}>{error || hint}</p>}
    </div>
  );
}
