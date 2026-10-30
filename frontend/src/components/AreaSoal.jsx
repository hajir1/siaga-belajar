import { useAuth } from '../lib/auth';

/**
 * Pembungkus area soal. Saat Mode Ketat aktif:
 * - watermark nama + ID siswa berulang (melacak kebocoran jika soal difoto/discreenshot);
 * - jika proteksi seleksi teks aktif: seleksi teks, klik kanan, salin, dan potong ditahan.
 * Ini MENGURANGI penyalinan, tidak mencegah screenshot atau foto layar.
 * Guru dapat mematikan proteksi seleksi teks agar pembaca layar tidak terhalang.
 */
export default function AreaSoal({ ketat, proteksiTeks, children }) {
  const { user } = useAuth();
  const lindungi = ketat && proteksiTeks;
  const tahan = lindungi ? (e) => e.preventDefault() : undefined;
  const tanda = `${user.nama} · ID ${user.id}`;

  return (
    <div className={`area-soal ${lindungi ? 'tanpa-seleksi' : ''}`} onContextMenu={tahan} onCopy={tahan} onCut={tahan} onDragStart={tahan}>
      {ketat && (
        <div className="watermark" aria-hidden="true">
          <div className="watermark-isi">
            {Array.from({ length: 24 }, (_, i) => (
              <span key={i}>{tanda}</span>
            ))}
          </div>
        </div>
      )}
      {children}
    </div>
  );
}
