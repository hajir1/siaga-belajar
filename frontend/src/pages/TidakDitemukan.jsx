import { Link } from 'react-router-dom';

export default function TidakDitemukan() {
  return (
    <section className="kartu">
      <h1>Halaman tidak ditemukan</h1>
      <p>
        <Link to="/">Kembali ke beranda</Link>
      </p>
    </section>
  );
}
