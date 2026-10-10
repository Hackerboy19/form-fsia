import { useState } from 'react';
import { useAuth } from '../lib/auth';

export default function Login() {
  const { login, loginWithPassword } = useAuth();
  const [mode, setMode] = useState('password'); // 'password' | 'token'
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [token, setToken] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function submit(e) {
    e.preventDefault();
    setBusy(true);
    setError('');
    try {
      if (mode === 'password') await loginWithPassword(username, password);
      else await login(token);
    } catch (err) {
      if (err.status === 401) setError(mode === 'password' ? 'Wrong username or password.' : 'That admin token is not valid.');
      else setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  const ready = mode === 'password' ? username.trim() && password : token.trim();

  return (
    <div className="grid min-h-screen place-items-center bg-navy p-4">
      <form onSubmit={submit} className="w-full max-w-sm rounded-2xl bg-white p-8 shadow-2xl">
        <p className="font-display text-2xl font-bold">FSIA <span className="text-gold-dark">CMS</span></p>
        <p className="mb-6 mt-1 text-sm text-slate-500">
          {mode === 'password' ? 'Sign in with your admin username and password.' : "Sign in with the admin token from the server's config.php."}
        </p>

        {mode === 'password' ? (
          <>
            <label htmlFor="username" className="label">Username</label>
            <input id="username" className="input" autoComplete="username" value={username} onChange={(e) => setUsername(e.target.value)} required autoFocus />
            <label htmlFor="password" className="label mt-4">Password</label>
            <input id="password" type="password" className="input" autoComplete="current-password" value={password} onChange={(e) => setPassword(e.target.value)} required />
          </>
        ) : (
          <>
            <label htmlFor="token" className="label">Admin token</label>
            <input id="token" type="password" autoComplete="off" className="input" value={token} onChange={(e) => setToken(e.target.value)} required autoFocus />
          </>
        )}

        {error && <p className="mt-2 text-sm text-red-600" role="alert">{error}</p>}
        <button type="submit" className="btn-gold mt-5 w-full" disabled={busy || !ready}>
          {busy ? 'Checking…' : 'Sign in'}
        </button>
        <button
          type="button"
          className="mt-4 w-full text-center text-xs font-semibold text-slate-500 hover:text-navy"
          onClick={() => { setMode(mode === 'password' ? 'token' : 'password'); setError(''); }}
        >
          {mode === 'password' ? 'Use the admin token instead' : 'Use username and password'}
        </button>
        <p className="mt-3 text-xs text-slate-400">Your sign-in lasts up to 12 hours and ends when you close this tab.</p>
      </form>
    </div>
  );
}
