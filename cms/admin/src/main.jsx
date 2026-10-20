import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
// HashRouter: the built admin is plain static files, so it works from any
// folder on shared hosting without server rewrite rules.
import { HashRouter } from 'react-router-dom';
import App from './App';
import { AuthProvider } from './lib/auth';
import { ToastProvider } from './lib/toast';
import './index.css';

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <HashRouter>
      <ToastProvider>
        <AuthProvider>
          <App />
        </AuthProvider>
      </ToastProvider>
    </HashRouter>
  </StrictMode>,
);
