const KUNCI = 'siaga_tema';

export function temaAwal() {
  try {
    const t = localStorage.getItem(KUNCI);
    if (t === 'gelap' || t === 'terang') return t;
  } catch {
    /* abaikan */
  }
  return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'gelap' : 'terang';
}

export function terapkanTema(tema) {
  document.documentElement.dataset.theme = tema === 'gelap' ? 'dark' : 'light';
  try {
    localStorage.setItem(KUNCI, tema);
  } catch {
    /* abaikan */
  }
}
