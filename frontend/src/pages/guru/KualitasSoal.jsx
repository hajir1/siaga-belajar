import { Link } from 'react-router-dom';
import { Galat, Memuat, PilihMapel } from '../../components/Ui';
import { useApi } from '../../lib/useApi';
import { useMapelTerpilih } from '../../lib/useMapel';

/** Kalibrasi kualitas soal: tanda untuk ditinjau guru, bukan perubahan otomatis. */
export default function KualitasSoal() {
  const { daftar, mapelId, pilih } = useMapelTerpilih();
  const data = useApi(mapelId ? `/guru/mapel/${mapelId}/kualitas-soal` : null);
  const soal = data.data?.data ?? [];
  const bercatatan = soal.filter((s) => s.catatan.length > 0);

  return (
    <>
      <h1>Kualitas soal</h1>
      <p className="redup">
        Dihitung dari jawaban siswa. Soal dengan minimal 8 jawaban diberi catatan bila terlalu mudah, terlalu sulit, tingkatnya tidak sesuai data, atau ada pengecoh yang tidak pernah dipilih.
      </p>
      <Galat error={daftar.error} coba={daftar.muatUlang} />
      {daftar.data && <PilihMapel mapel={daftar.data.data} nilai={mapelId} onChange={pilih} />}
      {data.memuat && <Memuat />}
      <Galat error={data.error} coba={data.muatUlang} />

      {data.data && (
        <>
          <p>
            <strong>{bercatatan.length}</strong> dari {soal.length} soal perlu ditinjau.
          </p>
          <ul className="daftar-soal">
            {(bercatatan.length ? bercatatan : soal.slice(0, 10)).map((s) => (
              <li key={s.id} className="kartu">
                <p className="topik-label">
                  {s.topik} · {s.tingkat} · {s.jumlah_jawaban} jawaban · {s.persen_benar ?? '-'}% benar · rating {s.rating_awal} → {s.rating_sekarang}
                </p>
                <p>{s.teks}</p>
                {s.catatan.length > 0 && (
                  <ul className="catatan-kualitas">
                    {s.catatan.map((c) => (
                      <li key={c}>{c}</li>
                    ))}
                  </ul>
                )}
                <details>
                  <summary>Sebaran pengecoh</summary>
                  <ul>
                    {s.pengecoh.map((p) => (
                      <li key={p.id}>
                        {p.teks}: dipilih {p.dipilih} kali
                      </li>
                    ))}
                  </ul>
                </details>
                <Link className="tombol tombol-garis" to={`/guru/soal/${s.id}`}>
                  Ubah soal
                </Link>
              </li>
            ))}
          </ul>
        </>
      )}
    </>
  );
}
