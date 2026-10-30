// Hapus hasil build lama di backend/public tanpa menyentuh berkas Laravel (index.php, .htaccess, robots.txt).
import { readdirSync, rmSync } from 'node:fs';

const dir = '../backend/public';
rmSync(`${dir}/assets`, { recursive: true, force: true });
for (const f of readdirSync(dir)) {
  if (/^(sw\.js|workbox-.*\.js|registerSW\.js|manifest\.webmanifest)(\.map)?$/.test(f)) rmSync(`${dir}/${f}`);
}
