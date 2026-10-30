import { useEffect, useState } from 'react';
import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../lib/auth';
import { temaAwal, terapkanTema } from '../lib/tema';

const MENU = {
  guru: [
    { ke: '/guru', label: 'Dashboard', akhir: true },
    { ke: '/guru/soal', label: 'Bank Soal' },
    { ke: '/guru/mapel', label: 'Mapel' },
    { ke: '/guru/kualitas', label: 'Kualitas Soal' },
  ],
  siswa: [{ ke: '/siswa', label: 'Latihan', akhir: true }],
  ortu: [{ ke: '/ortu', label: 'Laporan Anak', akhir: true }],
};

const NAMA_PERAN = { guru: 'Guru', siswa: 'Siswa', ortu: 'Orang tua' };

export default function Layout() {
  const { user, keluar } = useAuth();
  const [tema, setTema] = useState(temaAwal);
  const [online, setOnline] = useState(navigator.onLine);

  useEffect(() => {
    const on = () => setOnline(true);
    const off = () => setOnline(false);
    window.addEventListener('online', on);
    window.addEventListener('offline', off);
    return () => {
      window.removeEventListener('online', on);
      window.removeEventListener('offline', off);
    };
  }, []);

  const gantiTema = () => {
    const baru = tema === 'gelap' ? 'terang' : 'gelap';
    setTema(baru);
    terapkanTema(baru);
  };

  return (
    <>
      <a className="lewati" href="#konten">
        Langsung ke konten
      </a>
      <header className="kepala">
        <div className="kepala-isi">
          <span className="merek" aria-label="Siaga Belajar">
            <svg aria-hidden="true" viewBox="0 0 64 64" width="28" height="28">
              <rect width="64" height="64" rx="14" fill="currentColor" />
              <path d="M18 40l10-10 8 8 12-16" stroke="var(--latar)" strokeWidth="6" fill="none" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            Siaga Belajar
          </span>
          <div className="kepala-aksi">
            <button type="button" className="tombol tombol-ikon" onClick={gantiTema} aria-label={tema === 'gelap' ? 'Pakai mode terang' : 'Pakai mode gelap'}>
              {tema === 'gelap' ? '☀' : '☾'}
            </button>
            {user && (
              <button type="button" className="tombol tombol-garis" onClick={keluar}>
                Keluar
              </button>
            )}
          </div>
        </div>
        {user && (
          <nav className="nav" aria-label="Menu utama">
            {MENU[user.peran].map((m) => (
              <NavLink key={m.ke} to={m.ke} end={m.akhir} className="nav-tautan">
                {m.label}
              </NavLink>
            ))}
            <span className="nav-pengguna">
              {user.nama} · {NAMA_PERAN[user.peran]}
            </span>
          </nav>
        )}
      </header>
      {!online && (
        <div className="pita-offline" role="status">
          Tidak ada sinyal. Mode Paket tetap bisa dipakai; data lain perlu koneksi.
        </div>
      )}
      <main id="konten" className="konten" tabIndex={-1}>
        <Outlet />
      </main>
    </>
  );
}
