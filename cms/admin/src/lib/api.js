// Thin fetch wrapper around the PHP API. Every request carries the admin token;
// errors come back as ApiError with the server's per-field messages attached.

const BASE = (import.meta.env.VITE_API_BASE || '/api').replace(/\/$/, '');
const TOKEN_KEY = 'fsia_cms_token';

export const tokenStore = {
  get: () => sessionStorage.getItem(TOKEN_KEY) || '',
  set: (t) => sessionStorage.setItem(TOKEN_KEY, t),
  clear: () => sessionStorage.removeItem(TOKEN_KEY),
};

export class ApiError extends Error {
  constructor(message, status, fields = {}) {
    super(message);
    this.status = status;
    this.fields = fields;
  }
}

// Called on any 401 so the app can drop back to the sign-in screen.
let onUnauthorized = () => {};
export const setUnauthorizedHandler = (fn) => { onUnauthorized = fn; };

async function request(path, { method = 'GET', body, query, token, isForm = false } = {}) {
  const url = new URL(`${BASE}/${path}`, window.location.href);
  Object.entries(query || {}).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, v);
  });

  const headers = { Accept: 'application/json' };
  const auth = token ?? tokenStore.get();
  if (auth) {
    headers.Authorization = `Bearer ${auth}`;
    // Many Apache/PHP-FPM hosts (Plesk included) drop the Authorization header
    // before PHP sees it; the API also reads this one.
    headers['X-Admin-Token'] = auth;
  }
  if (body !== undefined && !isForm) headers['Content-Type'] = 'application/json';

  let res;
  try {
    res = await fetch(url, { method, headers, body: isForm ? body : body !== undefined ? JSON.stringify(body) : undefined });
  } catch {
    throw new ApiError('Cannot reach the server. Check your connection.', 0);
  }

  const data = await res.json().catch(() => null);
  if (!res.ok) {
    if (res.status === 401 && token === undefined) onUnauthorized();
    throw new ApiError(data?.error || `Request failed (${res.status})`, res.status, data?.fields || {});
  }
  return data;
}

export const api = {
  verifyToken: (token) => request('auth.php', { token }),
  login: (username, password) => request('auth.php', { method: 'POST', body: { username, password }, token: '' }),

  listSeo: () => request('seo.php'),
  getSeo: (page) => request('seo.php', { query: { page } }),
  saveSeo: (page, body) => request('seo.php', { method: 'PUT', query: { page }, body }),

  getSections: (page) => request('sections.php', { query: { page } }),
  saveSection: (page, key, body) => request('sections.php', { method: 'PUT', query: { page, key }, body }),

  listTeam: (params) => request('teams.php', { query: { include_inactive: 1, ...params } }),
  createMember: (body) => request('teams.php', { method: 'POST', body }),
  updateMember: (id, body) => request('teams.php', { method: 'PUT', query: { id }, body }),
  deleteMember: (id) => request('teams.php', { method: 'DELETE', query: { id } }),

  listNews: (params) => request('news.php', { query: params }),
  getNews: (id) => request('news.php', { query: { id } }),
  createNews: (body) => request('news.php', { method: 'POST', body }),
  updateNews: (id, body) => request('news.php', { method: 'PUT', query: { id }, body }),
  deleteNews: (id) => request('news.php', { method: 'DELETE', query: { id } }),

  upload: (file) => {
    const form = new FormData();
    form.append('image', file);
    return request('upload.php', { method: 'POST', body: form, isForm: true });
  },
};

// Image paths stored as "/static/media/x.jpg" are relative to the public site,
// not to wherever this admin is hosted. Resolve them for previews.
const SITE = (import.meta.env.VITE_SITE_URL || 'https://www.fsia.in').replace(/\/$/, '');
export const assetUrl = (u) => (u && u.startsWith('/') && !u.startsWith('//') ? SITE + u : u || '');
