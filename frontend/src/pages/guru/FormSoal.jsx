import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { Galat, Memuat, Pesan } from '../../components/Ui';
import { api } from '../../lib/api';
import { useApi } from '../../lib/useApi';

const BATAS = { teks: 1000, pilihan: 500, minPilihan: 2, maxPilihan: 6 };
const pilihanKosong = () => ({ teks: '', benar: false });

/** Validasi klien, sama dengan aturan server (server tetap memvalidasi ulang). */
function validasi(f) {
  const e = {};
  if (!f.topik_id) e.topik_id = 'Pilih topik.';
  if (f.teks.trim().length < 5) e.teks = 'Teks soal minimal 5 karakter.';
  if (f.teks.length > BATAS.teks) e.teks = `Teks soal maksimal ${BATAS.teks} karakter.`;
  if (f.pilihan.length < BATAS.minPilihan) e.pilihan = 'Minimal 2 pilihan jawaban.';
  if (f.pilihan.filter((p) => p.benar).length !== 1) e.pilihan = 'Tandai tepat 1 pilihan yang benar.';
  const teks = f.pilihan.map((p) => p.teks.trim());
  f.pilihan.forEach((p, i) => {
    if (!teks[i]) e[`pilihan.${i}.teks`] = 'Teks pilihan tidak boleh kosong.';
    else if (p.teks.length > BATAS.pilihan) e[`pilihan.${i}.teks`] = `Maksimal ${BATAS.pilihan} karakter.`;
    else if (teks.indexOf(teks[i]) !== i) e[`pilihan.${i}.teks`] = 'Teks pilihan tidak boleh sama.';
  });
  return e;
}

export default function FormSoal() {
  const { soalId } = useParams();
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const mapel = useApi('/mapel');
  const lama = useApi(soalId ? `/guru/soal/${soalId}` : null);

  const [form, setForm] = useState({
    mapel_id: Number(params.get('mapel')) || null,
    topik_id: '',
    teks: '',
    tingkat: 'sedang',
    status: 'draf',
    pilihan: [pilihanKosong(), pilihanKosong(), pilihanKosong(), pilihanKosong()],
  });
  const [galat, setGalat] = useState({});
  const [galatUmum, setGalatUmum] = useState(null);
  const [kirim, setKirim] = useState(false);

  useEffect(() => {
    if (!form.mapel_id && mapel.data?.data.length) setForm((f) => ({ ...f, mapel_id: mapel.data.data[0].id }));
  }, [mapel.data, form.mapel_id]);

  useEffect(() => {
    const s = lama.data?.data;
    if (s) {
      setForm({
        mapel_id: s.mapel_id,
        topik_id: String(s.topik_id),
        teks: s.teks,
        tingkat: s.tingkat,
        status: s.status,
        pilihan: s.pilihan.map((p) => ({ teks: p.teks, benar: p.benar })),
      });
    }
  }, [lama.data]);

  const topik = mapel.data?.data.find((m) => m.id === form.mapel_id)?.topik ?? [];
  const ubah = (k, v) => setForm((f) => ({ ...f, [k]: v }));
  const ubahPilihan = (i, k, v) =>
    setForm((f) => ({
      ...f,
      pilihan: f.pilihan.map((p, j) => (k === 'benar' ? { ...p, benar: i === j } : j === i ? { ...p, [k]: v } : p)),
    }));

  const simpan = async (e) => {
    e.preventDefault();
    const e2 = validasi(form);
    setGalat(e2);
    setGalatUmum(null);
    if (Object.keys(e2).length) return;

    setKirim(true);
    try {
      const body = { ...form, topik_id: Number(form.topik_id) };
      await api(soalId ? `/guru/soal/${soalId}` : '/guru/soal', { method: soalId ? 'PUT' : 'POST', body });
      navigate(`/guru/soal?mapel=${form.mapel_id}`);
    } catch (err) {
      const g = Object.fromEntries(Object.entries(err.errors).map(([k, v]) => [k, v[0]]));
      setGalat(g);
      setGalatUmum(Object.keys(g).length ? 'Periksa kembali isian yang ditandai.' : err.message);
    } finally {
      setKirim(false);
    }
  };

  if (mapel.memuat || lama.memuat) return <Memuat />;

  const idGalat = (k) => (galat[k] ? `galat-${k.replaceAll('.', '-')}` : undefined);
  const TeksGalat = ({ k }) => (galat[k] ? <p className="teks-galat" id={idGalat(k)}>{galat[k]}</p> : null);

  return (
    <>
      <Link to={`/guru/soal${form.mapel_id ? `?mapel=${form.mapel_id}` : ''}`}>← Bank soal</Link>
      <h1>{soalId ? 'Ubah soal' : 'Tambah soal'}</h1>
      <Galat error={mapel.error || lama.error} />

      <form className="kartu" onSubmit={simpan} noValidate>
        {galatUmum && <Pesan jenis="galat">{galatUmum}</Pesan>}

        <div className="dua-kolom">
          <div className="bidang">
            <label htmlFor="mapel">Mapel</label>
            <select id="mapel" value={form.mapel_id ?? ''} onChange={(e) => setForm((f) => ({ ...f, mapel_id: Number(e.target.value), topik_id: '' }))}>
              {mapel.data?.data.map((m) => (
                <option key={m.id} value={m.id}>{m.nama}</option>
              ))}
            </select>
            <TeksGalat k="mapel_id" />
          </div>
          <div className="bidang">
            <label htmlFor="topik">Topik</label>
            <select id="topik" value={form.topik_id} onChange={(e) => ubah('topik_id', e.target.value)} aria-invalid={Boolean(galat.topik_id)} aria-describedby={idGalat('topik_id')}>
              <option value="">Pilih topik</option>
              {topik.map((t) => (
                <option key={t.id} value={t.id}>{t.nama}</option>
              ))}
            </select>
            <TeksGalat k="topik_id" />
          </div>
        </div>

        <div className="bidang">
          <label htmlFor="teks">Teks soal</label>
          <textarea id="teks" rows={4} maxLength={BATAS.teks} value={form.teks} onChange={(e) => ubah('teks', e.target.value)} aria-invalid={Boolean(galat.teks)} aria-describedby={idGalat('teks') || 'bantu-teks'} />
          <p className="redup teks-kecil" id="bantu-teks">{form.teks.length}/{BATAS.teks} karakter</p>
          <TeksGalat k="teks" />
        </div>

        <fieldset className="bidang">
          <legend>Pilihan jawaban (tandai satu yang benar)</legend>
          {form.pilihan.map((p, i) => (
            <div key={i} className="baris-pilihan">
              <input type="radio" name="benar" id={`benar-${i}`} checked={p.benar} onChange={() => ubahPilihan(i, 'benar', true)} aria-label={`Pilihan ${i + 1} benar`} />
              <label htmlFor={`pil-${i}`} className="sr-only">Teks pilihan {i + 1}</label>
              <input id={`pil-${i}`} type="text" maxLength={BATAS.pilihan} value={p.teks} placeholder={`Pilihan ${i + 1}`} onChange={(e) => ubahPilihan(i, 'teks', e.target.value)} aria-invalid={Boolean(galat[`pilihan.${i}.teks`])} aria-describedby={idGalat(`pilihan.${i}.teks`)} />
              <button type="button" className="tombol tombol-ikon" aria-label={`Hapus pilihan ${i + 1}`} disabled={form.pilihan.length <= BATAS.minPilihan} onClick={() => ubah('pilihan', form.pilihan.filter((_, j) => j !== i))}>×</button>
              <TeksGalat k={`pilihan.${i}.teks`} />
            </div>
          ))}
          <TeksGalat k="pilihan" />
          <button type="button" className="tombol tombol-garis" disabled={form.pilihan.length >= BATAS.maxPilihan} onClick={() => ubah('pilihan', [...form.pilihan, pilihanKosong()])}>
            + Tambah pilihan
          </button>
        </fieldset>

        <div className="dua-kolom">
          <div className="bidang">
            <label htmlFor="tingkat">Tingkat</label>
            <select id="tingkat" value={form.tingkat} onChange={(e) => ubah('tingkat', e.target.value)}>
              <option value="mudah">Mudah</option>
              <option value="sedang">Sedang</option>
              <option value="sulit">Sulit</option>
            </select>
          </div>
          <div className="bidang">
            <label htmlFor="status">Status</label>
            <select id="status" value={form.status} onChange={(e) => ubah('status', e.target.value)}>
              <option value="draf">Draf (belum tampil ke siswa)</option>
              <option value="terbit">Terbit</option>
            </select>
          </div>
        </div>

        <button type="submit" className="tombol tombol-utama" disabled={kirim}>
          {kirim ? 'Menyimpan...' : 'Simpan soal'}
        </button>
      </form>
    </>
  );
}
