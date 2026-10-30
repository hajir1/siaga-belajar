import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Galat, LabelDemo, Memuat, Nilai } from '../../components/Ui';
import { useAuth } from '../../lib/auth';
import { useApi } from '../../lib/useApi';

const STATUS = { baik: 'Sudah baik', cukup: 'Cukup', perlu_bantuan: 'Perlu didampingi' };

/** Teks ringkas untuk dibagikan lewat WhatsApp (tautan wa.me, tanpa API berbayar). */
function teksWhatsApp(d) {
  const baris = [`Laporan belajar ${d.anak.nama} (Siaga Belajar)`, `${d.soal_7_hari} soal dikerjakan dalam 7 hari terakhir.`, ''];
  d.mapel.forEach((m) => {
    baris.push(`*${m.mapel}*`, m.ringkasan);
    m.topik.filter((t) => t.status === 'perlu_bantuan').forEach((t) => baris.push(`- ${t.topik} (${t.nilai}): ${t.saran}`));
    baris.push('');
  });
  return baris.join('\n').trim();
}

/** Dipakai orang tua (/ortu) dan guru (/guru/siswa/:id). Server memastikan hanya data yang berhak. */
export default function LaporanOrtu() {
  const { user } = useAuth();
  const { siswaId } = useParams();
  const anak = useApi(user.peran === 'ortu' ? '/ortu/anak' : null);
  const [terpilih, setTerpilih] = useState(siswaId ? Number(siswaId) : null);

  useEffect(() => {
    if (!terpilih && anak.data?.data.length) setTerpilih(anak.data.data[0].id);
  }, [anak.data, terpilih]);

  const lap = useApi(terpilih ? `/laporan/siswa/${terpilih}` : null);
  const d = lap.data?.data;

  return (
    <>
      {user.peran === 'guru' && <Link to="/guru">← Dashboard</Link>}
      <div className="baris-antara">
        <h1>Laporan belajar{d ? ` ${d.anak.nama}` : ''}</h1>
        <LabelDemo />
      </div>
      <Galat error={anak.error} coba={anak.muatUlang} />
      {anak.data && anak.data.data.length > 1 && (
        <div className="bidang bidang-sejajar">
          <label htmlFor="anak">Anak</label>
          <select id="anak" value={terpilih ?? ''} onChange={(e) => setTerpilih(Number(e.target.value))}>
            {anak.data.data.map((a) => (
              <option key={a.id} value={a.id}>{a.nama}</option>
            ))}
          </select>
        </div>
      )}
      {anak.data && anak.data.data.length === 0 && <p>Belum ada data anak yang terhubung ke akun ini.</p>}
      {lap.memuat && <Memuat />}
      <Galat error={lap.error} coba={lap.muatUlang} />

      {d && (
        <>
          <p className="redup">{d.soal_7_hari} soal dikerjakan dalam 7 hari terakhir.</p>
          <p>
            <a className="tombol tombol-wa" href={`https://wa.me/?text=${encodeURIComponent(teksWhatsApp(d))}`} target="_blank" rel="noopener noreferrer">
              Kirim laporan lewat WhatsApp
            </a>
          </p>
          {d.mapel.map((m) => (
            <section key={m.mapel_id} className="kartu" aria-labelledby={`lap-${m.mapel_id}`}>
              <h2 id={`lap-${m.mapel_id}`}>{m.mapel}</h2>
              <p className="ringkasan">{m.ringkasan}</p>
              <ul className="daftar-topik">
                {m.topik.map((t) => (
                  <li key={t.topik_id} className={`topik-${t.status}`}>
                    <div className="baris-antara">
                      <strong>{t.topik}</strong>
                      <span>
                        <Nilai nilai={t.nilai} /> <span className="teks-kecil">{STATUS[t.status]}</span>
                      </span>
                    </div>
                    {t.peringatan && <p className="teks-kecil teks-peringatan">Nilainya menurun atau di bawah 55. Perlu perhatian.</p>}
                    <p className="saran">
                      <span className="sr-only">Saran: </span>
                      {t.saran}
                    </p>
                  </li>
                ))}
              </ul>
            </section>
          ))}
        </>
      )}
    </>
  );
}
