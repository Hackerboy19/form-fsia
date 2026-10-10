import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api, setUnauthorizedHandler, tokenStore } from './api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setToken] = useState(tokenStore.get());

  const logout = useCallback(() => {
    tokenStore.clear();
    setToken('');
  }, []);

  // Any 401 from the API (token rotated on the server, etc.) signs the user out.
  useEffect(() => setUnauthorizedHandler(logout), [logout]);

  const storeToken = useCallback((t) => {
    tokenStore.set(t);
    setToken(t);
  }, []);

  // Admin token from config.php (fallback / emergency access).
  const login = useCallback(async (candidate) => {
    await api.verifyToken(candidate.trim());
    storeToken(candidate.trim());
  }, [storeToken]);

  // Username + password: the server returns a 12-hour session token.
  const loginWithPassword = useCallback(async (username, password) => {
    const res = await api.login(username.trim(), password);
    storeToken(res.token);
  }, [storeToken]);

  const value = useMemo(() => ({ isAuthed: Boolean(token), login, loginWithPassword, logout }), [token, login, loginWithPassword, logout]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export const useAuth = () => useContext(AuthContext);
