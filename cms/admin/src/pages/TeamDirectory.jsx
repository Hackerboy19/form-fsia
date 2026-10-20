import { useCallback, useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { api, assetUrl } from '../lib/api';
import { SOCIAL_NETWORKS, TEAM_CATEGORIES, TEAM_CORE_CATEGORIES, splitCategories } from '../lib/pages';
import { PageHeader } from '../components/Layout';
import { Field, Toggle } from '../components/Field';
import ImageUpload from '../components/ImageUpload';
import Modal from '../components/Modal';
import { useToast } from '../lib/toast';

const NEW_MEMBER = { name: '', designation: '', category: 'Mentors', image_url: '', bio: '', quote: '', phone: '', display_order: 0, is_active: true, social_links: {} };

function MemberModal({ member, onClose, onSaved }) {
  const [form, setForm] = useState(() => ({ ...NEW_MEMBER, ...member, bio: member?.bio ?? '', quote: member?.quote ?? '', phone: member?.phone ?? '', social_links: { ...(member?.social_links || {}) } }));
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);
  const notify = useToast();
  const isNew = !member?.id;
  const set = (k) => (v) => setForm((f) => ({ ...f, [k]: v }));
  const setSocial = (k) => (v) => setForm((f) => ({ ...f, social_links: { ...f.social_links, [k]: v } }));

  async function save(e) {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    const body = {
      name: form.name,
      designation: form.designation,
      category: form.category,
      image_url: form.image_url,
      bio: form.bio,
      quote: form.quote,
      phone: form.phone,
      display_order: Number(form.display_order) || 0,
      is_active: form.is_active,
      social_links: Object.fromEntries(Object.entries(form.social_links).filter(([, v]) => v && v.trim())),
    };
    try {
      const saved = isNew ? await api.createMember(body) : await api.updateMember(member.id, body);
      notify(isNew ? `${saved.name} added` : `${saved.name} updated`);
      onSaved(saved);
    } catch (err) {
      setErrors(err.fields || {});
      notify(err.message, 'error');
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      title={isNew ? 'Add team member' : `Edit ${member.name}`}
      footer={
        <>
          <button type="button" className="btn-ghost" onClick={onClose}>Cancel</button>
          <button type="submit" form="member-form" className="btn-gold" disabled={saving}>{saving ? 'Saving…' : isNew ? 'Add member' : 'Save changes'}</button>
        </>
      }
    >
      <form id="member-form" onSubmit={save} className="grid gap-5 sm:grid-cols-[200px_1fr]">
        <div>
          <ImageUpload label="Photo" value={form.image_url} onChange={set('image_url')} size={{ w: 900, h: 1200, fit: 'cover' }} hint={errors.image_url || 'Portrait; keep the face near the top.'} />
        </div>
        <div className="space-y-4">
          <Field label="Name *" value={form.name} onChange={set('name')} max={150} error={errors.name} required />
          <Field label="Designation" value={form.designation} onChange={set('designation')} max={150} error={errors.designation} placeholder="e.g. Rampwalk Expert" />
          <div className="grid grid-cols-2 gap-4">
            <CategoryPicker value={form.category} onChange={set('category')} error={errors.category} />
            <Field label="Display order" type="number" value={form.display_order} onChange={set('display_order')} error={errors.display_order} hint="Lower shows first" />
          </div>
          <Toggle checked={form.is_active} onChange={set('is_active')} label="Visible on the website" description="Hidden members stay here but don't appear on our-teams.php." />
          <Field label="About (profile text)" type="textarea" rows={4} value={form.bio} onChange={set('bio')} max={2000} recommended={400} error={errors.bio}
            hint={errors.bio || 'Shown in the profile pop-up (and under the name for Leadership). Empty = a short automatic line.'} />
          {splitCategories(form.category).includes('Leadership') && (
            <Field label="Quote line (Leadership only)" value={form.quote} onChange={set('quote')} max={255} error={errors.quote}
              placeholder="Talent should receive opportunity before it is judged." hint={errors.quote || 'The large italic line under the name on our-teams.php.'} />
          )}
          <Field label="Phone (optional)" value={form.phone} onChange={set('phone')} max={30} error={errors.phone} placeholder="9983599666"
            hint={errors.phone || 'Adds a “Call” button on the profile.'} />
          <fieldset className="space-y-2">
            <legend className="label">Social links</legend>
            {SOCIAL_NETWORKS.map((net) => (
              <div key={net} className="grid grid-cols-[90px_1fr] items-center gap-2">
                <span className="text-xs font-semibold capitalize text-slate-500">{net}</span>
                <div>
                  <input
                    className={`input !py-2 ${errors[`social_links.${net}`] ? 'border-red-400' : ''}`}
                    type="url"
                    placeholder="https://"
                    value={form.social_links[net] || ''}
                    onChange={(e) => setSocial(net)(e.target.value)}
                  />
                  {errors[`social_links.${net}`] && <p className="mt-1 text-xs text-red-600">{errors[`social_links.${net}`]}</p>}
                </div>
              </div>
            ))}
          </fieldset>
        </div>
      </form>
    </Modal>
  );
}

/** Several categories per member: the page sections as checkboxes, anything else typed in "Other". */
function CategoryPicker({ value, onChange, error }) {
  const current = splitCategories(value);
  const custom = current.filter((c) => !TEAM_CORE_CATEGORIES.includes(c));
  const toggle = (cat) => {
    const next = current.includes(cat) ? current.filter((c) => c !== cat) : [...current, cat];
    onChange(next.join(', '));
  };
  const setCustom = (text) => {
    const core = current.filter((c) => TEAM_CORE_CATEGORIES.includes(c));
    onChange([...core, ...splitCategories(text)].join(', '));
  };
  return (
    <div className="col-span-2 space-y-2 rounded-xl border border-slate-200 bg-slate-50/60 p-3">
      <span className="label font-bold text-navy">Category (select one or more, or add Other)</span>
      <div className="flex flex-wrap gap-2">
        {TEAM_CORE_CATEGORIES.map((cat) => {
          const on = current.includes(cat);
          return (
            <label key={cat} className={`inline-flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-semibold ${on ? 'border-navy bg-navy text-gold' : 'border-slate-300 bg-white text-slate-700'}`}>
              <input type="checkbox" className="sr-only" checked={on} onChange={() => toggle(cat)} />
              {on ? '✓ ' : '+ '}{cat}
            </label>
          );
        })}
      </div>
      <div className="border-t border-slate-200 pt-2">
        <span className="text-[11px] font-semibold text-slate-500">Other (type a category not in the list):</span>
        <input className="input mt-1 !py-1 text-xs" placeholder="e.g. Jury, Special Guests (comma separated)" defaultValue={custom.join(', ')}
          onChange={(e) => setCustom(e.target.value)} />
      </div>
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  );
}

export default function TeamDirectory() {
  const [params, setParams] = useSearchParams();
  const [members, setMembers] = useState(null);
  const [category, setCategory] = useState('All');
  const [query, setQuery] = useState('');
  const [editing, setEditing] = useState(params.get('new') ? {} : null);
  const notify = useToast();

  const load = useCallback(() => {
    api.listTeam().then(setMembers).catch((e) => notify(e.message, 'error'));
  }, [notify]);
  useEffect(load, [load]);

  const counts = useMemo(() => {
    const c = { All: members?.length || 0 };
    TEAM_CATEGORIES.forEach((cat) => { c[cat] = members?.filter((m) => splitCategories(m.category).includes(cat)).length || 0; });
    return c;
  }, [members]);

  const visible = useMemo(() => {
    const q = query.trim().toLowerCase();
    return (members || []).filter(
      (m) => (category === 'All' || splitCategories(m.category).includes(category)) && (!q || `${m.name} ${m.designation}`.toLowerCase().includes(q)),
    );
  }, [members, category, query]);

  async function toggleActive(m) {
    setMembers((list) => list.map((x) => (x.id === m.id ? { ...x, is_active: !x.is_active } : x)));
    try {
      await api.updateMember(m.id, { is_active: !m.is_active });
    } catch (e) {
      notify(e.message, 'error');
      load();
    }
  }

  async function remove(m) {
    if (!window.confirm(`Delete ${m.name}? This cannot be undone. (To just hide them, switch off "Visible".)`)) return;
    try {
      await api.deleteMember(m.id);
      setMembers((list) => list.filter((x) => x.id !== m.id));
      notify(`${m.name} deleted`);
    } catch (e) {
      notify(e.message, 'error');
    }
  }

  function closeModal() {
    setEditing(null);
    if (params.get('new')) setParams({});
  }

  return (
    <>
      <PageHeader
        title="Team Directory"
        description="Members shown on our-teams.php, in display order within each category."
        actions={<button type="button" className="btn-gold" onClick={() => setEditing({})}>+ Add member</button>}
      />

      <div className="mb-4 flex flex-wrap items-center gap-2">
        {['All', ...TEAM_CATEGORIES].map((c) => (
          <button
            key={c}
            type="button"
            onClick={() => setCategory(c)}
            className={`rounded-full border px-3.5 py-1.5 text-sm font-semibold transition ${c === category ? 'border-navy bg-navy text-white' : 'border-slate-200 bg-white hover:border-gold'}`}
          >
            {c} <span className={c === category ? 'text-gold' : 'text-slate-400'}>{counts[c]}</span>
          </button>
        ))}
        <input className="input ml-auto !w-full !py-2 sm:!w-64" placeholder="Search name or role…" value={query} onChange={(e) => setQuery(e.target.value)} aria-label="Search team" />
      </div>

      <div className="card overflow-x-auto">
        <table className="w-full min-w-[720px] text-left text-sm">
          <thead className="border-b border-slate-100 bg-cream text-xs uppercase tracking-wider text-slate-500">
            <tr>
              <th className="px-4 py-3">Member</th>
              <th className="px-4 py-3">Category</th>
              <th className="px-4 py-3 text-center">Order</th>
              <th className="px-4 py-3">Visible</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {members === null &&
              [1, 2, 3, 4].map((i) => (
                <tr key={i}><td colSpan={5} className="px-4 py-3"><div className="h-10 animate-pulse rounded-lg bg-cream" /></td></tr>
              ))}
            {members && visible.length === 0 && (
              <tr><td colSpan={5} className="px-4 py-10 text-center text-slate-500">No members match.</td></tr>
            )}
            {visible.map((m) => (
              <tr key={m.id} className={`border-b border-slate-50 last:border-0 hover:bg-cream/60 ${m.is_active ? '' : 'opacity-60'}`}>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-3">
                    <div className="h-12 w-10 flex-none overflow-hidden rounded-t-full bg-cream-2">
                      {m.image_url && <img src={assetUrl(m.image_url)} alt="" className="h-full w-full object-cover object-top" loading="lazy" />}
                    </div>
                    <div>
                      <p className="font-semibold">{m.name}</p>
                      <p className="text-xs text-slate-500">{m.designation}</p>
                    </div>
                  </div>
                </td>
                <td className="px-4 py-3">
                  <span className="flex flex-wrap gap-1">
                    {splitCategories(m.category).map((c) => <span key={c} className="rounded-full bg-cream-2 px-2.5 py-1 text-xs font-semibold text-gold-bronze">{c}</span>)}
                  </span>
                </td>
                <td className="px-4 py-3 text-center tabular-nums">{m.display_order}</td>
                <td className="px-4 py-3"><Toggle checked={m.is_active} onChange={() => toggleActive(m)} label={m.is_active ? 'Shown' : 'Hidden'} /></td>
                <td className="px-4 py-3 text-right">
                  <div className="inline-flex gap-2">
                    <button type="button" className="btn-ghost !px-3 !py-1.5" onClick={() => setEditing(m)}>Edit</button>
                    <button type="button" className="btn-danger !px-3 !py-1.5" onClick={() => remove(m)}>Delete</button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {editing && (
        <MemberModal
          member={editing.id ? editing : { ...NEW_MEMBER, category: category === 'All' ? 'Mentors' : category }}
          onClose={closeModal}
          onSaved={() => { closeModal(); load(); }}
        />
      )}
    </>
  );
}
