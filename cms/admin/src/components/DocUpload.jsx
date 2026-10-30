import { useRef, useState } from 'react';
import { api, assetUrl } from '../lib/api';

/**
 * Uploads a PDF or PowerPoint through /api/upload-doc.php and reports the
 * stored URL via onChange. A link can also be pasted (e.g. Google Drive).
 * kind = 'pdf' | 'ppt' only changes which files the picker offers.
 */
export default function DocUpload({ label, kind = 'pdf', value, onChange, hint }) {
  const input = useRef(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const accept = kind === 'pdf' ? '.pdf,application/pdf' : '.ppt,.pptx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation';
  const name = value ? value.split('?')[0].split('/').pop() || '' : '';

  async function handleFile(file) {
    if (!file) return;
    setError('');
    setBusy(true);
    try {
      const { url } = await api.uploadDoc(file);
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
      <div className="flex flex-wrap items-center gap-2 rounded-xl border-2 border-dashed border-slate-200 bg-cream p-3"
        onDragOver={(e) => e.preventDefault()}
        onDrop={(e) => { e.preventDefault(); handleFile(e.dataTransfer.files?.[0]); }}>
        <span className={`grid h-10 w-10 shrink-0 place-items-center rounded-lg text-[11px] font-bold text-white ${kind === 'pdf' ? 'bg-red-600' : 'bg-orange-500'}`}>
          {kind === 'pdf' ? 'PDF' : 'PPT'}
        </span>
        <div className="min-w-0 flex-1 text-sm">
          {busy ? <span className="font-semibold">Uploading…</span>
            : value ? (
              <>
                <span className="block font-semibold text-emerald-700">✓ File added</span>
                <a href={assetUrl(value)} target="_blank" rel="noreferrer" className="block truncate text-xs text-gold-dark hover:underline" title={name}>Open to check</a>
              </>
            )
            : <span className="text-slate-500">No file. Drop it here or choose one.</span>}
        </div>
        <button type="button" className="btn-ghost" onClick={() => input.current?.click()} disabled={busy}>{value ? 'Replace' : 'Choose file'}</button>
        {value && <button type="button" className="text-xs font-semibold text-red-600" onClick={() => onChange('')}>Remove</button>}
        <input ref={input} type="file" accept={accept} className="hidden" onChange={(e) => handleFile(e.target.files?.[0])} />
      </div>
      <input className="input mt-2 text-xs" placeholder="…or paste a link (https://)" value={value || ''} onChange={(e) => onChange(e.target.value.trim())} />
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
      {hint && <p className="mt-1 text-[11px] text-slate-500">{hint}</p>}
    </div>
  );
}
