import { useState } from 'react';
import { Galat, Memuat, Pesan } from '../../components/Ui';
import { api } from '../../lib/api';
import { useApi } from '../../lib/useApi';

function KartuMapel({ mapel, onSimpan }) {
  const [f, setF] = useState({
    nama: mapel.nama,
    mode_ketat: mapel.mode_ketat,
    mode_pengerjaan: mapel.mode_pengerjaan,
    proteksi_seleksi_teks: mapel.proteksi_seleksi_teks,
    batas_waktu_per_soal: mapel.batas_waktu_per_soal ?? '',
  });
  const [topikBaru, setTopikBaru] = useState('');
  const [pesan, setPesan] = useState(null);
  const [galat, setGalat] = useState(null);
  const id = `m${mapel.id}`;

  const simpan = async (e) => {
    e.preventDefault();
    setPesan(null);
    setGalat(null);
    const batas = f.batas_waktu_per_soal === '' ? null : Number(f.batas_waktu_per_soal);
    if (batas !== null && (batas < 10 || batas > 600)) {
      setGalat({ message: 'Batas waktu harus 10-600 detik, atau kosongkan.' });
      return;
    }
    try {
      await api(`/guru/mapel/${mapel.id}`, { method: 'PATCH', body: { ...f, nama: f.nama.trim(), batas_waktu_per_soal: batas } });
      setPesan('Pengaturan tersimpan.');
      onSimpan();
    } catch (err) {
      setGalat(err);
    }
  };

  const tambahTopik = async (e) => {
    e.preventDefault();
    setPesan(null);
    setGalat(null);
    if (topikBaru.trim().length < 2) {
      setGalat({ message: 'Nama topik minimal 2 karakter.' });
      return;
    }
    try {
      await api(`/guru/mapel/${mapel.id}/topik`, { method: 'POST', body: { nama: topikBaru.trim() } });
      setTopikBaru('');
      setPesan('Topik ditambahkan.');
      onSimpan();
    } catch (err) {
      setGalat({ message: err.errors?.nama?.[0] || err.message });
    }
  };

  return (
    <section className="kartu" aria-labelledby={`${id}-judul`}>
      <h2 id={`${id}-judul`}>{mapel.nama}</h2>
      {pesan && <Pesan jenis="sukses">{pesan}</Pesan>}
      <Galat error={galat} />
      <form onSubmit={simpan}>
        <div className="bidang">
          <label htmlFor={`${id}-nama`}>Nama mapel</label>
          <input id={`${id}-nama`} value={f.nama} maxLength={100} onChange={(e) => setF({ ...f, nama: e.target.value })} />
        </div>
        <label className="saklar">
          <input type="checkbox" checked={f.mode_ketat} onChange={(e) => setF({ ...f, mode_ketat: e.target.checked })} />
          <span>
            <strong>Mode Ketat</strong>
            <span className="redup teks-kecil d-blok">Mengurangi penyalinan soal dan mencatat pindah tab sebagai "perlu dicek".</span>
          </span>
        </label>
        <label className="saklar">
          <input type="checkbox" checked={f.proteksi_seleksi_teks} onChange={(e) => setF({ ...f, proteksi_seleksi_teks: e.target.checked })} />
          <span>
            <strong>Proteksi seleksi teks</strong>
            <span className="redup teks-kecil d-blok">Matikan jika ada siswa yang memakai pembaca layar.</span>
          </span>
        </label>
        <fieldset className="bidang">
          <legend>Mode pengerjaan</legend>
          <label className="saklar">
            <input type="radio" name={`${id}-mode`} checked={f.mode_pengerjaan === 'online'} onChange={() => setF({ ...f, mode_pengerjaan: 'online' })} />
            <span>Online (soal diambil satu per satu)</span>
          </label>
          <label className="saklar">
            <input type="radio" name={`${id}-mode`} checked={f.mode_pengerjaan === 'paket'} onChange={() => setF({ ...f, mode_pengerjaan: 'paket' })} />
            <span>Paket (unduh soal saat ada sinyal, untuk koneksi tidak stabil)</span>
          </label>
        </fieldset>
        <div className="bidang">
          <label htmlFor={`${id}-batas`}>Batas waktu per soal (detik, kosongkan jika tanpa batas)</label>
          <input id={`${id}-batas`} type="number" inputMode="numeric" min={10} max={600} value={f.batas_waktu_per_soal} onChange={(e) => setF({ ...f, batas_waktu_per_soal: e.target.value })} />
        </div>
        <button type="submit" className="tombol tombol-utama">Simpan pengaturan</button>
      </form>

      <h3>Topik</h3>
      <ul className="daftar-chip">
        {mapel.topik.map((t) => (
          <li key={t.id}>{t.nama}</li>
        ))}
      </ul>
      <form className="baris-input" onSubmit={tambahTopik}>
        <label htmlFor={`${id}-topik`} className="sr-only">Nama topik baru</label>
        <input id={`${id}-topik`} placeholder="Topik baru" maxLength={100} value={topikBaru} onChange={(e) => setTopikBaru(e.target.value)} />
        <button type="submit" className="tombol tombol-garis">Tambah</button>
      </form>
    </section>
  );
}

export default function PengaturanMapel() {
  const { data, error, memuat, muatUlang } = useApi('/mapel');
  const [nama, setNama] = useState('');
  const [galat, setGalat] = useState(null);

  const tambah = async (e) => {
    e.preventDefault();
    setGalat(null);
    if (nama.trim().length < 2) {
      setGalat({ message: 'Nama mapel minimal 2 karakter.' });
      return;
    }
    try {
      await api('/guru/mapel', { method: 'POST', body: { nama: nama.trim() } });
      setNama('');
      muatUlang();
    } catch (err) {
      setGalat(err);
    }
  };

  return (
    <>
      <h1>Mapel dan pengaturan</h1>
      {memuat && !data && <Memuat />}
      <Galat error={error} coba={muatUlang} />
      {data?.data.map((m) => (
        <KartuMapel key={`${m.id}-${m.topik.length}`} mapel={m} onSimpan={muatUlang} />
      ))}
      <section className="kartu">
        <h2>Tambah mapel</h2>
        <Galat error={galat} />
        <form className="baris-input" onSubmit={tambah}>
          <label htmlFor="mapel-baru" className="sr-only">Nama mapel baru</label>
          <input id="mapel-baru" placeholder="Nama mapel" maxLength={100} value={nama} onChange={(e) => setNama(e.target.value)} />
          <button type="submit" className="tombol tombol-utama">Tambah</button>
        </form>
      </section>
    </>
  );
}
