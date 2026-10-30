import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Galat, LabelDemo, Memuat, Nilai, PilihMapel } from '../../components/Ui';
import { unduhBerkas } from '../../lib/api';
import { useApi } from '../../lib/useApi';
import { useMapelTerpilih } from '../../lib/useMapel';

const JENIS = { di_bawah_batas: 'Di bawah 55', turun_beruntun: 'Turun 3x berturut-turut' };

function kelasNilai(n) {
  if (n === null || n === undefined) return 'kosong';
  return n >= 75 ? 'baik' : n >= 55 ? 'cukup' : 'kurang';
}

function durasi(detik) {
  return detik < 60 ? `${detik} detik` : `${Math.round(detik / 60)} menit`;
}

export default function DashboardGuru() {
  const { daftar, mapelId, pilih } = useMapelTerpilih();
  const dash = useApi(mapelId ? `/guru/mapel/${mapelId}/dashboard` : null);
  const d = dash.data?.data;
  const berperingatan = d?.siswa.filter((s) => s.peringatan.length > 0) ?? [];
  const perluDicek = d?.siswa.filter((s) => s.kejadian_pindah_tab.jumlah > 0) ?? [];
  const [galatUnduh, setGalatUnduh] = useState(null);

  const ekspor = () => {
    setGalatUnduh(null);
    unduhBerkas(`/guru/mapel/${mapelId}/ekspor/nilai`, 'nilai.csv').catch(setGalatUnduh);
  };

  return (
    <>
      <div className="baris-antara">
        <h1>Dashboard guru</h1>
        <LabelDemo />
      </div>
      <Galat error={daftar.error} coba={daftar.muatUlang} />
      {daftar.data && <PilihMapel mapel={daftar.data.data} nilai={mapelId} onChange={pilih} />}
      {dash.memuat && <Memuat />}
      <Galat error={dash.error} coba={dash.muatUlang} />

      {d && (
        <>
          <section className="kartu" aria-labelledby="judul-peringatan">
            <h2 id="judul-peringatan">Peringatan dini</h2>
            <p className="angka-besar">
              {berperingatan.length} <span className="redup teks-kecil">dari {d.siswa.length} siswa perlu perhatian</span>
            </p>
            <p className="redup teks-kecil">Aturan: nilai topik di bawah 55, atau nilai turun 3 pertemuan berturut-turut.</p>
            {berperingatan.length === 0 ? (
              <p>Belum ada siswa yang perlu perhatian khusus.</p>
            ) : (
              <ul className="daftar-peringatan">
                {berperingatan.map((s) => (
                  <li key={s.id}>
                    <Link to={`/guru/siswa/${s.id}`}>
                      <strong>{s.nama}</strong>
                    </Link>
                    <ul>
                      {s.peringatan.map((p, i) => (
                        <li key={i}>
                          <span className={`lencana lencana-${p.jenis}`}>{JENIS[p.jenis]}</span> {p.pesan}
                        </li>
                      ))}
                    </ul>
                  </li>
                ))}
              </ul>
            )}
          </section>

          <section className="kartu" aria-labelledby="judul-peta">
            <h2 id="judul-peta">Peta kelemahan kelas</h2>
            <ul className="kalimat-kelas">
              {d.kalimat_kelas.map((k) => (
                <li key={k}>{k}</li>
              ))}
            </ul>
            <ul className="batang-topik">
              {d.ringkasan_kelas.map((r) => (
                <li key={r.topik_id}>
                  <div className="baris-antara">
                    <span>{r.topik}</span>
                    <span className="teks-kecil">
                      rata-rata <Nilai nilai={r.rata_rata} /> · {r.persen_lemah}% lemah
                    </span>
                  </div>
                  <div className="batang" role="img" aria-label={`${r.persen_lemah} persen siswa lemah di ${r.topik}`}>
                    <span style={{ width: `${r.persen_lemah}%` }} />
                  </div>
                </li>
              ))}
            </ul>
          </section>

          {(d.mapel.mode_ketat || perluDicek.length > 0) && (
            <section className="kartu" aria-labelledby="judul-dicek">
              <h2 id="judul-dicek">Perlu dicek (Mode Ketat)</h2>
              <p className="redup teks-kecil">
                Pindah tab atau keluar dari halaman saat kuis. Bisa karena alasan wajar (notifikasi, telepon), jadi ini bukan vonis curang.
              </p>
              {perluDicek.length === 0 ? (
                <p>Belum ada catatan pindah tab.</p>
              ) : (
                <ul>
                  {perluDicek.map((s) => (
                    <li key={s.id}>
                      <strong>{s.nama}</strong>: {s.kejadian_pindah_tab.jumlah} kali, total {durasi(s.kejadian_pindah_tab.durasi_detik)}
                    </li>
                  ))}
                </ul>
              )}
            </section>
          )}

          <section className="kartu" aria-labelledby="judul-nilai">
            <div className="baris-antara">
              <h2 id="judul-nilai">Nilai per topik (heatmap)</h2>
              <button type="button" className="tombol tombol-garis" onClick={ekspor}>
                Unduh CSV
              </button>
            </div>
            <Galat error={galatUnduh} />
            <p className="redup teks-kecil">Nilai = persen benar dari 10 jawaban terakhir di topik itu. Tren = nilai per pertemuan, terlama ke terbaru. Hijau ≥ 75, kuning 55-74, merah &lt; 55.</p>
            <div className="tabel-gulir" tabIndex={0} role="region" aria-label="Tabel nilai siswa per topik">
              <table>
                <thead>
                  <tr>
                    <th scope="col">Siswa</th>
                    {d.topik.map((t) => (
                      <th key={t.id} scope="col">
                        {t.nama}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {d.siswa.map((s) => (
                    <tr key={s.id}>
                      <th scope="row">
                        <Link to={`/guru/siswa/${s.id}`}>{s.nama}</Link>
                      </th>
                      {s.topik.map((t) => (
                        <td key={t.topik_id} className={`sel-panas sel-${kelasNilai(t.nilai)} ${t.peringatan.length ? 'sel-peringatan' : ''}`}>
                          <Nilai nilai={t.nilai} />
                          {t.peringatan.length > 0 && <span className="sr-only"> (ada peringatan)</span>}
                          {t.tren.length > 1 && <span className="tren">{t.tren.join(' → ')}</span>}
                        </td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        </>
      )}
    </>
  );
}
