/**
 * generate-icons.js
 * Regenerates Android mipmap launcher icons AND splash screen images
 * from assets/images/icon.png using @expo/image-utils.
 *
 * Usage: node scripts/generate-icons.js
 */
const path = require('path');
const fs = require('fs');
const { generateImageAsync } = require('@expo/image-utils');

const ROOT = path.resolve(__dirname, '..');
const ICON_SRC = path.join(ROOT, 'assets/images/icon.png');
const FG_SRC = path.join(ROOT, 'assets/images/android-icon-foreground.png');
const BG_SRC = path.join(ROOT, 'assets/images/android-icon-background.png');
const RES_DIR = path.join(ROOT, 'android/app/src/main/res');

// Android mipmap sizes (legacy square icon)
const ICON_SIZES = {
  'mipmap-mdpi':    48,
  'mipmap-hdpi':    72,
  'mipmap-xhdpi':   96,
  'mipmap-xxhdpi':  144,
  'mipmap-xxxhdpi': 192,
};

// Adaptive icon layer sizes (foreground = 108dp base, scales per density)
const ADAPTIVE_SIZES = {
  'mipmap-mdpi':    108,
  'mipmap-hdpi':    162,
  'mipmap-xhdpi':   216,
  'mipmap-xxhdpi':  324,
  'mipmap-xxxhdpi': 432,
};

// Splash screen logo sizes per drawable density
const SPLASH_SIZES = {
  'drawable-mdpi':    200,
  'drawable-hdpi':    300,
  'drawable-xhdpi':   400,
  'drawable-xxhdpi':  600,
  'drawable-xxxhdpi': 800,
};

async function resizeToPng(srcFile, destFile, size, background = 'transparent') {
  const result = await generateImageAsync(
    { projectRoot: ROOT, cacheType: 'none' },
    {
      src: srcFile,
      width: size,
      height: size,
      resizeMode: 'contain',
      backgroundColor: background,
    }
  );
  // API returns { name, source } where source is a Buffer
  const buffer = result.source ?? result.data;
  fs.writeFileSync(destFile, buffer);
  console.log(`  ✓ ${path.relative(ROOT, destFile)} (${size}x${size})`);
}

async function main() {
  // ── Launcher icons ───────────────────────────────────────────────────────
  console.log('\n🎨 Launcher icons (ic_launcher)...');
  for (const [density, size] of Object.entries(ICON_SIZES)) {
    const dir = path.join(RES_DIR, density);
    fs.mkdirSync(dir, { recursive: true });
    await resizeToPng(ICON_SRC, path.join(dir, 'ic_launcher.png'), size, '#ffffff');
    await resizeToPng(ICON_SRC, path.join(dir, 'ic_launcher_round.png'), size, '#ffffff');
  }

  // ── Adaptive icon layers ─────────────────────────────────────────────────
  console.log('\n🎨 Adaptive icon layers (foreground / background)...');
  for (const [density, size] of Object.entries(ADAPTIVE_SIZES)) {
    const dir = path.join(RES_DIR, density);
    await resizeToPng(FG_SRC, path.join(dir, 'ic_launcher_foreground.png'), size);
    await resizeToPng(BG_SRC, path.join(dir, 'ic_launcher_background.png'), size);
  }

  // ── Adaptive icon XMLs ───────────────────────────────────────────────────
  const anydpiDir = path.join(RES_DIR, 'mipmap-anydpi-v26');
  fs.mkdirSync(anydpiDir, { recursive: true });
  const adapterXml = `<?xml version="1.0" encoding="utf-8"?>
<adaptive-icon xmlns:android="http://schemas.android.com/apk/res/android">
    <background android:drawable="@mipmap/ic_launcher_background"/>
    <foreground android:drawable="@mipmap/ic_launcher_foreground"/>
</adaptive-icon>`;
  fs.writeFileSync(path.join(anydpiDir, 'ic_launcher.xml'), adapterXml);
  fs.writeFileSync(path.join(anydpiDir, 'ic_launcher_round.xml'), adapterXml);
  console.log('  ✓ mipmap-anydpi-v26/ic_launcher.xml');

  // ── Splash screen logo ───────────────────────────────────────────────────
  console.log('\n🌅 Splash screen logos (splashscreen_logo)...');
  for (const [density, size] of Object.entries(SPLASH_SIZES)) {
    const dir = path.join(RES_DIR, density);
    fs.mkdirSync(dir, { recursive: true });
    await resizeToPng(ICON_SRC, path.join(dir, 'splashscreen_logo.png'), size);
  }

  console.log('\n✅ All done! Run: npm run android');
}

main().catch(err => {
  console.error('\n❌ Error:', err.message);
  process.exit(1);
});
