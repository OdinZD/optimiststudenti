// Provjera Mermaid dijagrama: svaki ```mermaid blok u docs/tehnicka i docs/decisions mora se parsirati i renderirati.
// Zahtijeva privremene alate IZVAN repozitorija:
//   mkdir -p /tmp/mermaid-provjera && cd /tmp/mermaid-provjera && npm i mermaid puppeteer-core
// Pokretanje: MERMAID_DIR=/tmp/mermaid-provjera node docs/tehnicka/alati/mermaid.cjs   (CHROME_BIN po potrebi)
const fs = require('fs');
const path = require('path');

const MERMAID_DIR = process.env.MERMAID_DIR || '/tmp/mermaid-provjera';
const CHROME = process.env.CHROME_BIN || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const puppeteer = require(path.join(MERMAID_DIR, 'node_modules', 'puppeteer-core'));
const mermaidJs = path.join(MERMAID_DIR, 'node_modules', 'mermaid', 'dist', 'mermaid.min.js');
const root = path.join(__dirname, '..', '..', '..');

function mdFiles(dir) {
  if (!fs.existsSync(dir)) return [];
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
    const p = path.join(dir, e.name);
    return e.isDirectory() ? mdFiles(p) : p.endsWith('.md') ? [p] : [];
  });
}

(async () => {
  const blocks = [];
  for (const file of [...mdFiles(path.join(root, 'docs', 'tehnicka')), ...mdFiles(path.join(root, 'docs', 'decisions'))]) {
    const src = fs.readFileSync(file, 'utf8');
    const re = /```mermaid\n([\s\S]*?)```/g;
    let m;
    while ((m = re.exec(src))) {
      const line = src.slice(0, m.index).split('\n').length;
      blocks.push({ file: path.relative(root, file), line, code: m[1] });
    }
  }

  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new' });
  const page = await browser.newPage();
  await page.setContent('<html><body><div id="out"></div></body></html>');
  await page.addScriptTag({ path: mermaidJs });
  await page.evaluate(() => mermaid.initialize({ startOnLoad: false, securityLevel: 'strict' }));

  let bad = 0;
  let i = 0;
  for (const b of blocks) {
    i += 1;
    const res = await page.evaluate(async (code, id) => {
      try {
        await mermaid.parse(code);
        await mermaid.render('g' + id, code);
        return null;
      } catch (e) {
        return String(e && e.message ? e.message : e).split('\n').slice(0, 3).join(' | ');
      }
    }, b.code, i);
    if (res) {
      bad += 1;
      console.log(`GREŠKA ${b.file}:${b.line} — ${res}`);
    }
  }
  console.log(`[${bad === 0 ? 'OK' : 'GREŠKE'}] mermaid — blokova: ${blocks.length}, grešaka: ${bad}`);
  await browser.close();
  process.exit(bad === 0 ? 0 : 1);
})();
