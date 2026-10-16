import { assetUrl } from '../lib/api';

/**
 * Two-pane editor for a list of items: the list on the left (select, reorder,
 * duplicate, delete), the selected item's form on the right.
 *
 * describe(item) -> { title, subtitle, image, badge, dot }
 */
export default function ListEditor({ items, selected, onSelect, onChange, describe, renderForm, makeNew, addLabel = 'Add item', emptyText = 'Nothing here yet.' }) {
  const index = items.findIndex((it) => it.id === selected);
  const current = index >= 0 ? items[index] : null;

  const update = (patch) => onChange(items.map((it, i) => (i === index ? { ...it, ...patch } : it)));
  const add = () => {
    const item = makeNew();
    onChange([...items, item]);
    onSelect(item.id);
  };
  const duplicate = (i) => {
    const copy = { ...structuredClone(items[i]), id: newId(items[i].id.split('-')[0] || 'item') };
    onChange([...items.slice(0, i + 1), copy, ...items.slice(i + 1)]);
    onSelect(copy.id);
  };
  const remove = (i) => {
    const d = describe(items[i]);
    if (!window.confirm(`Delete "${d.title || 'this item'}"?`)) return;
    const next = items.filter((_, j) => j !== i);
    onChange(next);
    onSelect(next[Math.min(i, next.length - 1)]?.id ?? null);
  };
  const move = (i, dir) => {
    const j = i + dir;
    if (j < 0 || j >= items.length) return;
    const next = [...items];
    [next[i], next[j]] = [next[j], next[i]];
    onChange(next);
  };

  return (
    <div className="grid grid-cols-1 gap-5 lg:grid-cols-[320px_minmax(0,1fr)]">
      <div className="card flex min-w-0 flex-col p-3">
        <ol className="flex max-h-[70vh] flex-col gap-2 overflow-y-auto pr-1">
          {items.length === 0 && <li className="p-4 text-center text-sm text-slate-500">{emptyText}</li>}
          {items.map((it, i) => {
            const d = describe(it);
            const active = it.id === selected;
            return (
              <li key={it.id}>
                <div
                  className={`group flex items-center gap-3 rounded-xl border p-2 transition ${active ? 'border-gold bg-gold/10' : 'border-slate-200 bg-white hover:border-gold/60'}`}
                >
                  <button type="button" onClick={() => onSelect(it.id)} className="flex min-w-0 flex-1 items-center gap-3 text-left">
                    <span className="relative grid h-12 w-16 flex-none place-items-center overflow-hidden rounded-lg bg-cream text-[10px] font-bold text-slate-400">
                      {d.image ? <img src={assetUrl(d.image)} alt="" className="h-full w-full object-cover" /> : (i + 1)}
                      {d.dot && <span className="absolute left-1 top-1 h-2.5 w-2.5 rounded-full ring-2 ring-white" style={{ background: d.dot }} />}
                    </span>
                    <span className="min-w-0">
                      <span className="block truncate text-sm font-semibold">{d.title || <em className="text-slate-400">Untitled</em>}</span>
                      {d.subtitle && <span className="block truncate text-xs text-slate-500">{d.subtitle}</span>}
                      {d.badge && <span className={`mt-0.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold ${d.badge.tone || 'bg-slate-100 text-slate-600'}`}>{d.badge.text}</span>}
                    </span>
                  </button>
                  <span className="flex flex-none flex-col gap-0.5 opacity-60 group-hover:opacity-100">
                    <button type="button" className="rounded px-1 text-xs hover:bg-slate-100 disabled:opacity-30" onClick={() => move(i, -1)} disabled={i === 0} aria-label="Move up">▲</button>
                    <button type="button" className="rounded px-1 text-xs hover:bg-slate-100 disabled:opacity-30" onClick={() => move(i, 1)} disabled={i === items.length - 1} aria-label="Move down">▼</button>
                  </span>
                </div>
                {active && (
                  <div className="mt-1 flex justify-end gap-2 px-1">
                    <button type="button" className="text-xs font-semibold text-slate-500 hover:text-navy" onClick={() => duplicate(i)}>Duplicate</button>
                    <button type="button" className="text-xs font-semibold text-red-600 hover:text-red-800" onClick={() => remove(i)}>Delete</button>
                  </div>
                )}
              </li>
            );
          })}
        </ol>
        <button type="button" className="btn-gold mt-3" onClick={add}>+ {addLabel}</button>
      </div>

      <div className="card min-w-0 p-5">
        {current ? renderForm(current, update) : <p className="py-16 text-center text-sm text-slate-500">Select an item on the left, or add a new one.</p>}
      </div>
    </div>
  );
}

export function newId(prefix = 'item') {
  return `${prefix}-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 6)}`;
}

/** "2026-11-30T23:59" -> window status for a start/end date pair. */
export function scheduleStatus(start, end, now = Date.now()) {
  const s = start ? new Date(start).getTime() : NaN;
  const e = end ? new Date(end).getTime() : NaN;
  if (!Number.isNaN(s) && now < s) return { text: `Starts in ${Math.ceil((s - now) / 864e5)}d`, tone: 'bg-sky-100 text-sky-700' };
  if (!Number.isNaN(e) && now > e) return { text: 'Expired', tone: 'bg-red-100 text-red-700' };
  if (!Number.isNaN(e)) return { text: `Active (${Math.max(0, Math.ceil((e - now) / 864e5))}d left)`, tone: 'bg-emerald-100 text-emerald-700' };
  return { text: 'Active (no end date)', tone: 'bg-emerald-100 text-emerald-700' };
}
