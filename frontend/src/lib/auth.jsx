import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { sinkron } from './antrean';
import { api, ambilToken, aturSaatTidakSah, simpanToken } from './api';

const AuthContext = createContext(null);
const K_USER = 'siaga_user';

// Data pengguna disimpan agar aplikasi tetap terbuka saat sinyal hilang (Mode Paket).
// Otorisasi tetap di server: token diperiksa setiap permintaan API.
function userTersimpan() {
  try {
    return JSON.parse(localStorage.getItem(K_USER)) || null;
  } catch {
    return null;
  }
}
function simpanUser(u) {
  try {
    if (u) localStorage.setItem(K_USER, JSON.stringify(u));
    else localStorage.removeItem(K_USER);
  } catch {
    /* abaikan */
  }
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [memuat, setMemuat] = useState(Boolean(ambilToken()));

  const keluarLokal = useCallback(() => {
    simpanToken(null);
    simpanUser(null);
    setUser(null);
  }, []);

  useEffect(() => {
    aturSaatTidakSah(keluarLokal);
    if (!ambilToken()) return;
    api('/me')
      .then((res) => {
        setUser(res.data);
        simpanUser(res.data);
      })
      .catch((e) => {
        // Tanpa sinyal: tetap masuk dengan data tersimpan. Token tidak sah: keluar.
        if (e.status === 0 && userTersimpan()) setUser(userTersimpan());
        else keluarLokal();
      })
      .finally(() => setMemuat(false));
  }, [keluarLokal]);

  const masuk = useCallback(async (email, password) => {
    const res = await api('/login', { method: 'POST', body: { email, password } });
    simpanToken(res.token);
    simpanUser(res.user);
    setUser(res.user);
    // Kirim jawaban Mode Paket milik akun ini yang tertunda.
    sinkron().catch(() => {});
    return res.user;
  }, []);

  const keluar = useCallback(async () => {
    try {
      await api('/logout', { method: 'POST' });
    } catch {
      /* tetap keluar di perangkat */
    }
    keluarLokal();
  }, [keluarLokal]);

  const nilai = useMemo(() => ({ user, memuat, masuk, keluar }), [user, memuat, masuk, keluar]);
  return <AuthContext.Provider value={nilai}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  return useContext(AuthContext);
}

export const BERANDA_PERAN = { guru: '/guru', siswa: '/siswa', ortu: '/ortu' };
