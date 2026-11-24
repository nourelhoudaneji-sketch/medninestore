import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const SRC_DIR = process.argv[2] || 'img';
const OUT_DIR = process.argv[3] || 'img/cache';

// Tailles cibles (adaptées à une grille de produits + cartes)
const WIDTHS = [320, 640, 960, 1280];

// On convertit en WebP (professionnel / compatible majorité navigateurs)
// Extension de sortie:
const OUT_EXT = 'webp';

function isImageFile(file) {
  const ext = file.toLowerCase();
  return ext === '.jpg' || ext === '.jpeg' || ext === '.png' || ext === '.webp';
}

function ensureDir(p) {
  fs.mkdirSync(p, { recursive: true });
}

async function fileExists(p) {
  try {
    await fs.promises.access(p);
    return true;
  } catch {
    return false;
  }
}

async function processFile(fullPath, relPath) {
  const baseName = path.parse(relPath).name;
  const outSubdir = path.dirname(relPath); // garde structure éventuelle (ex: img/marques/..)

  for (const w of WIDTHS) {
    const outDirW = path.join(process.cwd(), OUT_DIR, String(w), outSubdir);
    ensureDir(outDirW);

    const outFile = path.join(outDirW, `${baseName}.${OUT_EXT}`);

    if (await fileExists(outFile)) {
      continue;
    }

    // Sharp: resize + WebP qualité raisonnable
    await sharp(fullPath)
      .rotate()
      .resize({ width: w, withoutEnlargement: true })
      .webp({ quality: 82 })
      .toFile(outFile);
  }
}

async function main() {
  ensureDir(path.join(process.cwd(), OUT_DIR));

  async function walk(dir, prefix = '') {
    const entries = await fs.promises.readdir(dir, { withFileTypes: true });
    for (const e of entries) {
      const full = path.join(dir, e.name);
      const rel = path.join(prefix, e.name);

      if (e.isDirectory()) {
        await walk(full, rel);
      } else if (e.isFile() && isImageFile(e.name)) {
        await processFile(full, rel);
      }
    }
  }

  const srcAbs = path.join(process.cwd(), SRC_DIR);
  if (!fs.existsSync(srcAbs)) {
    console.error(`Source dir not found: ${srcAbs}`);
    process.exit(1);
  }

  await walk(srcAbs);
  console.log('✅ Image optimization finished');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});

