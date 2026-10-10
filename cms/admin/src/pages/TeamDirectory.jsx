import { useCallback, useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { api, assetUrl } from '../lib/api';
import { SOCIAL_NETWORKS, TEAM_CATEGORIES } from '../lib/pages';
import { PageHeader } from '../components/Layout';
import { Field, SelectField, Toggle } from '../components/Field';
import ImageUpload from '../components/ImageUpload';
import Modal from '../components/Modal';
import { useToast } from '../lib/toast';

const NEW_MEMBER = { name: '', designation: '', category: 'Mentors', image_url: '', display_order: 0, is_active: true, social_links: {} };

function MemberModal({ member, onClose, onSaved }) {
  const [form, setForm] = useState(() => ({ ...NEW_MEMBER, ...member, social_links: { ...(member?.social_links || {}) } }));
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
          <ImageUpload label="Photo" value={form.image_url} onChange={set('image_url')} aspect="aspect-[3/4]" hint={errors.image_url || 'Portrait, at least 600 × 800'} />
        </div>
        <div className="space-y-4">
          <Field label="Name *" value={form.name} onChange={set('name')} max={150} error={errors.name} required />
          <Field label="Designation" value={form.designation} onChange={set('designation')} max={150} error={errors.designation} placeholder="e.g. Rampwalk Expert" />
          <div className="grid grid-cols-2 gap-4">
            <SelectField label="Category" value={form.category} options={TEAM_CATEGORIES} onChange={set('category')} error={errors.category} />
            <Field label="Display order" type="number" value={form.display_order} onChange={set('display_order')} error={errors.display_order} hint="Lower shows first" />
          </div>
          <Toggle checked={form.is_active} onChange={set('is_active')} label="Visible on the website" description="Hidden members stay here but don't appear on our-teams.php." />
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
    TEAM_CATEGORIES.forEach((cat) => { c[cat] = members?.filter((m) => m.category === cat).length || 0; });
    return c;
  }, [members]);

  const visible = useMemo(() => {
    const q = query.trim().toLowerCase();
    return (members || []).filter(
      (m) => (category === 'All' || m.category === category) && (!q || `${m.name} ${m.designation}`.toLowerCase().includes(q)),
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
                <td className="px-4 py-3"><span className="rounded-full bg-cream-2 px-2.5 py-1 text-xs font-semibold text-gold-bronze">{m.category}</span></td>
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
