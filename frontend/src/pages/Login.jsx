import { useState } from 'react';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { Pesan } from '../components/Ui';
import { BERANDA_PERAN, useAuth } from '../lib/auth';

const AKUN_DEMO = [
  { email: 'guru@siaga.test', label: 'Guru' },
  { email: 'andi@siaga.test', label: 'Siswa (Andi)' },
  { email: 'ortu@siaga.test', label: 'Orang tua' },
];

export default function Login() {
  const { user, masuk } = useAuth();
  const navigate = useNavigate();
  const lokasi = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [galat, setGalat] = useState(null);
  const [kirim, setKirim] = useState(false);

  if (user) return <Navigate to={BERANDA_PERAN[user.peran]} replace />;

  const onSubmit = async (e) => {
    e.preventDefault();
    if (!email.trim() || !password) {
      setGalat('Email dan kata sandi wajib diisi.');
      return;
    }
    setKirim(true);
    setGalat(null);
    try {
      const u = await masuk(email.trim(), password);
      const tujuan = lokasi.state?.dari?.startsWith(BERANDA_PERAN[u.peran]) ? lokasi.state.dari : BERANDA_PERAN[u.peran];
      navigate(tujuan, { replace: true });
    } catch (err) {
      setGalat(err.errors?.email?.[0] || err.message);
    } finally {
      setKirim(false);
    }
  };

  return (
    <main className="halaman-masuk" id="konten">
      <section className="kartu kartu-masuk">
        <h1>Siaga Belajar</h1>
        <p className="redup">Deteksi dini siswa yang mulai tertinggal, supaya bisa dibantu lebih cepat.</p>

        <form onSubmit={onSubmit} noValidate>
          {galat && <Pesan jenis="galat">{galat}</Pesan>}
          <div className="bidang">
            <label htmlFor="email">Email</label>
            <input id="email" type="email" autoComplete="username" value={email} maxLength={255} onChange={(e) => setEmail(e.target.value)} required />
          </div>
          <div className="bidang">
            <label htmlFor="sandi">Kata sandi</label>
            <input id="sandi" type="password" autoComplete="current-password" value={password} maxLength={255} onChange={(e) => setPassword(e.target.value)} required />
          </div>
          <button type="submit" className="tombol tombol-utama tombol-penuh" disabled={kirim}>
            {kirim ? 'Memproses...' : 'Masuk'}
          </button>
        </form>

        <details className="demo">
          <summary>Akun demo untuk juri</summary>
          <p className="redup">Kata sandi semua akun: <code>siaga123</code></p>
          <div className="baris-tombol">
            {AKUN_DEMO.map((a) => (
              <button key={a.email} type="button" className="tombol tombol-garis" onClick={() => { setEmail(a.email); setPassword('siaga123'); }}>
                {a.label}
              </button>
            ))}
          </div>
        </details>
      </section>
    </main>
  );
}
