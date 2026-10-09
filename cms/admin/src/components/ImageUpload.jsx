import { useRef, useState } from 'react';
import { api, assetUrl } from '../lib/api';

const ACCEPT = 'image/jpeg,image/png,image/webp';

/**
 * Shows the current image, uploads a new one through /api/upload.php and
 * reports the stored URL via onChange. The URL can also be pasted directly.
 */
export default function ImageUpload({ label = 'Image', value, onChange, hint, aspect = 'aspect-video' }) {
  const input = useRef(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [dragging, setDragging] = useState(false);

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

  return (
    <div>
      <span className="label">{label}</span>
      <div
        className={`relative overflow-hidden rounded-xl border-2 border-dashed ${dragging ? 'border-gold bg-gold/5' : 'border-slate-200 bg-cream'} ${aspect}`}
        onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
        onDragLeave={() => setDragging(false)}
        onDrop={(e) => { e.preventDefault(); setDragging(false); handleFile(e.dataTransfer.files?.[0]); }}
      >
        {value ? (
          <img src={assetUrl(value)} alt="" className="h-full w-full object-cover" />
        ) : (
          <div className="flex h-full flex-col items-center justify-center p-4 text-center text-sm text-slate-500">
            <span className="text-2xl" aria-hidden="true">🖼</span>
            Drop an image here
          </div>
        )}
        {busy && <div className="absolute inset-0 grid place-items-center bg-white/80 text-sm font-semibold">Uploading…</div>}
      </div>

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
      {(error || hint) && <p className={`mt-1 text-xs ${error ? 'text-red-600' : 'text-slate-500'}`}>{error || hint}</p>}
    </div>
  );
}
