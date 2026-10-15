import { useEffect, useMemo, useState } from 'react';
import { api } from '../lib/api';
import { PageHeader } from '../components/Layout';
import Modal from '../components/Modal';

const SITE = 'https://www.fsia.in';
const IMG = /\.(jpe?g|png|webp|gif|jfif|avif)$/i;

/* Plain-language description of every group the scan can return. */
const GROUPS = {
  backup: {
    icon: '🗂', title: 'Backup & copy files',
    what: 'Old copies of pages, like feach-data.php.bak or .htaccess-broken.',
    why: 'Anyone who guesses the name can download them and read your code.',
    tag: ['Security risk', 'bg-red-100 text-red-700'], advice: 'Safe to move to Trash.',
  },
  archive: {
    icon: '📦', title: 'Zip files & database dumps',
    what: 'Uploaded .zip packages and .sql database exports.',
    why: 'They can contain code, passwords or customer data, and anyone with the link can download them.',
    tag: ['Security risk', 'bg-red-100 text-red-700'], advice: 'Safe to move once the upload is done.',
  },
  log: {
    icon: '📜', title: 'Log files',
    what: 'Records the website writes, like contact-submissions.log.',
    why: "They can hold visitors' names, emails and phone numbers, and are open to anyone.",
    tag: ['Privacy risk', 'bg-orange-100 text-orange-800'], advice: 'Download a copy first if you still need it.',
  },
  orphan: {
    icon: '🖼', title: 'Unused profile photos',
    what: 'Photos in the profile folders that no profile uses any more (older than 30 days).',
    why: 'They only take up space on the server.',
    tag: ['Frees space', 'bg-emerald-100 text-emerald-800'], advice: 'Look at the photos, then move them.',
  },
  large: {
    icon: '🐘', title: 'Very large files',
    what: 'Files over 10 MB, like videos or big images.',
    why: 'Some may still be used on the website: check each one first.',
    tag: ['Check first', 'bg-amber-100 text-amber-800'], advice: 'Only move files you are sure about.',
  },
};
const RISKY = ['backup', 'archive', 'log'];

export const formatBytes = (n) => {
  if (!n) return '0 KB';
  const u = ['bytes', 'KB', 'MB', 'GB'];
  const i = Math.min(u.length - 1, Math.floor(Math.log(n) / Math.log(1024)));
  return `${(n / 1024 ** i).toFixed(i > 1 ? 1 : 0)} ${u[i]}`;
};
const ago = (iso) => {
  const d = Math.floor((Date.now() - new Date(iso).getTime()) / 86400000);
  return d <= 0 ? 'today' : d === 1 ? 'yesterday' : `${d} days ago`;
};

/** Finds files the website does not need and moves them to a Trash folder outside the website. */
export default function ServerCleanup() {
  const [tab, setTab] = useState('find');
  const [scan, setScan] = useState(null);
  const [scanning, setScanning] = useState(false);
  const [group, setGroup] = useState(null);
  const [selected, setSelected] = useState({}); // path -> size
  const [search, setSearch] = useState('');
  const [confirm, setConfirm] = useState(false);
  const [busy, setBusy] = useState(false);
  const [msg, setMsg] = useState('');
  const [error, setError] = useState('');
  const [trash, setTrash] = useState(null);

  const loadTrash = () => api.cleanupTrashList().then(setTrash).catch((e) => setError(e.message));
  useEffect(() => { loadTrash(); }, []);

  async function runScan() {
    setScanning(true); setError(''); setMsg(''); setSelected({}); setGroup(null);
    try {
      const d = await api.cleanupScan();
      setScan(d);
      const first = Object.keys(GROUPS).find((k) => d.groups[k]?.count);
      setGroup(first || null);
    } catch (e) { setError(e.message); }
    finally { setScanning(false); }
  }

  const items = useMemo(() => {
    const list = (group && scan?.groups[group]?.items) || [];
    const q = search.trim().toLowerCase();
    return q ? list.filter((i) => i.path.toLowerCase().includes(q)) : list;
  }, [scan, group, search]);

  const selPaths = Object.keys(selected);
  const selBytes = Object.values(selected).reduce((a, b) => a + b, 0);
  const allOn = items.length > 0 && items.every((i) => selected[i.path] !== undefined);
  const toggle = (i) => setSelected((s) => { const n = { ...s }; if (n[i.path] !== undefined) delete n[i.path]; else n[i.path] = i.size; return n; });
  const toggleAll = () => setSelected((s) => {
    const n = { ...s };
    items.forEach((i) => { if (allOn) delete n[i.path]; else n[i.path] = i.size; });
    return n;
  });

  async function moveToTrash() {
    setBusy(true); setError('');
    try {
      const r = await api.cleanupTrash(selPaths);
      setMsg(`✓ Moved ${r.moved} file${r.moved === 1 ? '' : 's'} (${formatBytes(r.bytes)}) to Trash. You can put them back from the Trash tab for 7 days.`);
      // Take the moved files out of the lists without scanning again.
      setScan((d) => {
        const groups = { ...d.groups };
        Object.keys(groups).forEach((k) => {
          const keep = groups[k].items.filter((i) => selected[i.path] === undefined);
          const gone = groups[k].items.length - keep.length;
          const goneBytes = groups[k].items.filter((i) => selected[i.path] !== undefined).reduce((a, i) => a + i.size, 0);
          groups[k] = { ...groups[k], items: keep, count: groups[k].count - gone, bytes: groups[k].bytes - goneBytes };
        });
        return { ...d, groups };
      });
      setSelected({});
      setConfirm(false);
      loadTrash();
    } catch (e) { setError(e.message); setConfirm(false); }
    finally { setBusy(false); }
  }

  const totalBytes = scan ? Object.values(scan.groups).reduce((a, g) => a + g.bytes, 0) : 0;
  const riskyCount = scan ? RISKY.reduce((a, k) => a + (scan.groups[k]?.count || 0), 0) : 0;
  const trashCount = trash?.batches.reduce((a, b) => a + b.count, 0) || 0;

  return (
    <>
      <PageHeader
        title="Server Cleanup"
        description="Finds files the website does not need. Chosen files go to a Trash folder outside the website: nothing is deleted straight away, and you can put files back for 7 days."
      />
      {error && <p className="card mb-4 p-4 text-sm text-red-600">{error}</p>}
      {msg && <p className="card mb-4 border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{msg}</p>}

      <div className="mb-4 flex gap-2" role="tablist">
        {[['find', '🔍 Find unwanted files'], ['trash', `🗑 Trash${trashCount ? ` (${trashCount})` : ''}`]].map(([k, label]) => (
          <button key={k} type="button" role="tab" aria-selected={tab === k} onClick={() => setTab(k)}
            className={`rounded-xl border px-4 py-2 text-sm font-semibold transition ${tab === k ? 'border-gold bg-gold/15 text-navy' : 'border-slate-200 bg-white text-slate-600 hover:border-gold'}`}>
            {label}
          </button>
        ))}
      </div>

      {tab === 'trash' ? (
        <TrashTab trash={trash} reload={loadTrash} setMsg={setMsg} setError={setError} />
      ) : (
        <>
          <div className="card flex flex-wrap items-center gap-4 p-5">
            <button type="button" className="btn-gold" onClick={runScan} disabled={scanning}>
              {scanning ? 'Scanning…' : scan ? '↻ Scan again' : '🔍 Scan the server'}
            </button>
            <p className="flex-1 text-sm text-slate-600">
              {scanning ? 'Looking through every folder of the website. This can take up to a minute.'
                : scan ? <>Checked {scan.walked.toLocaleString()} files.{!scan.photos_checked && ' Unused photos were not checked: the profiles database could not be read.'}{scan.stopped && ' The scan stopped early (too many files).'}</>
                  : 'The scan only looks; nothing is moved until you choose files and confirm. Code, settings (config.php, .htaccess) and the CMS are never touched.'}
            </p>
          </div>

          {scan && (
            <>
              <div className="mt-4 grid gap-3 sm:grid-cols-2">
                <div className="card p-5">
                  <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Space you can free</p>
                  <p className="mt-1 font-display text-4xl font-bold">{formatBytes(totalBytes)}</p>
                </div>
                <div className={`card p-5 ${riskyCount ? 'border-red-200 bg-red-50' : ''}`}>
                  <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Files anyone can download</p>
                  <p className={`mt-1 font-display text-4xl font-bold ${riskyCount ? 'text-red-700' : 'text-emerald-700'}`}>{riskyCount}</p>
                  <p className="text-xs text-slate-600">{riskyCount ? 'Backups, zips and logs: move these first.' : 'Nothing risky found. 👍'}</p>
                </div>
              </div>

              <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                {Object.entries(GROUPS).map(([k, g]) => {
                  const d = scan.groups[k] || { count: 0, bytes: 0 };
                  return (
                    <button key={k} type="button" onClick={() => { setGroup(k); setSearch(''); }} aria-pressed={group === k} disabled={!d.count}
                      className={`card p-4 text-left transition disabled:opacity-50 ${group === k ? 'ring-2 ring-gold' : 'hover:border-gold'}`}>
                      <span className="text-2xl" aria-hidden="true">{g.icon}</span>
                      <p className="mt-1 text-sm font-bold">{g.title}</p>
                      <p className="mt-1 font-display text-2xl font-bold">{d.count.toLocaleString()}</p>
                      <p className="text-xs text-slate-500">{formatBytes(d.bytes)}</p>
                      {d.count > 0 && <span className={`mt-2 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold ${g.tag[1]}`}>{g.tag[0]}</span>}
                    </button>
                  );
                })}
              </div>

              {group && (
                <div className="card mt-4 p-5">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="max-w-2xl">
                      <h2 className="font-display text-xl font-bold">{GROUPS[group].icon} {GROUPS[group].title}</h2>
                      <p className="mt-1 text-sm text-slate-600"><b>What:</b> {GROUPS[group].what}</p>
                      <p className="text-sm text-slate-600"><b>Why remove:</b> {GROUPS[group].why}</p>
                      <p className="mt-1 text-sm font-semibold text-gold-dark">💡 {GROUPS[group].advice}</p>
                    </div>
                    <input className="input !w-64" placeholder="Search file name…" value={search} onChange={(e) => setSearch(e.target.value)} />
                  </div>
                  {scan.groups[group].count > scan.groups[group].items.length && (
                    <p className="mt-3 rounded-xl bg-amber-50 p-3 text-xs text-amber-800">
                      Showing the {scan.groups[group].items.length.toLocaleString()} largest of {scan.groups[group].count.toLocaleString()}. Move these, then scan again for the rest.
                    </p>
                  )}

                  <div className="relative mt-4 overflow-x-auto rounded-xl border border-slate-100">
                    <table className="w-full min-w-[560px] text-left text-sm">
                      <thead className="bg-cream/60 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr>
                          <th className="w-10 p-3"><input type="checkbox" checked={allOn} onChange={toggleAll} aria-label="Select all" /></th>
                          <th className="p-3">File</th>
                          <th className="p-3">Size</th>
                          <th className="p-3">Last changed</th>
                          <th className="p-3"><span className="sr-only">Copy</span></th>
                        </tr>
                      </thead>
                      <tbody>
                        {items.map((i) => (
                          <tr key={i.path} className={`border-t border-slate-100 ${selected[i.path] !== undefined ? 'bg-gold/5' : ''}`}>
                            <td className="p-3"><input type="checkbox" checked={selected[i.path] !== undefined} onChange={() => toggle(i)} aria-label={`Select ${i.path}`} /></td>
                            <td className="p-3">
                              <label className="flex cursor-pointer items-center gap-3" onClick={(e) => { e.preventDefault(); toggle(i); }}>
                                {IMG.test(i.path) && (
                                  <img src={`${SITE}/${i.path.split('/').map(encodeURIComponent).join('/')}`} alt="" loading="lazy" className="h-10 w-10 shrink-0 rounded-lg bg-cream object-cover" />
                                )}
                                <span className="break-all font-mono text-xs">{i.path}</span>
                              </label>
                            </td>
                            <td className="whitespace-nowrap p-3 text-slate-600">{formatBytes(i.size)}</td>
                            <td className="whitespace-nowrap p-3 text-slate-600">{i.date}</td>
                            <td className="p-3">
                              <button type="button" className="text-xs font-semibold text-gold-dark underline" onClick={() => api.cleanupDownload(i.path).catch((e) => setError(e.message))}>
                                ⤓ Copy
                              </button>
                            </td>
                          </tr>
                        ))}
                        {!items.length && <tr><td colSpan="5" className="p-6 text-center text-sm text-slate-500">Nothing here.</td></tr>}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </>
          )}

          {selPaths.length > 0 && (
            <div className="sticky bottom-4 z-10 mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-navy p-4 text-white shadow-2xl">
              <p className="text-sm"><b>{selPaths.length.toLocaleString()}</b> file{selPaths.length === 1 ? '' : 's'} selected · {formatBytes(selBytes)}</p>
              <div className="flex gap-2">
                <button type="button" className="rounded-xl px-3 py-2 text-sm font-semibold text-slate-300 hover:text-white" onClick={() => setSelected({})}>Clear</button>
                <button type="button" className="btn-gold" onClick={() => setConfirm(true)}>🗑 Move to Trash</button>
              </div>
            </div>
          )}
        </>
      )}

      <Modal open={confirm} title="Move to Trash?" onClose={() => !busy && setConfirm(false)} width="max-w-lg"
        footer={(
          <>
            <button type="button" className="btn-ghost" onClick={() => setConfirm(false)} disabled={busy}>Cancel</button>
            <button type="button" className="btn-gold" onClick={moveToTrash} disabled={busy}>{busy ? 'Moving…' : `Move ${selPaths.length} file${selPaths.length === 1 ? '' : 's'}`}</button>
          </>
        )}>
        <ul className="space-y-2 text-sm text-slate-700">
          <li>📁 <b>{selPaths.length.toLocaleString()} files ({formatBytes(selBytes)})</b> leave the website and go to a Trash folder on the server.</li>
          <li>🚫 Visitors can no longer open or download them.</li>
          <li>↩ You can put them back from the <b>Trash</b> tab at any time.</li>
          <li>⏳ They can be deleted for good only after <b>7 days</b>.</li>
        </ul>
      </Modal>
    </>
  );
}

function TrashTab({ trash, reload, setMsg, setError }) {
  const [open, setOpen] = useState(null);
  const [busy, setBusy] = useState('');
  const [purge, setPurge] = useState(null);

  async function restore(batch, paths) {
    setBusy(batch); setError(''); setMsg('');
    try {
      const r = await api.cleanupRestore(batch, paths);
      setMsg(`↩ Put back ${r.restored} file${r.restored === 1 ? '' : 's'} on the website.${r.skipped.length ? ` Skipped: ${r.skipped.join('; ')}` : ''}`);
      await reload();
    } catch (e) { setError(e.message); }
    finally { setBusy(''); }
  }
  async function doPurge() {
    const b = purge;
    setBusy(b.batch); setError(''); setMsg('');
    try {
      const r = await api.cleanupPurge(b.batch);
      setMsg(`Deleted ${r.deleted} file${r.deleted === 1 ? '' : 's'} for good.`);
      setPurge(null);
      await reload();
    } catch (e) { setError(e.message); setPurge(null); }
    finally { setBusy(''); }
  }

  if (!trash) return <div className="card h-32 animate-pulse bg-cream" />;
  if (!trash.batches.length) {
    return <p className="card p-8 text-center text-sm text-slate-500">Trash is empty. Files you move from “Find unwanted files” show up here.</p>;
  }
  return (
    <div className="space-y-3">
      <p className="text-sm text-slate-600">Files here are outside the website. Put them back any time; “Delete for good” unlocks {trash.keep_days} days after moving.</p>
      {trash.batches.map((b) => (
        <div key={b.batch} className="card overflow-hidden">
          <div className="flex flex-wrap items-center gap-3 p-4">
            <button type="button" className="min-w-0 flex-1 text-left" onClick={() => setOpen(open === b.batch ? null : b.batch)} aria-expanded={open === b.batch}>
              <p className="font-semibold">Moved {ago(b.at)} <span className="font-normal text-slate-500">· {new Date(b.at).toLocaleString()}</span></p>
              <p className="text-xs text-slate-500">{b.count} file{b.count === 1 ? '' : 's'} · {formatBytes(b.bytes)} · {open === b.batch ? 'hide list ▲' : 'show list ▼'}</p>
            </button>
            <button type="button" className="btn-ghost !py-1.5" disabled={!!busy} onClick={() => restore(b.batch)}>↩ Put all back</button>
            <button type="button" className="btn-danger !py-1.5" disabled={!!busy || !b.can_purge} onClick={() => setPurge(b)}
              title={b.can_purge ? '' : `Available in ${b.purge_in} day${b.purge_in === 1 ? '' : 's'}`}>
              {b.can_purge ? 'Delete for good' : `Delete in ${b.purge_in}d`}
            </button>
          </div>
          {open === b.batch && (
            <ul className="max-h-80 divide-y divide-slate-100 overflow-y-auto border-t border-slate-100 bg-cream/40">
              {b.items.map((i) => (
                <li key={i.path} className="flex items-center gap-3 px-4 py-2 text-xs">
                  <span className="min-w-0 flex-1 break-all font-mono">{i.path}</span>
                  <span className="text-slate-500">{formatBytes(i.size)}</span>
                  <button type="button" className="font-semibold text-gold-dark underline" disabled={!!busy} onClick={() => restore(b.batch, [i.path])}>Put back</button>
                </li>
              ))}
            </ul>
          )}
        </div>
      ))}

      <Modal open={!!purge} title="Delete for good?" onClose={() => setPurge(null)} width="max-w-lg"
        footer={(
          <>
            <button type="button" className="btn-ghost" onClick={() => setPurge(null)}>Cancel</button>
            <button type="button" className="btn-danger" onClick={doPurge} disabled={!!busy}>{busy ? 'Deleting…' : 'Yes, delete for good'}</button>
          </>
        )}>
        <p className="text-sm text-slate-700">
          {purge && <>The <b>{purge.count} files ({formatBytes(purge.bytes)})</b> moved {ago(purge.at)} will be removed from the server. <b>This cannot be undone.</b></>}
        </p>
      </Modal>
    </div>
  );
}
