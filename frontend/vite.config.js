import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

// Hasil build masuk ke backend/public agar SPA dan API disajikan dari satu domain.
export default defineConfig({
  plugins: [
    react(),
    // PWA: manifest + service worker agar aplikasi bisa dibuka tanpa sinyal setelah dimuat sekali.
    // API tidak di-cache oleh service worker; data Mode Paket disimpan sendiri di localStorage.
    VitePWA({
      registerType: 'autoUpdate',
      injectRegister: false,
      manifest: {
        name: 'Siaga Belajar',
        short_name: 'Siaga Belajar',
        description: 'Deteksi dini siswa yang mulai tertinggal.',
        lang: 'id',
        start_url: '/',
        scope: '/',
        display: 'standalone',
        background_color: '#f7f7f4',
        theme_color: '#0f766e',
        icons: [
          { src: '/icons/ikon-192.png', sizes: '192x192', type: 'image/png' },
          { src: '/icons/ikon-512.png', sizes: '512x512', type: 'image/png' },
          { src: '/icons/ikon-512-maskable.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,svg,png,ico,webmanifest}'],
        globIgnores: ['**/index.php'],
        navigateFallback: '/index.html',
        navigateFallbackDenylist: [/^\/api\//, /^\/up$/],
        cleanupOutdatedCaches: true,
      },
    }),
  ],
  build: {
    outDir: '../backend/public',
    emptyOutDir: false,
    assetsDir: 'assets',
  },
  server: {
    proxy: { '/api': 'http://localhost:8000' },
  },
});
