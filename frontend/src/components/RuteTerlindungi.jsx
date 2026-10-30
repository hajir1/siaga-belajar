import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { BERANDA_PERAN, useAuth } from '../lib/auth';

/**
 * Route guard di sisi klien hanya untuk kenyamanan navigasi.
 * Otorisasi sebenarnya selalu dicek di server (middleware peran + Policy).
 */
export default function RuteTerlindungi({ peran }) {
  const { user, memuat } = useAuth();
  const lokasi = useLocation();

  if (memuat) return <p className="muat" role="status">Memuat...</p>;
  if (!user) return <Navigate to="/masuk" replace state={{ dari: lokasi.pathname }} />;
  if (user.peran !== peran) return <Navigate to={BERANDA_PERAN[user.peran]} replace />;
  return <Outlet />;
}
