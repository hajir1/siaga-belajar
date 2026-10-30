export function Pesan({ jenis = 'info', children }) {
  return (
    <div className={`pesan pesan-${jenis}`} role={jenis === 'galat' ? 'alert' : 'status'}>
      {children}
    </div>
  );
}

export function Memuat({ teks = 'Memuat...' }) {
  return (
    <p className="muat" role="status">
      {teks}
    </p>
  );
}

export function Galat({ error, coba }) {
  if (!error) return null;
  return (
    <Pesan jenis="galat">
      {error.message}{' '}
      {coba && (
        <button type="button" className="tombol tombol-kecil" onClick={coba}>
          Coba lagi
        </button>
      )}
    </Pesan>
  );
}

export function LabelDemo() {
  return <span className="label-demo">Data demo</span>;
}

/** Warna nilai: hijau >= 75, kuning 55-74, merah < 55. Teks tetap ditulis agar tidak bergantung warna. */
export function Nilai({ nilai }) {
  if (nilai === null || nilai === undefined) return <span className="nilai nilai-kosong">belum ada</span>;
  const kelas = nilai >= 75 ? 'baik' : nilai >= 55 ? 'cukup' : 'kurang';
  return <span className={`nilai nilai-${kelas}`}>{nilai}</span>;
}

export function PilihMapel({ mapel, nilai, onChange, id = 'pilih-mapel' }) {
  return (
    <div className="bidang bidang-sejajar">
      <label htmlFor={id}>Mapel</label>
      <select id={id} value={nilai ?? ''} onChange={(e) => onChange(Number(e.target.value))}>
        {mapel.map((m) => (
          <option key={m.id} value={m.id}>
            {m.nama}
          </option>
        ))}
      </select>
    </div>
  );
}
