import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App';
import { AuthProvider } from './lib/auth';
import { temaAwal, terapkanTema } from './lib/tema';
import { pasangSinkronOtomatis } from './lib/antrean';
import './styles.css';

terapkanTema(temaAwal());
pasangSinkronOtomatis();

// Service worker (PWA): aplikasi tetap terbuka saat sinyal hilang setelah pernah dimuat sekali.
if ('serviceWorker' in navigator && import.meta.env.PROD) {
  import('virtual:pwa-register').then(({ registerSW }) => registerSW({ immediate: true }));
}

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <BrowserRouter>
      <AuthProvider>
        <App />
      </AuthProvider>
    </BrowserRouter>
  </StrictMode>,
);
