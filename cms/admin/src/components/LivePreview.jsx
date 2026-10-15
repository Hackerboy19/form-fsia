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
 * Pages without these markers (an older or hand-edited copy of the page) are
 * matched by text instead: each field's current text (the saved value, or the
 * page's default shown as the placeholder) is looked up on the page and, when it
 * appears exactly once, replaced as you type. Photos then show after Save.
 * Nothing is saved or published by the preview.
 */

const WIDTHS = { desktop: 1280, mobile: 390 };
const MIN_MATCH = 6; // shorter texts ("Team") appear in too many places to match safely

function textNodes(doc) {
  const out = [];
  const walker = doc.createTreeWalker(doc.body, NodeFilter.SHOW_TEXT, {
    acceptNode: (n) => (/^(SCRIPT|STYLE|NOSCRIPT|TEXTAREA)$/.test(n.parentNode?.nodeName || '') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT),
  });
  while (walker.nextNode()) out.push(walker.currentNode);
  return out;
}

/** For pages without data-cms markers: find where each field's current text sits on the page. */
function bindByText(doc, baseline) {
  if (!doc?.body) return [];
  const nodes = textNodes(doc);
  const used = new Set();
  const out = [];
  Object.entries(baseline || {}).forEach(([key, fields]) => {
    Object.entries(fields).forEach(([field, current]) => {
      const lines = String(current || '').split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
      if (!lines.length || lines[0].length < MIN_MATCH) return;
      const hits = nodes.filter((n) => !used.has(n) && n.nodeValue.includes(lines[0]));
      if (hits.length !== 1) return; // missing or ambiguous: leave the page alone
      const node = hits[0];
      if (lines.length > 1) {
        // Several lines (shown with <br>): use the element that holds exactly this text.
        let el = node.parentElement;
        while (el && el !== doc.body && !lines.every((l) => el.textContent.includes(l))) el = el.parentElement;
        const own = lines.join(' ').length;
        if (!el || el === doc.body || el.textContent.replace(/\s+/g, ' ').trim().length > own + 40) return;
        out.push({ key, field, el, origHTML: el.innerHTML });
      } else {
        out.push({ key, field, node, orig: lines[0], origValue: node.nodeValue });
      }
      used.add(node);
    });
  });
  return out;
}

function applyBound(bindings, drafts) {
  bindings.forEach((b) => {
    const v = drafts[b.key]?.content?.[b.field];
    const has = typeof v === 'string' && v.trim() !== '';
    if (b.node) {
      const next = has ? b.origValue.replace(b.orig, v.trim().replace(/\s*\n\s*/g, ' ')) : b.origValue;
      if (b.node.nodeValue !== next) b.node.nodeValue = next;
    } else if (has) {
      if (b.el.innerText !== v.trim()) b.el.innerText = v.trim();
    } else if (b.el.innerHTML !== b.origHTML) {
      b.el.innerHTML = b.origHTML;
    }
  });
}

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

export default function LivePreview({ url, drafts, focusKey, baseline, reloadKey = 0 }) {
  const frame = useRef(null);
  const [device, setDevice] = useState('desktop');
  const [box, setBox] = useState(0);
  const [blocked, setBlocked] = useState(false);
  const holder = useRef(null);
  const [nonce, setNonce] = useState(0);
  const bound = useRef(null); // text bindings when the page has no data-cms markers
  const [mode, setMode] = useState('markers');

  const doc = () => {
    try {
      return frame.current?.contentDocument || null;
    } catch {
      return null;
    }
  };

  const applyAll = (d) => {
    if (!d) return;
    if (bound.current) applyBound(bound.current, drafts);
    else apply(d, drafts);
  };

  // Re-apply on every change and when the frame (re)loads.
  useEffect(() => { applyAll(doc()); }, [drafts]); // eslint-disable-line react-hooks/exhaustive-deps
  const onLoad = () => {
    const d = doc();
    setBlocked(!d);
    if (!d) return;
    // Links inside the preview should not navigate away from it.
    d.addEventListener('click', (e) => { if (e.target.closest('a')) e.preventDefault(); }, true);
    const marked = d.querySelector('[data-cms], [data-cms-img]');
    bound.current = marked ? null : bindByText(d, baseline);
    setMode(marked ? 'markers' : bound.current.length ? 'text' : 'none');
    applyAll(d);
  };

  // The saved texts can arrive after the page has loaded: bind then.
  const baseKey = baseline ? JSON.stringify(baseline) : '';
  useEffect(() => {
    const d = doc();
    if (!baseKey || !d?.body || d.querySelector('[data-cms], [data-cms-img]') || bound.current?.length) return;
    bound.current = bindByText(d, baseline);
    setMode(bound.current.length ? 'text' : 'none');
    applyAll(d);
  }, [baseKey]); // eslint-disable-line react-hooks/exhaustive-deps

  // Scroll the preview to the section being edited.
  useEffect(() => {
    if (!focusKey) return;
    const b = bound.current?.find((x) => x.key === focusKey);
    const el = b ? (b.el || b.node.parentElement) : doc()?.querySelector(`[data-cms^="${focusKey}."], [data-cms-img="${focusKey}"]`);
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
          <p className="text-[11px] text-slate-500">
            {mode === 'none'
              ? 'Shows the page as it is now; your changes appear here after Save.'
              : mode === 'text'
                ? 'Text updates as you type; photos after Save. Nothing is published until you press Save.'
                : 'Updates as you type. Nothing is published until you press Save.'}
          </p>
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
          key={`${device}-${nonce}-${reloadKey}`}
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
