import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useApi } from './useApi';

/** Daftar mapel + mapel terpilih yang disimpan di query string (?mapel=). */
export function useMapelTerpilih() {
  const daftar = useApi('/mapel');
  const [params, setParams] = useSearchParams();
  const [id, setId] = useState(Number(params.get('mapel')) || null);

  useEffect(() => {
    if (!id && daftar.data?.data.length) setId(daftar.data.data[0].id);
  }, [id, daftar.data]);

  const pilih = (baru) => {
    setId(baru);
    const p = new URLSearchParams(params);
    p.set('mapel', String(baru));
    setParams(p, { replace: true });
  };

  const mapel = daftar.data?.data.find((m) => m.id === id) || null;
  return { daftar, mapelId: id, mapel, pilih };
}
