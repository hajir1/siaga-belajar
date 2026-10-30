// Mode Paket: paket soal dan antrean jawaban disimpan di localStorage perangkat.
// localStorage dipilih karena paling sederhana dan stabil di HP murah; ukuran paket kecil (puluhan KB).
import { api } from './api';

const K_PAKET = (mapelId) => `siaga_paket_${mapelId}`;
// Antrean dan sesi disimpan per pengguna, sehingga jawaban tidak pernah terkirim atas nama akun lain
// jika satu HP dipakai bergantian.
function idPengguna() {
  try {
    return JSON.parse(localStorage.getItem('siaga_user'))?.id ?? 'anon';
  } catch {
    return 'anon';
  }
}
const K_SESI = (mapelId) => `siaga_sesi_${idPengguna()}_${mapelId}`;
const K_ANTREAN = () => `siaga_antrean_${idPengguna()}`;

function baca(kunci, cadangan) {
  try {
    const v = localStorage.getItem(kunci);
    return v ? JSON.parse(v) : cadangan;
  } catch {
    return cadangan;
  }
}

function tulis(kunci, nilai) {
  try {
    if (nilai === null) localStorage.removeItem(kunci);
    else localStorage.setItem(kunci, JSON.stringify(nilai));
    return true;
  } catch {
    return false; // penyimpanan penuh atau diblokir
  }
}

export const ambilPaket = (mapelId) => baca(K_PAKET(mapelId), null);
export const simpanPaket = (mapelId, paket) => tulis(K_PAKET(mapelId), paket);
export const hapusPaket = (mapelId) => tulis(K_PAKET(mapelId), null);

/** Daftar paket yang tersimpan di perangkat (dipakai saat tidak ada sinyal). */
export function daftarPaket() {
  const hasil = [];
  try {
    for (let i = 0; i < localStorage.length; i++) {
      const k = localStorage.key(i);
      if (k?.startsWith('siaga_paket_')) {
        const p = baca(k, null);
        if (p?.mapel) hasil.push({ id: p.mapel.id, nama: p.mapel.nama, jumlah: p.soal.length });
      }
    }
  } catch {
    /* abaikan */
  }
  return hasil;
}

/** Sesi paket yang sedang dikerjakan di perangkat: { id, mulai, urutan_soal_id, posisi, jawaban: {soal_id: {...}} }. */
export const ambilSesiLokal = (mapelId) => baca(K_SESI(mapelId), null);
export const simpanSesiLokal = (mapelId, sesi) => tulis(K_SESI(mapelId), sesi);

/**
 * Antrean berisi satu entri per sesi: { mapel_id, id_sesi_klien, mulai, selesai, jawaban: [], kejadian: [] }.
 * Hasil sinkron disimpan di entri.hasil agar benar/salah tetap bisa ditampilkan.
 */
export const ambilAntrean = () => baca(K_ANTREAN(), []);
const simpanAntrean = (a) => tulis(K_ANTREAN(), a);

export function ubahEntri(idSesi, ubah) {
  const a = ambilAntrean();
  const i = a.findIndex((e) => e.id_sesi_klien === idSesi);
  if (i === -1) return;
  a[i] = ubah(a[i]);
  simpanAntrean(a);
  beritahu();
}

export function tambahEntri(entri) {
  const a = ambilAntrean();
  if (!a.some((e) => e.id_sesi_klien === entri.id_sesi_klien)) a.push(entri);
  simpanAntrean(a);
  beritahu();
}

function perluDikirim(e) {
  const terkirim = new Set(e.kejadianTerkirim || []);
  return (
    e.jawaban.some((j) => !e.hasil?.[j.id_unik_klien]) ||
    e.kejadian.some((k) => !terkirim.has(k.id_unik_klien)) ||
    (e.selesai && !e.selesaiTerkirim)
  );
}

/** Jumlah jawaban yang belum terkirim ke server. */
export function jumlahMenunggu() {
  return ambilAntrean().reduce((n, e) => n + e.jawaban.filter((j) => !e.hasil?.[j.id_unik_klien]).length, 0);
}

const pendengar = new Set();
export function dengarkan(fn) {
  pendengar.add(fn);
  return () => pendengar.delete(fn);
}
function beritahu() {
  pendengar.forEach((fn) => fn());
}

let sedangSinkron = false;
let percobaan = 0;
let pengatur = null;

/** Kirim semua entri yang belum terkirim. Gagal → coba lagi dengan jeda makin panjang (5 dtk sampai 5 menit). */
export async function sinkron() {
  if (sedangSinkron) return;
  const antre = ambilAntrean().filter((e) => perluDikirim(e));
  if (antre.length === 0) return;
  if (typeof navigator !== 'undefined' && navigator.onLine === false) return jadwalkan();

  sedangSinkron = true;
  beritahu();
  try {
    for (const e of antre) {
      const belum = e.jawaban.filter((j) => !e.hasil?.[j.id_unik_klien]);
      const terkirim = new Set(e.kejadianTerkirim || []);
      const kejadian = e.kejadian.filter((k) => !terkirim.has(k.id_unik_klien)).slice(0, 100);
      // Maksimal 100 jawaban per permintaan; sisanya ikut di putaran berikutnya.
      const res = await api('/kuis/sinkron', {
        method: 'POST',
        body: {
          mapel_id: e.mapel_id,
          id_sesi_klien: e.id_sesi_klien,
          mulai: e.mulai,
          selesai: belum.length <= 100 ? e.selesai : null,
          jawaban: belum.slice(0, 100),
          kejadian,
        },
      });
      ubahEntri(e.id_sesi_klien, (x) => ({
        ...x,
        hasil: { ...(x.hasil || {}), ...Object.fromEntries(res.data.hasil.map((h) => [h.id_unik_klien, h])) },
        kejadianTerkirim: [...(x.kejadianTerkirim || []), ...kejadian.map((k) => k.id_unik_klien)],
        selesaiTerkirim: Boolean(x.selesai) && belum.length <= 100,
        terkirimPada: new Date().toISOString(),
      }));
    }
    percobaan = 0;
  } catch (err) {
    if (err.status === 401) throw err;
    jadwalkan();
  } finally {
    sedangSinkron = false;
    beritahu();
  }
  if (ambilAntrean().some(perluDikirim)) jadwalkan();
}

function jadwalkan() {
  clearTimeout(pengatur);
  const jeda = Math.min(300000, 5000 * 2 ** percobaan);
  percobaan += 1;
  pengatur = setTimeout(() => sinkron().catch(() => {}), jeda);
}

export const sedangMengirim = () => sedangSinkron;

/** Dipanggil sekali saat aplikasi dimuat: sinkron saat sinyal kembali. */
export function pasangSinkronOtomatis() {
  window.addEventListener('online', () => {
    percobaan = 0;
    sinkron().catch(() => {});
  });
  sinkron().catch(() => {});
}

export function formatUkuran(byte) {
  if (byte < 1024) return `${byte} B`;
  if (byte < 1024 * 1024) return `${Math.round(byte / 102.4) / 10} KB`;
  return `${Math.round(byte / 104857.6) / 10} MB`;
}
