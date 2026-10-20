// Prints an HTML file to PDF after rendering its Mermaid blocks. Called by izradi-pdf.php.
// Usage: node pdf.cjs <input.html> <output.pdf>   (MERMAID_DIR, CHROME_BIN as in mermaid.cjs)
const path = require('path');

const MERMAID_DIR = process.env.MERMAID_DIR || '/tmp/mermaid-provjera';
const CHROME = process.env.CHROME_BIN || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const puppeteer = require(path.join(MERMAID_DIR, 'node_modules', 'puppeteer-core'));
const mermaidJs = path.join(MERMAID_DIR, 'node_modules', 'mermaid', 'dist', 'mermaid.min.js');

(async () => {
  const [input, output] = process.argv.slice(2);
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new' });
  const page = await browser.newPage();
  await page.goto('file://' + input, { waitUntil: 'load' });
  await page.addScriptTag({ path: mermaidJs });
  const result = await page.evaluate(async () => {
    mermaid.initialize({ startOnLoad: false, securityLevel: 'strict', er: { useMaxWidth: true }, sequence: { useMaxWidth: true }, flowchart: { useMaxWidth: true } });
    const nodes = Array.from(document.querySelectorAll('pre.mermaid'));
    let failed = 0;
    for (const n of nodes) {
      try {
        await mermaid.run({ nodes: [n] });
      } catch (e) {
        failed += 1;
      }
    }
    return { total: nodes.length, failed };
  });
  console.log(`Mermaid: ${result.total} dijagrama, ${result.failed} neuspjelih`);
  await page.pdf({
    path: output,
    format: 'A4',
    landscape: true,
    printBackground: true,
    displayHeaderFooter: true,
    headerTemplate: '<span></span>',
    footerTemplate: '<div style="font:7.5pt Helvetica,Arial,sans-serif;color:#6b7280;width:100%;text-align:center">Optimist — tehnička dokumentacija · stranica <span class="pageNumber"></span> / <span class="totalPages"></span></div>',
    margin: { top: '14mm', bottom: '16mm', left: '14mm', right: '14mm' },
  });
  await browser.close();
  process.exit(result.failed === 0 ? 0 : 1);
})();
