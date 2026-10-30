import { Navigate, Route, Routes } from 'react-router-dom';
import Layout from './components/Layout';
import RuteTerlindungi from './components/RuteTerlindungi';
import { BERANDA_PERAN, useAuth } from './lib/auth';
import Login from './pages/Login';
import TidakDitemukan from './pages/TidakDitemukan';
import BerandaSiswa from './pages/siswa/BerandaSiswa';
import Kuis from './pages/siswa/Kuis';
import KuisPaket from './pages/siswa/KuisPaket';
import KualitasSoal from './pages/guru/KualitasSoal';
import DashboardGuru from './pages/guru/DashboardGuru';
import BankSoal from './pages/guru/BankSoal';
import FormSoal from './pages/guru/FormSoal';
import PengaturanMapel from './pages/guru/PengaturanMapel';
import LaporanOrtu from './pages/ortu/LaporanOrtu';

function Awal() {
  const { user, memuat } = useAuth();
  if (memuat) return null;
  return <Navigate to={user ? BERANDA_PERAN[user.peran] : '/masuk'} replace />;
}

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Awal />} />
      <Route path="/masuk" element={<Login />} />

      <Route element={<Layout />}>
        <Route element={<RuteTerlindungi peran="siswa" />}>
          <Route path="/siswa" element={<BerandaSiswa />} />
          <Route path="/siswa/kuis/:mapelId" element={<Kuis />} />
          <Route path="/siswa/paket/:mapelId" element={<KuisPaket />} />
        </Route>

        <Route element={<RuteTerlindungi peran="guru" />}>
          <Route path="/guru" element={<DashboardGuru />} />
          <Route path="/guru/soal" element={<BankSoal />} />
          <Route path="/guru/soal/baru" element={<FormSoal />} />
          <Route path="/guru/soal/:soalId" element={<FormSoal />} />
          <Route path="/guru/mapel" element={<PengaturanMapel />} />
          <Route path="/guru/kualitas" element={<KualitasSoal />} />
          <Route path="/guru/siswa/:siswaId" element={<LaporanOrtu />} />
        </Route>

        <Route element={<RuteTerlindungi peran="ortu" />}>
          <Route path="/ortu" element={<LaporanOrtu />} />
        </Route>

        <Route path="*" element={<TidakDitemukan />} />
      </Route>
    </Routes>
  );
}
