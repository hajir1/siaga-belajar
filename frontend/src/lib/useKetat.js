import { useEffect, useRef, useState } from 'react';

/**
 * Mode Ketat: mendeteksi pindah tab (visibilitychange) dan jendela kehilangan fokus (blur).
 * Setiap kejadian dilaporkan bersama durasinya saat siswa kembali.
 * Ini hanya catatan "perlu dicek" untuk guru, bukan bukti kecurangan.
 */
export function usePantauFokus(aktif, laporkan) {
  const pergiSejak = useRef(null);
  const jenis = useRef(null);
  const fnLapor = useRef(laporkan);
  fnLapor.current = laporkan;

  useEffect(() => {
    if (!aktif) return undefined;

    const pergi = (j) => {
      if (pergiSejak.current) return;
      pergiSejak.current = Date.now();
      jenis.current = j;
    };
    const kembali = () => {
      if (!pergiSejak.current) return;
      const durasi = Math.round((Date.now() - pergiSejak.current) / 1000);
      const j = jenis.current;
      pergiSejak.current = null;
      // Blur sangat singkat (misalnya menyentuh notifikasi) diabaikan agar tidak berisik.
      if (j === 'blur' && durasi < 2) return;
      fnLapor.current({ jenis: j, durasi_detik: durasi });
    };
    const onVis = () => (document.hidden ? pergi('pindah_tab') : kembali());
    const onBlur = () => pergi('blur');
    const onFocus = () => {
      if (!document.hidden) kembali();
    };

    document.addEventListener('visibilitychange', onVis);
    window.addEventListener('blur', onBlur);
    window.addEventListener('focus', onFocus);
    return () => {
      document.removeEventListener('visibilitychange', onVis);
      window.removeEventListener('blur', onBlur);
      window.removeEventListener('focus', onFocus);
    };
  }, [aktif]);
}

/** Hitung mundur per soal. Mengembalikan sisa detik; onHabis dipanggil sekali saat waktu habis. */
export function useHitungMundur(detik, kunci, onHabis) {
  const fn = useRef(onHabis);
  fn.current = onHabis;
  const [sisa, setSisa] = useState(detik);

  useEffect(() => {
    if (!detik || kunci === null) return undefined;
    setSisa(detik);
    const mulai = Date.now();
    const t = setInterval(() => {
      const s = Math.max(0, detik - Math.floor((Date.now() - mulai) / 1000));
      setSisa(s);
      if (s === 0) {
        clearInterval(t);
        fn.current();
      }
    }, 250);
    return () => clearInterval(t);
  }, [detik, kunci]);

  return sisa;
}
