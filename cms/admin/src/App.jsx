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
const loading = <div className="card h-96 animate-pulse bg-cream" />;

export default function App() {
  const { isAuthed } = useAuth();
  if (!isAuthed) return <Login />;

  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<Dashboard />} />
        <Route path="seo" element={<SeoManager />} />
        <Route path="pages/:slug" element={<PageEditor />} />
        <Route path="team" element={<TeamDirectory />} />
        <Route path="news" element={<Suspense fallback={loading}><NewsList /></Suspense>} />
        <Route path="news/:id" element={<Suspense fallback={loading}><NewsEditor /></Suspense>} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  );
}
