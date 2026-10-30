import { Link } from 'react-router-dom';
import StatusSinkron from '../../components/StatusSinkron';
import { Galat, Memuat } from '../../components/Ui';
import { ambilAntrean, daftarPaket } from '../../lib/antrean';
import { useApi } from '../../lib/useApi';

export default function BerandaSiswa() {
  const { data, error, memuat, muatUlang } = useApi('/mapel');
  const rek = useApi('/kuis/rekomendasi');
  const adaAntrean = ambilAntrean().length > 0;

  return (
    <>
      <h1>Pilih latihan</h1>
      <p className="redup">Soal menyesuaikan kemampuanmu. Jawaban salah akan muncul lagi supaya kamu bisa mencoba lagi.</p>
      {adaAntrean && <StatusSinkron />}

      {rek.data?.data.length > 0 && (
        <section className="kartu kartu-remedial" aria-labelledby="judul-remedial">
          <h2 id="judul-remedial">Perlu diulang</h2>
          <p className="redup teks-kecil">Topik ini nilainya menurun atau masih di bawah 55. Latihan ulang dimulai dari soal yang sedikit lebih mudah.</p>
          <ul className="daftar-remedial">
            {rek.data.data.map((r) => (
              <li key={r.topik_id}>
                <span>
                  <strong>{r.topik}</strong> <span className="redup teks-kecil">· {r.mapel} · nilai {r.nilai}</span>
                </span>
                <Link className="tombol tombol-garis" to={`/siswa/kuis/${r.mapel_id}?topik=${r.topik_id}`}>
                  Latihan ulang
                </Link>
              </li>
            ))}
          </ul>
        </section>
      )}

      {memuat && <Memuat />}
      {error?.status === 0 ? (
        <section className="kartu">
          <h2>Tidak ada sinyal</h2>
          {daftarPaket().length === 0 ? (
            <p>Belum ada paket soal di perangkat ini. Unduh paket saat ada sinyal.</p>
          ) : (
            <>
              <p>Kamu masih bisa mengerjakan paket yang sudah diunduh:</p>
              <ul className="daftar-remedial">
                {daftarPaket().map((p) => (
                  <li key={p.id}>
                    <span>
                      <strong>{p.nama}</strong> <span className="redup teks-kecil">· {p.jumlah} soal</span>
                    </span>
                    <Link className="tombol tombol-utama" to={`/siswa/paket/${p.id}`}>
                      Kerjakan
                    </Link>
                  </li>
                ))}
              </ul>
            </>
          )}
          <button type="button" className="tombol tombol-garis" onClick={muatUlang}>
            Coba sambung lagi
          </button>
        </section>
      ) : (
        <Galat error={error} coba={muatUlang} />
      )}
      <ul className="daftar-kartu">
        {data?.data.map((m) => (
          <li key={m.id} className="kartu">
            <h2>{m.nama}</h2>
            <p className="redup">
              {m.topik.length} topik · {m.jumlah_soal_terbit} soal
            </p>
            <p className="baris-lencana">
              {m.mode_pengerjaan === 'paket' && <span className="lencana">Mode Paket: bisa dikerjakan saat sinyal hilang</span>}
              {m.mode_ketat && <span className="lencana lencana-turun_beruntun">Mode Ketat</span>}
            </p>
            <Link className="tombol tombol-utama" to={m.mode_pengerjaan === 'paket' ? `/siswa/paket/${m.id}` : `/siswa/kuis/${m.id}`}>
              Mulai latihan
            </Link>
          </li>
        ))}
      </ul>
      {data && data.data.length === 0 && <p>Belum ada mapel. Hubungi gurumu.</p>}
    </>
  );
}
