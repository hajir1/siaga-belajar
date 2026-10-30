import { useEffect, useState } from 'react';
import { ambilAntrean, dengarkan, jumlahMenunggu, sedangMengirim, sinkron } from '../lib/antrean';

/** Indikator jujur: "Tersimpan di perangkat", "Menunggu sinyal (N jawaban)", "Mengirim...", "Terkirim". */
export default function StatusSinkron({ baruDisimpan = false }) {
  const [, segarkan] = useState(0);
  const [online, setOnline] = useState(navigator.onLine);

  useEffect(() => {
    const lepas = dengarkan(() => segarkan((n) => n + 1));
    const on = () => setOnline(true);
    const off = () => setOnline(false);
    window.addEventListener('online', on);
    window.addEventListener('offline', off);
    return () => {
      lepas();
      window.removeEventListener('online', on);
      window.removeEventListener('offline', off);
    };
  }, []);

  const n = jumlahMenunggu();
  if (n === 0 && !baruDisimpan && ambilAntrean().length === 0) return null;
  let teks;
  let jenis;
  if (sedangMengirim()) {
    teks = `Mengirim ${n} jawaban...`;
    jenis = 'proses';
  } else if (n > 0) {
    teks = online
      ? `Tersimpan di perangkat. Menunggu dikirim (${n} jawaban), dicoba lagi otomatis.`
      : `Tersimpan di perangkat. Menunggu sinyal (${n} jawaban).`;
    jenis = 'menunggu';
  } else {
    teks = 'Terkirim. Semua jawaban sudah dinilai.';
    jenis = 'terkirim';
  }

  return (
    <div className={`status-sinkron status-${jenis}`} role="status" aria-live="polite">
      <span className="titik" aria-hidden="true" />
      <span>{teks}</span>
      {n > 0 && online && !sedangMengirim() && (
        <button type="button" className="tombol tombol-kecil" onClick={() => sinkron().catch(() => {})}>
          Kirim sekarang
        </button>
      )}
    </div>
  );
}
