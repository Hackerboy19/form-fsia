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

  const login = useCallback(async (candidate) => {
    await api.verifyToken(candidate.trim());
    tokenStore.set(candidate.trim());
    setToken(candidate.trim());
  }, []);

  const value = useMemo(() => ({ isAuthed: Boolean(token), login, logout }), [token, login, logout]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export const useAuth = () => useContext(AuthContext);
