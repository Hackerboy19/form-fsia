import { lazy, Suspense } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { useAuth } from './lib/auth';
import Layout from './components/Layout';
import Login from './components/Login';
import Dashboard from './pages/Dashboard';
import SeoManager from './pages/SeoManager';
import PageEditor from './pages/PageEditor';
import TeamDirectory from './pages/TeamDirectory';
// The news screens carry the rich text editor (most of the bundle): load on demand.
const NewsList = lazy(() => import('./pages/NewsManager').then((m) => ({ default: m.NewsList })));
const NewsEditor = lazy(() => import('./pages/NewsManager').then((m) => ({ default: m.NewsEditor })));
const HomepageEditor = lazy(() => import('./pages/HomepageEditor'));
const SiteCheck = lazy(() => import('./pages/SiteCheck'));
const Profiles = lazy(() => import('./pages/Profiles'));
const Events = lazy(() => import('./pages/Events'));
const ServerCleanup = lazy(() => import('./pages/ServerCleanup'));
const ProfileChanges = lazy(() => import('./pages/ProfileChanges'));
const UsersRoles = lazy(() => import('./pages/UsersRoles'));
const ActivityLog = lazy(() => import('./pages/ActivityLog'));
const loading = <div className="card h-96 animate-pulse bg-cream" />;

export default function App() {
  const { isAuthed, me } = useAuth();
  if (!isAuthed) return <Login />;

  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<Dashboard />} />
        <Route path="seo" element={<SeoManager />} />
        <Route path="events" element={<Suspense fallback={loading}><Events /></Suspense>} />
        <Route path="profiles" element={<Suspense fallback={loading}><Profiles /></Suspense>} />
        <Route path="site-check" element={<Suspense fallback={loading}><SiteCheck /></Suspense>} />
        <Route path="cleanup" element={<Suspense fallback={loading}><ServerCleanup /></Suspense>} />
        <Route path="changes" element={<Suspense fallback={loading}><ProfileChanges /></Suspense>} />
        {/* Single-admin setup: Users & Roles only exists once config.php sets roles_enabled. */}
        <Route path="users" element={me?.roles_available ? <Suspense fallback={loading}><UsersRoles /></Suspense> : <Navigate to="/" replace />} />
        <Route path="activity" element={<Suspense fallback={loading}><ActivityLog /></Suspense>} />
        <Route path="homepage" element={<Suspense fallback={loading}><HomepageEditor /></Suspense>} />
        {/* The live homepage is edited on the Homepage screen; the old text blocks did not reach it. */}
        <Route path="pages/index" element={<Navigate to="/homepage" replace />} />
        <Route path="pages/:slug" element={<PageEditor />} />
        <Route path="team" element={<TeamDirectory />} />
        <Route path="news" element={<Suspense fallback={loading}><NewsList /></Suspense>} />
        <Route path="news/:id" element={<Suspense fallback={loading}><NewsEditor /></Suspense>} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  );
}
