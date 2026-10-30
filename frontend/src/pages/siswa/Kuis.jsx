import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, Navigate, useParams, useSearchParams } from 'react-router-dom';
import AreaSoal from '../../components/AreaSoal';
import { Galat, Memuat, Pesan } from '../../components/Ui';
import { api } from '../../lib/api';
import { useHitungMundur, usePantauFokus } from '../../lib/useKetat';
import { uuid } from '../../lib/uuid';

export default function Kuis() {
  const { mapelId } = useParams();
  const [params] = useSearchParams();
  const topikId = Number(params.get('topik')) || null;
  const [mapel, setMapel] = useState(null);
  const [sesi, setSesi] = useState(null);
  const [soal, setSoal] = useState(null);
  const [pilihan, setPilihan] = useState(null);
  const [hasil, setHasil] = useState(null);
  const [skor, setSkor] = useState({ benar: 0, total: 0 });
  const [ringkasan, setRingkasan] = useState(null);
  const [galat, setGalat] = useState(null);
  const [sibuk, setSibuk] = useState(false);
  const [jumlahPindah, setJumlahPindah] = useState(0);
  const idJawaban = useRef(null);
  const dimulai = useRef(false);
  const judulSoal = useRef(null);

  const ketat = Boolean(mapel?.mode_ketat);
  const batasWaktu = ketat ? mapel?.batas_waktu_per_soal : null;

  const ambilSoal = useCallback(async (sesiId) => {
    setSibuk(true);
    setGalat(null);
    try {
      const res = await api(`/kuis/sesi/${sesiId}/soal-berikutnya`);
      setSoal(res.data);
      setPilihan(null);
      setHasil(null);
      idJawaban.current = uuid();
      setTimeout(() => judulSoal.current?.focus(), 0);
    } catch (e) {
      setGalat(e);
    } finally {
      setSibuk(false);
    }
  }, []);

  useEffect(() => {
    if (dimulai.current) return;
    dimulai.current = true;
    (async () => {
      try {
        const m = await api(`/mapel/${mapelId}`);
        setMapel(m.data);
        if (m.data.mode_pengerjaan === 'paket' && !topikId) return;
        const res = await api('/kuis/sesi', { method: 'POST', body: { mapel_id: Number(mapelId), topik_id: topikId } });
        setSesi(res.data);
        await ambilSoal(res.data.id);
      } catch (e) {
        setGalat(e);
      }
    })();
  }, [mapelId, topikId, ambilSoal]);

  usePantauFokus(ketat && Boolean(sesi) && !ringkasan, (k) => {
    setJumlahPindah((n) => n + 1);
    api(`/kuis/sesi/${sesi.id}/kejadian`, { method: 'POST', body: k }).catch(() => {});
  });

  const kirimJawaban = async (body) => {
    setSibuk(true);
    setGalat(null);
    try {
      // id_unik_klien sama jika dikirim ulang, jadi server tidak mencatat dua kali.
      const res = await api(`/kuis/sesi/${sesi.id}/jawaban`, {
        method: 'POST',
        body: { soal_id: soal.id, id_unik_klien: idJawaban.current, ...body },
      });
      setHasil({ ...res.data, waktuHabis: Boolean(body.waktu_habis) });
      if (!res.data.duplikat) setSkor((s) => ({ benar: s.benar + (res.data.benar ? 1 : 0), total: s.total + 1 }));
    } catch (err) {
      setGalat(err);
    } finally {
      setSibuk(false);
    }
  };

  const sisa = useHitungMundur(batasWaktu, soal && !hasil ? soal.id + idJawaban.current : null, () => {
    if (!hasil) kirimJawaban({ waktu_habis: true, pilihan_id: null });
  });

  const kirim = (e) => {
    e.preventDefault();
    if (!pilihan || hasil) return;
    kirimJawaban({ pilihan_id: pilihan });
  };

  const selesai = async () => {
    setSibuk(true);
    try {
      const res = await api(`/kuis/sesi/${sesi.id}/selesai`, { method: 'POST' });
      setRingkasan(res.data);
    } catch (err) {
      setGalat(err);
    } finally {
      setSibuk(false);
    }
  };

  if (mapel?.mode_pengerjaan === 'paket' && !topikId) return <Navigate to={`/siswa/paket/${mapelId}`} replace />;

  if (ringkasan) {
    return (
      <section className="kartu">
        <h1>Latihan selesai</h1>
        <p className="angka-besar">
          {ringkasan.jumlah_benar} / {ringkasan.jumlah_soal} benar
        </p>
        <p>Terus berlatih. Hasilmu membantu guru tahu topik mana yang perlu dibahas lagi.</p>
        <Link className="tombol tombol-utama" to="/siswa">
          Kembali
        </Link>
      </section>
    );
  }

  return (
    <>
      <div className="baris-antara">
        <Link to="/siswa">← Kembali</Link>
        <span className="redup" aria-live="polite">
          Benar {skor.benar} dari {skor.total}
        </span>
      </div>

      {sesi?.topik_fokus && <Pesan>Latihan ulang (remedial) topik <strong>{sesi.topik_fokus.nama}</strong>. Soal dimulai dari yang sedikit lebih mudah.</Pesan>}
      {ketat && (
        <Pesan jenis="peringatan">
          <strong>Mode Ketat aktif.</strong> Tetap di halaman ini selama mengerjakan. Pindah tab akan tercatat untuk dicek guru
          {jumlahPindah > 0 ? ` (sudah ${jumlahPindah} kali)` : ''}.
        </Pesan>
      )}

      <Galat error={galat} coba={sesi && !soal ? () => ambilSoal(sesi.id) : undefined} />
      {!soal && !galat && <Memuat teks="Menyiapkan soal..." />}
      {sesi && soal === null && !sibuk && !galat && <Pesan>Belum ada soal yang terbit untuk mapel ini.</Pesan>}

      {soal && (
        <form className="kartu kuis" onSubmit={kirim}>
          <div className="baris-antara">
            <p className="topik-label">{soal.topik}</p>
            {batasWaktu && !hasil && (
              <p className={`waktu ${sisa <= 10 ? 'waktu-kritis' : ''}`} role="timer" aria-live={sisa <= 10 ? 'polite' : 'off'}>
                Sisa {sisa} detik
              </p>
            )}
          </div>
          <AreaSoal ketat={ketat} proteksiTeks={mapel.proteksi_seleksi_teks}>
            <h1 className="teks-soal" tabIndex={-1} ref={judulSoal}>
              {soal.teks}
            </h1>
            <fieldset disabled={Boolean(hasil) || sibuk}>
              <legend className="sr-only">Pilih satu jawaban</legend>
              {soal.pilihan.map((p) => {
                let status = '';
                if (hasil) {
                  if (p.id === hasil.pilihan_benar_id) status = 'pilihan-benar';
                  else if (p.id === pilihan) status = 'pilihan-salah';
                }
                return (
                  <label key={p.id} className={`pilihan ${pilihan === p.id ? 'dipilih' : ''} ${status}`}>
                    <input type="radio" name="pilihan" value={p.id} checked={pilihan === p.id} onChange={() => setPilihan(p.id)} />
                    <span>{p.teks}</span>
                    {status === 'pilihan-benar' && <span className="tanda"> ✓ jawaban benar</span>}
                    {status === 'pilihan-salah' && <span className="tanda"> ✗ pilihanmu</span>}
                  </label>
                );
              })}
            </fieldset>
          </AreaSoal>

          <div aria-live="assertive">
            {hasil && (
              <Pesan jenis={hasil.benar ? 'sukses' : 'peringatan'}>
                {hasil.benar ? 'Benar! Bagus sekali.' : hasil.waktuHabis ? 'Waktu habis. Soal ini akan muncul lagi nanti.' : 'Belum tepat. Soal ini akan muncul lagi nanti.'}
              </Pesan>
            )}
          </div>

          <div className="baris-tombol">
            {!hasil ? (
              <button type="submit" className="tombol tombol-utama" disabled={!pilihan || sibuk}>
                {sibuk ? 'Menilai...' : 'Kirim jawaban'}
              </button>
            ) : (
              <button type="button" className="tombol tombol-utama" onClick={() => ambilSoal(sesi.id)} disabled={sibuk}>
                Soal berikutnya
              </button>
            )}
            <button type="button" className="tombol tombol-garis" onClick={selesai} disabled={sibuk || skor.total === 0}>
              Selesai
            </button>
          </div>
        </form>
      )}
    </>
  );
}
