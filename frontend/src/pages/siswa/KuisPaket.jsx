import { useEffect, useRef, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import AreaSoal from '../../components/AreaSoal';
import StatusSinkron from '../../components/StatusSinkron';
import { Galat, Memuat, Pesan } from '../../components/Ui';
import { api } from '../../lib/api';
import {
  ambilAntrean, ambilPaket, ambilSesiLokal, dengarkan, formatUkuran, simpanPaket, simpanSesiLokal, sinkron, tambahEntri, ubahEntri,
} from '../../lib/antrean';
import { useHitungMundur, usePantauFokus } from '../../lib/useKetat';
import { uuid } from '../../lib/uuid';

const SOAL_PER_SESI = 10;

/**
 * Mode Paket: soal diunduh saat ada sinyal (tanpa kunci jawaban), dikerjakan tanpa sinyal,
 * jawaban antre di perangkat lalu dikirim dan dinilai server saat online.
 */
export default function KuisPaket() {
  const { mapelId } = useParams();
  const [paket, setPaket] = useState(() => ambilPaket(mapelId));
  const [info, setInfo] = useState(null);
  const [galatInfo, setGalatInfo] = useState(null);
  const [mengunduh, setMengunduh] = useState(false);
  const [sesi, setSesi] = useState(() => ambilSesiLokal(mapelId));
  const [pilihan, setPilihan] = useState(null);
  const [baruDisimpan, setBaruDisimpan] = useState(false);
  const [selesaiId, setSelesaiId] = useState(null);
  const [, segarkan] = useState(0);
  const judul = useRef(null);

  useEffect(() => dengarkan(() => segarkan((n) => n + 1)), []);

  useEffect(() => {
    api(`/kuis/mapel/${mapelId}/paket/info`)
      .then((r) => setInfo(r.data))
      .catch(setGalatInfo);
  }, [mapelId]);

  const unduh = async () => {
    setMengunduh(true);
    setGalatInfo(null);
    try {
      const r = await api(`/kuis/mapel/${mapelId}/paket`);
      const p = { ...r.data, kursor: 0 };
      if (!simpanPaket(mapelId, p)) throw new Error('Memori perangkat penuh. Hapus data lain lalu coba lagi.');
      setPaket(p);
    } catch (e) {
      setGalatInfo(e);
    } finally {
      setMengunduh(false);
    }
  };

  const m = paket?.mapel;
  const ketat = Boolean(m?.mode_ketat);
  const soalPaket = paket?.soal ?? [];
  const soal = sesi ? soalPaket.find((s) => s.id === sesi.soal_ids[sesi.posisi]) : null;

  const mulai = () => {
    const ids = [];
    for (let i = 0; i < Math.min(SOAL_PER_SESI, soalPaket.length); i++) ids.push(soalPaket[(paket.kursor + i) % soalPaket.length].id);
    const baru = { id: uuid(), mulai: new Date().toISOString(), soal_ids: ids, posisi: 0, idJawaban: uuid() };
    const p = { ...paket, kursor: (paket.kursor + ids.length) % soalPaket.length };
    simpanPaket(mapelId, p);
    setPaket(p);
    tambahEntri({ mapel_id: Number(mapelId), id_sesi_klien: baru.id, mulai: baru.mulai, selesai: null, jawaban: [], kejadian: [] });
    simpanSesiLokal(mapelId, baru);
    setSesi(baru);
    setSelesaiId(null);
    setPilihan(null);
    setTimeout(() => judul.current?.focus(), 0);
  };

  const simpanJawaban = (pilihanId, waktuHabis = false) => {
    ubahEntri(sesi.id, (e) => ({
      ...e,
      jawaban: [...e.jawaban, {
        soal_id: soal.id, pilihan_id: pilihanId, waktu_habis: waktuHabis, id_unik_klien: sesi.idJawaban, dijawab_pada: new Date().toISOString(),
      }],
    }));
    setBaruDisimpan(true);
    const lanjut = { ...sesi, posisi: sesi.posisi + 1, idJawaban: uuid() };
    setPilihan(null);
    if (lanjut.posisi >= sesi.soal_ids.length) {
      tutup(lanjut);
    } else {
      simpanSesiLokal(mapelId, lanjut);
      setSesi(lanjut);
      setTimeout(() => judul.current?.focus(), 0);
    }
    sinkron().catch(() => {});
  };

  const tutup = (s = sesi) => {
    ubahEntri(s.id, (e) => ({ ...e, selesai: new Date().toISOString() }));
    simpanSesiLokal(mapelId, null);
    setSelesaiId(s.id);
    setSesi(null);
    sinkron().catch(() => {});
  };

  usePantauFokus(ketat && Boolean(sesi), (k) => {
    ubahEntri(sesi.id, (e) => ({ ...e, kejadian: [...e.kejadian, { ...k, id_unik_klien: uuid(), waktu: new Date().toISOString() }] }));
  });

  const sisa = useHitungMundur(ketat ? m?.batas_waktu_per_soal : null, soal ? sesi.idJawaban : null, () => simpanJawaban(null, true));

  // Hasil sesi yang baru selesai (benar/salah tampil setelah sinkron).
  const entriSelesai = selesaiId ? ambilAntrean().find((e) => e.id_sesi_klien === selesaiId) : null;

  return (
    <>
      <div className="baris-antara">
        <Link to="/siswa">← Kembali</Link>
        <span className="lencana">Mode Paket</span>
      </div>
      <h1>{m?.nama ?? 'Latihan'} (Mode Paket)</h1>
      <StatusSinkron baruDisimpan={baruDisimpan} />

      {!paket && (
        <section className="kartu">
          <h2>Unduh paket soal</h2>
          <p>Unduh saat ada sinyal. Setelah itu soal bisa dikerjakan walau sinyal hilang. Kunci jawaban tidak ikut diunduh; jawabanmu dinilai saat terkirim.</p>
          {!info && !galatInfo && <Memuat teks="Menghitung ukuran paket..." />}
          {info && (
            <p>
              <strong>{info.jumlah_soal} soal</strong>, perkiraan ukuran <strong>{formatUkuran(info.perkiraan_ukuran_byte)}</strong>.
            </p>
          )}
          <Galat error={galatInfo} />
          <button type="button" className="tombol tombol-utama" onClick={unduh} disabled={mengunduh || !info}>
            {mengunduh ? 'Mengunduh...' : 'Unduh paket'}
          </button>
        </section>
      )}

      {paket && !sesi && (
        <section className="kartu">
          <p>
            Paket tersimpan di perangkat: {soalPaket.length} soal, diunduh {new Date(paket.dibuat_pada).toLocaleString('id-ID')}.
          </p>
          <div className="baris-tombol">
            <button type="button" className="tombol tombol-utama" onClick={mulai}>
              Mulai latihan ({Math.min(SOAL_PER_SESI, soalPaket.length)} soal)
            </button>
            <button type="button" className="tombol tombol-garis" onClick={unduh} disabled={mengunduh || !info}>
              {mengunduh ? 'Mengunduh...' : 'Perbarui paket'}
            </button>
          </div>
          {info && <p className="redup teks-kecil">Perbarui paket butuh sinyal (sekitar {formatUkuran(info.perkiraan_ukuran_byte)}).</p>}
        </section>
      )}

      {entriSelesai && (
        <section className="kartu" aria-labelledby="hasil-paket">
          <h2 id="hasil-paket">Hasil latihan</h2>
          <ul className="daftar-hasil">
            {entriSelesai.jawaban.map((j, i) => {
              const h = entriSelesai.hasil?.[j.id_unik_klien];
              const s = soalPaket.find((x) => x.id === j.soal_id);
              return (
                <li key={j.id_unik_klien}>
                  <span>{i + 1}. {s?.teks ?? 'Soal'}</span>
                  {!h && <span className="lencana">Menunggu sinyal</span>}
                  {h?.status === 'ditolak' && <span className="lencana">Soal sudah ditarik guru</span>}
                  {h && h.status !== 'ditolak' && (
                    <span className={`lencana ${h.benar ? 'lencana-terbit' : 'lencana-di_bawah_batas'}`}>
                      {h.benar ? '✓ Benar' : `✗ Salah. Jawaban: ${s?.pilihan.find((p) => p.id === h.pilihan_benar_id)?.teks ?? '-'}`}
                    </span>
                  )}
                </li>
              );
            })}
          </ul>
        </section>
      )}

      {sesi && soal && (
        <form className="kartu kuis" onSubmit={(e) => { e.preventDefault(); if (pilihan) simpanJawaban(pilihan); }}>
          <div className="baris-antara">
            <p className="topik-label">
              Soal {sesi.posisi + 1} dari {sesi.soal_ids.length} · {soal.topik}
            </p>
            {ketat && m.batas_waktu_per_soal && (
              <p className={`waktu ${sisa <= 10 ? 'waktu-kritis' : ''}`} role="timer">
                Sisa {sisa} detik
              </p>
            )}
          </div>
          {ketat && <Pesan jenis="peringatan">Mode Ketat aktif. Pindah tab tercatat untuk dicek guru.</Pesan>}
          <AreaSoal ketat={ketat} proteksiTeks={m.proteksi_seleksi_teks}>
            <h2 className="teks-soal" tabIndex={-1} ref={judul}>
              {soal.teks}
            </h2>
            <fieldset>
              <legend className="sr-only">Pilih satu jawaban</legend>
              {soal.pilihan.map((p) => (
                <label key={p.id} className={`pilihan ${pilihan === p.id ? 'dipilih' : ''}`}>
                  <input type="radio" name="pilihan" checked={pilihan === p.id} onChange={() => setPilihan(p.id)} />
                  <span>{p.teks}</span>
                </label>
              ))}
            </fieldset>
          </AreaSoal>
          <p className="redup teks-kecil">Benar/salah tampil setelah jawaban terkirim ke server.</p>
          <div className="baris-tombol">
            <button type="submit" className="tombol tombol-utama" disabled={!pilihan}>
              Simpan jawaban
            </button>
            <button type="button" className="tombol tombol-garis" onClick={() => tutup()} disabled={sesi.posisi === 0}>
              Selesai
            </button>
          </div>
        </form>
      )}
    </>
  );
}
