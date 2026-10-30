// Pembungkus fetch sederhana untuk API Laravel (Sanctum mode token Bearer).
const KUNCI_TOKEN = 'siaga_token';

// Alamat API. Kosong = domain yang sama dengan halaman (bawaan, dipakai saat build disajikan oleh Laravel).
// Isi VITE_API_URL di frontend/.env.production hanya jika frontend di-hosting di domain lain.
const BASIS_API = (import.meta.env.VITE_API_URL || '').replace(/\/+$/, '');

export function ambilToken() {
  try {
    return localStorage.getItem(KUNCI_TOKEN);
  } catch {
    return null;
  }
}

export function simpanToken(token) {
  try {
    if (token) localStorage.setItem(KUNCI_TOKEN, token);
    else localStorage.removeItem(KUNCI_TOKEN);
  } catch {
    /* penyimpanan tidak tersedia */
  }
}

export class ApiError extends Error {
  constructor(status, body) {
    super(body?.message || pesanStatus(status));
    this.status = status;
    this.errors = body?.errors || {};
  }
}

function pesanStatus(status) {
  if (status === 0) return 'Tidak ada sinyal. Periksa koneksi lalu coba lagi.';
  if (status === 401) return 'Sesi berakhir. Silakan masuk lagi.';
  if (status === 403) return 'Anda tidak punya akses.';
  if (status === 404) return 'Data tidak ditemukan.';
  if (status === 429) return 'Terlalu banyak permintaan. Tunggu sebentar.';
  return 'Terjadi kesalahan. Coba lagi.';
}

let saatTidakSah = () => {};
export function aturSaatTidakSah(fn) {
  saatTidakSah = fn;
}

/** Mengunduh berkas (misalnya CSV) dengan token, lalu memicu simpan di browser. */
export async function unduhBerkas(path, namaCadangan) {
  const headers = { Accept: 'text/csv' };
  const token = ambilToken();
  if (token) headers.Authorization = `Bearer ${token}`;
  let res;
  try {
    res = await fetch(`${BASIS_API}/api${path}`, { headers });
  } catch {
    throw new ApiError(0, null);
  }
  if (!res.ok) throw new ApiError(res.status, await res.json().catch(() => null));
  const nama = /filename="([^"]+)"/.exec(res.headers.get('Content-Disposition') || '')?.[1] || namaCadangan;
  const url = URL.createObjectURL(await res.blob());
  const a = document.createElement('a');
  a.href = url;
  a.download = nama;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export async function api(path, { method = 'GET', body } = {}) {
  const headers = { Accept: 'application/json' };
  const token = ambilToken();
  if (token) headers.Authorization = `Bearer ${token}`;
  const formulir = body instanceof FormData;
  if (body !== undefined && !formulir) headers['Content-Type'] = 'application/json';

  let res;
  try {
    res = await fetch(`${BASIS_API}/api${path}`, {
      method,
      headers,
      body: body === undefined ? undefined : formulir ? body : JSON.stringify(body),
    });
  } catch {
    throw new ApiError(0, null);
  }

  const data = res.status === 204 ? null : await res.json().catch(() => null);

  if (!res.ok) {
    if (res.status === 401 && path !== '/login') saatTidakSah();
    throw new ApiError(res.status, data);
  }
  return data;
}
