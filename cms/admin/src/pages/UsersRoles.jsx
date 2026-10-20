import { useEffect, useState } from 'react';
import { api } from '../lib/api';
import { useAuth } from '../lib/auth';
import { PageHeader } from '../components/Layout';
import Modal from '../components/Modal';

const ROLE_CLS = {
  owner: 'bg-gold/20 text-gold-dark',
  admin: 'bg-sky-100 text-sky-800',
  editor: 'bg-emerald-100 text-emerald-800',
  viewer: 'bg-slate-100 text-slate-600',
};

/** Who may do what in this admin. Passwords stay on the server (set-password.php). */
export default function UsersRoles() {
  const { refreshMe } = useAuth();
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [msg, setMsg] = useState('');
  const [busy, setBusy] = useState('');
  const [confirmSetup, setConfirmSetup] = useState(false);

  const load = () => api.listUsers().then(setData).catch((e) => setError(e.message));
  useEffect(() => { load(); }, []);

  async function setup() {
    setBusy('setup'); setError(''); setMsg('');
    try {
      await api.setupRoles();
      setMsg('✓ Roles are on. Everyone is an Owner for now: pick a role for each person below.');
      await load();
    } catch (e) { setError(e.message); }
    finally { setBusy(''); setConfirmSetup(false); }
  }

  async function change(username, role) {
    setBusy(username); setError(''); setMsg('');
    try {
      const r = await api.setRole(username, role);
      setData((d) => ({ ...d, users: r.users }));
      setMsg(`✓ ${username} is now ${data.roles.find((x) => x.key === role)?.label}.`);
      if (username === data.me) {
        await refreshMe();
        if (role !== 'owner') load().catch(() => {});
      }
    } catch (e) { setError(e.message); }
    finally { setBusy(''); }
  }

  return (
    <>
      <PageHeader title="Users & Roles" description="Decide who may change what. Every change and every refused attempt is written to the Activity Log." />
      {error && <p className="card mb-4 p-4 text-sm text-red-600">{error}</p>}
      {msg && <p className="card mb-4 border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{msg}</p>}

      {data && !data.roles_on && (
        <div className="card mb-4 border-amber-200 bg-amber-50/50 p-5">
          <p className="text-lg font-bold text-amber-800">○ Roles are off</p>
          <p className="mt-1 text-sm text-slate-600">
            Right now everyone who can sign in can change everything. Turn roles on to give people only the access they need.
            Nothing changes at first: every current user starts as an <b>Owner</b>, then you lower the ones who need less.
          </p>
          <button type="button" className="btn-gold mt-3" onClick={() => setConfirmSetup(true)}>Turn on roles</button>
        </div>
      )}

      {data && (
        <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
          <div className="card overflow-hidden">
            <table className="w-full text-left text-sm">
              <thead className="bg-cream text-[11px] uppercase tracking-wider text-slate-500">
                <tr><th className="p-3">User</th><th className="p-3">Role</th><th className="hidden p-3 sm:table-cell">Last changed</th></tr>
              </thead>
              <tbody>
                {data.users.map((u) => (
                  <tr key={u.username} className="border-t border-slate-100">
                    <td className="p-3">
                      <p className="font-semibold">{u.username}{u.username === data.me && <span className="ml-1 text-xs text-slate-400">(you)</span>}</p>
                      {!u.can_sign_in && <p className="text-xs text-slate-400">Removed from admin_users.php: cannot sign in</p>}
                    </td>
                    <td className="p-3">
                      {data.roles_on && u.can_sign_in ? (
                        <select
                          className="input !w-auto !py-1.5"
                          value={u.role}
                          disabled={busy !== ''}
                          aria-label={`Role for ${u.username}`}
                          onChange={(e) => change(u.username, e.target.value)}
                        >
                          {data.roles.map((r) => <option key={r.key} value={r.key}>{r.label}</option>)}
                        </select>
                      ) : (
                        <span className={`rounded-full px-2 py-0.5 text-xs font-bold ${ROLE_CLS[u.role] || ''}`}>{data.roles.find((r) => r.key === u.role)?.label}</span>
                      )}
                    </td>
                    <td className="hidden p-3 text-xs text-slate-500 sm:table-cell">{u.updated_at ? `${u.updated_at.slice(0, 16)} by ${u.updated_by}` : '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            <p className="border-t border-slate-100 p-3 text-xs text-slate-500">
              New users and new passwords are still made on the server with <code className="rounded bg-cream px-1">php set-password.php name</code>.
              A new user is a <b>Viewer</b> until you give them a role here.
            </p>
          </div>

          <div className="card p-5">
            <p className="text-xs font-bold uppercase tracking-wider text-slate-500">What each role can do</p>
            <ul className="mt-3 space-y-3">
              {data.roles.map((r) => (
                <li key={r.key}>
                  <p><span className={`rounded-full px-2 py-0.5 text-xs font-bold ${ROLE_CLS[r.key]}`}>{r.label}</span> <span className="text-sm text-slate-600">{r.about}</span></p>
                  <ul className="mt-1 list-disc pl-5 text-xs text-slate-500">
                    {r.permissions.map((p) => <li key={p}>{data.permissions.find((x) => x.key === p)?.label || p}</li>)}
                  </ul>
                </li>
              ))}
            </ul>
          </div>
        </div>
      )}

      <Modal
        open={confirmSetup}
        title="Turn on roles?"
        onClose={() => setConfirmSetup(false)}
        width="max-w-lg"
        footer={<>
          <button type="button" className="btn-ghost" onClick={() => setConfirmSetup(false)}>Cancel</button>
          <button type="button" className="btn-primary" disabled={busy === 'setup'} onClick={setup}>{busy === 'setup' ? 'Turning on…' : 'Turn on roles'}</button>
        </>}
      >
        <ul className="list-disc space-y-1 pl-5 text-sm text-slate-600">
          <li>All {data?.users.length} current users become <b>Owners</b>, so nobody loses access today.</li>
          <li>After that, choose a lower role for people who need less.</li>
          <li>There must always be at least one Owner.</li>
          <li>To switch roles off again later, delete the <code>cms_user_roles</code> table in the database.</li>
        </ul>
      </Modal>
    </>
  );
}
