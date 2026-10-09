import { useState } from 'react';
import { useAuth } from '../lib/auth';

export default function Login() {
  const { login } = useAuth();
  const [token, setToken] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function submit(e) {
    e.preventDefault();
    setBusy(true);
    setError('');
    try {
      await login(token);
    } catch (err) {
      setError(err.status === 401 ? 'That admin token is not valid.' : err.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="grid min-h-screen place-items-center bg-navy p-4">
      <form onSubmit={submit} className="w-full max-w-sm rounded-2xl bg-white p-8 shadow-2xl">
        <p className="font-display text-2xl font-bold">FSIA <span className="text-gold-dark">CMS</span></p>
        <p className="mb-6 mt-1 text-sm text-slate-500">Sign in with the admin token from the server's config.php.</p>
        <label htmlFor="token" className="label">Admin token</label>
        <input
          id="token"
          type="password"
          autoComplete="current-password"
          className="input"
          value={token}
          onChange={(e) => setToken(e.target.value)}
          required
          autoFocus
        />
        {error && <p className="mt-2 text-sm text-red-600" role="alert">{error}</p>}
        <button type="submit" className="btn-gold mt-5 w-full" disabled={busy || !token.trim()}>
          {busy ? 'Checking…' : 'Sign in'}
        </button>
        <p className="mt-4 text-xs text-slate-400">The token is kept for this browser tab only and cleared when you close it.</p>
      </form>
    </div>
  );
}
