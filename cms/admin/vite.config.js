import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

// In development, /api/* is proxied to the PHP backend so the browser sees one
// origin (no CORS). Point VITE_DEV_API_TARGET at wherever backend/ is served.
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  return {
    plugins: [react()],
    base: './', // works from any sub-folder, e.g. https://www.fsia.in/cms-admin/
    server: {
      proxy: {
        '/api': { target: env.VITE_DEV_API_TARGET || 'http://127.0.0.1:8099', changeOrigin: true },
      },
    },
  };
});
