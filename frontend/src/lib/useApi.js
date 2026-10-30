import { useCallback, useEffect, useState } from 'react';
import { api } from './api';

/** Mengambil data GET sederhana: { data, error, memuat, muatUlang }. */
export function useApi(path) {
  const [state, setState] = useState({ data: null, error: null, memuat: Boolean(path) });

  const muat = useCallback(async () => {
    if (!path) return;
    setState((s) => ({ ...s, memuat: true, error: null }));
    try {
      const res = await api(path);
      setState({ data: res, error: null, memuat: false });
    } catch (error) {
      setState({ data: null, error, memuat: false });
    }
  }, [path]);

  useEffect(() => {
    muat();
  }, [muat]);

  return { ...state, muatUlang: muat };
}
