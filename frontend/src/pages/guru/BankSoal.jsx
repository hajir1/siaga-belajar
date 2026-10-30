import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Galat, Memuat, Pesan, PilihMapel } from '../../components/Ui';
import { api, unduhBerkas } from '../../lib/api';
import { useApi } from '../../lib/useApi';
import { useMapelTerpilih } from '../../lib/useMapel';

export default function BankSoal() {
  const { daftar, mapelId, mapel, pilih } = useMapelTerpilih();
  const [topikId, setTopikId] = useState('');
  const [status, setStatus] = useState('');
  const [halaman, setHalaman] = useState(1);
  const [galatHapus, setGalatHapus] = useState(null);
  const [berkas, setBerkas] = useState(null);
  const [impor, setImpor] = useState({ sibuk: false, pesan: null, galat: null });

  const kirimImpor = async (e) => {
    e.preventDefault();
    if (!berkas) {
      setImpor({ sibuk: false, pesan: null, galat: ['Pilih berkas CSV terlebih dahulu.'] });
      return;
    }
    if (berkas.size > 1024 * 1024) {
      setImpor({ sibuk: false, pesan: null, galat: ['Ukuran berkas maksimal 1 MB.'] });
      return;
    }
    setImpor({ sibuk: true, pesan: null, galat: null });
    const fd = new FormData();
    fd.append('berkas', berkas);
    try {
      const r = await api(`/guru/mapel/${mapelId}/impor/soal`, { method: 'POST', body: fd });
      const topikBaru = r.data.topik_baru.length ? ` Topik baru: ${r.data.topik_baru.join(', ')}.` : '';
      setImpor({ sibuk: false, pesan: `${r.message}${topikBaru}`, galat: null });
      setBerkas(null);
      e.target.reset();
      daftar.muatUlang();
      soal.muatUlang();
    } catch (err) {
      setImpor({ sibuk: false, pesan: null, galat: err.errors?.berkas || [err.message] });
    }
  };

  const q = new URLSearchParams({ page: String(halaman) });
  if (topikId) q.set('topik_id', topikId);
  if (status) q.set('status', status);
  const soal = useApi(mapelId ? `/guru/mapel/${mapelId}/soal?${q}` : null);

  const hapus = async (s) => {
    if (!window.confirm(`Hapus soal "${s.teks.slice(0, 60)}"?`)) return;
    try {
      await api(`/guru/soal/${s.id}`, { method: 'DELETE' });
      soal.muatUlang();
    } catch (e) {
      setGalatHapus(e);
    }
  };

  const meta = soal.data?.meta;

  return (
    <>
      <div className="baris-antara">
        <h1>Bank soal</h1>
        <Link className="tombol tombol-utama" to={`/guru/soal/baru${mapelId ? `?mapel=${mapelId}` : ''}`}>
          + Tambah soal
        </Link>
      </div>
      <Galat error={daftar.error} coba={daftar.muatUlang} />
      {daftar.data && (
        <div className="filter">
          <PilihMapel mapel={daftar.data.data} nilai={mapelId} onChange={(id) => { pilih(id); setTopikId(''); setHalaman(1); }} />
          <div className="bidang bidang-sejajar">
            <label htmlFor="f-topik">Topik</label>
            <select id="f-topik" value={topikId} onChange={(e) => { setTopikId(e.target.value); setHalaman(1); }}>
              <option value="">Semua</option>
              {mapel?.topik.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.nama}
                </option>
              ))}
            </select>
          </div>
          <div className="bidang bidang-sejajar">
            <label htmlFor="f-status">Status</label>
            <select id="f-status" value={status} onChange={(e) => { setStatus(e.target.value); setHalaman(1); }}>
              <option value="">Semua</option>
              <option value="terbit">Terbit</option>
              <option value="draf">Draf</option>
            </select>
          </div>
        </div>
      )}
      <Galat error={galatHapus} />
      {soal.memuat && <Memuat />}
      <Galat error={soal.error} coba={soal.muatUlang} />

      <ul className="daftar-soal">
        {soal.data?.data.map((s) => (
          <li key={s.id} className="kartu">
            <p className="topik-label">
              {s.topik} · {s.tingkat} · <span className={`lencana lencana-${s.status}`}>{s.status}</span>
            </p>
            <p>{s.teks}</p>
            <div className="baris-tombol">
              <Link className="tombol tombol-garis" to={`/guru/soal/${s.id}`}>
                Ubah
              </Link>
              <button type="button" className="tombol tombol-bahaya" onClick={() => hapus(s)}>
                Hapus
              </button>
            </div>
          </li>
        ))}
      </ul>
      {soal.data && soal.data.data.length === 0 && <p>Belum ada soal.</p>}

      {mapelId && (
        <section className="kartu" aria-labelledby="judul-berkas">
          <h2 id="judul-berkas">Impor dan ekspor (CSV)</h2>
          <p className="redup teks-kecil">
            Kolom: topik; teks; tingkat (mudah/sedang/sulit); status (draf/terbit); benar; salah1 sampai salah5. Jika ada satu baris yang salah, tidak ada soal yang disimpan.
          </p>
          <div className="baris-tombol">
            <button type="button" className="tombol tombol-garis" onClick={() => unduhBerkas('/guru/contoh-format-soal', 'contoh-format-soal.csv').catch((e) => setImpor({ galat: [e.message] }))}>
              Unduh contoh format
            </button>
            <button type="button" className="tombol tombol-garis" onClick={() => unduhBerkas(`/guru/mapel/${mapelId}/ekspor/soal`, 'bank-soal.csv').catch((e) => setImpor({ galat: [e.message] }))}>
              Ekspor bank soal
            </button>
          </div>
          <form className="baris-input mt" onSubmit={kirimImpor}>
            <label htmlFor="berkas-csv" className="sr-only">Berkas CSV soal</label>
            <input id="berkas-csv" type="file" accept=".csv,text/csv" onChange={(e) => setBerkas(e.target.files?.[0] ?? null)} />
            <button type="submit" className="tombol tombol-utama" disabled={impor.sibuk}>
              {impor.sibuk ? 'Mengimpor...' : 'Impor'}
            </button>
          </form>
          {impor.pesan && <Pesan jenis="sukses">{impor.pesan}</Pesan>}
          {impor.galat && (
            <Pesan jenis="galat">
              <ul className="tanpa-titik">
                {impor.galat.map((g) => (
                  <li key={g}>{g}</li>
                ))}
              </ul>
            </Pesan>
          )}
        </section>
      )}

      {meta && meta.last_page > 1 && (
        <nav className="baris-tombol" aria-label="Halaman">
          <button type="button" className="tombol tombol-garis" disabled={halaman <= 1} onClick={() => setHalaman(halaman - 1)}>
            ← Sebelumnya
          </button>
          <span className="redup">
            Halaman {meta.current_page} dari {meta.last_page}
          </span>
          <button type="button" className="tombol tombol-garis" disabled={halaman >= meta.last_page} onClick={() => setHalaman(halaman + 1)}>
            Berikutnya →
          </button>
        </nav>
      )}
    </>
  );
}
