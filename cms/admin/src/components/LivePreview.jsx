import { useEffect, useRef, useState } from 'react';
import { assetUrl } from '../lib/api';

/*
 * Live preview of a public page, next to its editor. The real page is loaded in
 * an iframe (same site as the admin, so its DOM is reachable) and the unsaved
 * texts and photos are written into the elements the page marks with
 *   data-cms="section.field"      -> text (Enter = new line)
 *   data-cms-img="section"        -> the section's photo
 *   data-cms-href="section.field" -> a link
 * An empty field puts back what the page showed when it loaded.
 * Nothing is saved or published by the preview.
 */

const WIDTHS = { desktop: 1280, mobile: 390 };

function apply(doc, drafts) {
  if (!doc) return;
  doc.querySelectorAll('[data-cms]').forEach((el) => {
    // Keep the page's own markup (line breaks included) to restore when a box is cleared.
    if (el.dataset.cmsOrig === undefined) el.dataset.cmsOrig = el.innerHTML;
    const [key, field] = el.dataset.cms.split('.');
    const v = drafts[key]?.content?.[field];
    if (typeof v === 'string' && v.trim() !== '') {
      if (el.dataset.cmsShown !== v) { el.innerText = v.trim(); el.dataset.cmsShown = v; }
    } else if (el.dataset.cmsShown !== undefined) {
      el.innerHTML = el.dataset.cmsOrig;
      delete el.dataset.cmsShown;
    }
  });
  doc.querySelectorAll('[data-cms-img]').forEach((el) => {
    if (el.dataset.cmsOrig === undefined) el.dataset.cmsOrig = el.getAttribute('src') || '';
    const url = drafts[el.dataset.cmsImg]?.image_url;
    const src = url ? assetUrl(url) : el.dataset.cmsOrig;
    if (el.getAttribute('src') !== src) el.setAttribute('src', src);
  });
  doc.querySelectorAll('[data-cms-href]').forEach((el) => {
    if (el.dataset.cmsOrig === undefined) el.dataset.cmsOrig = el.getAttribute('href') || '';
    const [key, field] = el.dataset.cmsHref.split('.');
    const v = drafts[key]?.content?.[field];
    el.setAttribute('href', typeof v === 'string' && v.trim() ? v.trim() : el.dataset.cmsOrig);
  });
}

export default function LivePreview({ url, drafts, focusKey }) {
  const frame = useRef(null);
  const [device, setDevice] = useState('desktop');
  const [box, setBox] = useState(0);
  const [blocked, setBlocked] = useState(false);
  const holder = useRef(null);
  const [nonce, setNonce] = useState(0);

  const doc = () => {
    try {
      return frame.current?.contentDocument || null;
    } catch {
      return null;
    }
  };

  // Re-apply on every change and when the frame (re)loads.
  useEffect(() => { apply(doc(), drafts); }, [drafts]);
  const onLoad = () => {
    const d = doc();
    setBlocked(!d);
    if (!d) return;
    // Links inside the preview should not navigate away from it.
    d.addEventListener('click', (e) => { if (e.target.closest('a')) e.preventDefault(); }, true);
    apply(d, drafts);
  };

  // Scroll the preview to the section being edited.
  useEffect(() => {
    if (!focusKey) return;
    const el = doc()?.querySelector(`[data-cms^="${focusKey}."], [data-cms-img="${focusKey}"]`);
    el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }, [focusKey]);

  // Scale the page down to the panel's width.
  useEffect(() => {
    const ro = new ResizeObserver(([e]) => setBox(e.contentRect.width));
    if (holder.current) ro.observe(holder.current);
    return () => ro.disconnect();
  }, []);
  const w = WIDTHS[device];
  const scale = box ? Math.min(1, box / w) : 1;
  const h = 1500;

  return (
    <div className="card overflow-hidden">
      <div className="flex flex-wrap items-center justify-between gap-2 border-b border-gold-light/70 px-4 py-3">
        <div>
          <p className="text-sm font-bold">Live preview</p>
          <p className="text-[11px] text-slate-500">Updates as you type. Nothing is published until you press Save.</p>
        </div>
        <div className="flex items-center gap-1">
          {Object.keys(WIDTHS).map((d) => (
            <button key={d} type="button" onClick={() => setDevice(d)}
              className={`rounded-lg px-2.5 py-1 text-xs font-semibold capitalize ${device === d ? 'bg-navy text-white' : 'bg-slate-100 text-slate-600'}`}>
              {d === 'desktop' ? '🖥 Desktop' : '📱 Mobile'}
            </button>
          ))}
          <button type="button" className="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600" onClick={() => setNonce((n) => n + 1)} title="Reload the page">↻</button>
          <a href={url} target="_blank" rel="noreferrer" className="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600" title="Open the live page">↗</a>
        </div>
      </div>
      <div ref={holder} className="relative overflow-hidden bg-slate-100" style={{ height: h * scale }}>
        <iframe
          key={`${device}-${nonce}`}
          ref={frame}
          src={url}
          title="Page preview"
          onLoad={onLoad}
          style={{ width: w, height: h, transform: `scale(${scale})`, transformOrigin: 'top left', border: 0, background: '#fff', marginLeft: device === 'mobile' && box > w ? (box - w) / 2 : 0 }}
        />
      </div>
      {blocked && (
        <p className="border-t border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
          The preview can show the page but not your unsaved changes here (the admin and the website are on different addresses). Open the admin at www.fsia.in/cms-admin/.
        </p>
      )}
    </div>
  );
}
