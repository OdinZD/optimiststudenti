// Izrada slika zaslona za upute. Zahtijeva: probnu bazu optimist_upute (vidi README), Google Chrome i
//   npm i --no-save puppeteer-core
// Dva servera na probnoj bazi: BASE_OFF (FEATURE_PAYMENTS=false, osnovne slike) i BASE_ON (FEATURE_PAYMENTS=true, samo slike Članarina).
// Pokretanje: NODE_PATH=$PWD/node_modules node docs/upute/alati/slike.cjs   (slike idu u docs/upute/slike/)
const puppeteer = require('puppeteer-core');
const path = require('path');
const BASE_OFF = process.env.BASE_OFF || 'http://127.0.0.1:8780';
const BASE_ON = process.env.BASE_ON || 'http://127.0.0.1:8781';
const CHROME = process.env.CHROME_BIN || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const OUT = path.join(__dirname, '..', 'slike');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function pass(base, jobs) {
  // Zaseban preglednik po prolazu: kolačići se dijele među portovima iste adrese.
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new' });
  const page = await browser.newPage();
  const go = (url) => page.goto(base + url, { waitUntil: 'networkidle0' });
  // clipH: izreži samo gornjih clipH piksela (stranice s malo sadržaja, da slika ne bude prazna)
  const shot = async (name, clipH) => {
    await sleep(600);
    const vp = page.viewport();
    const opts = { path: path.join(OUT, name + '.png') };
    if (clipH) opts.clip = { x: 0, y: 0, width: vp.width, height: clipH };
    await page.screenshot(opts);
    console.log('ok', name);
  };
  const scrollTo = (text) => page.evaluate((t) => {
    const h = [...document.querySelectorAll('[role=dialog] h3, [role=dialog] h4')].find((x) => x.textContent.includes(t));
    if (h) h.scrollIntoView({ block: 'start' });
  }, text);
  const login = async () => {
    await page.setViewport({ width: 700, height: 520, deviceScaleFactor: 2 });
    await go('/login');
    if (jobs.loginShot) await shot(jobs.loginShot);
    await page.type('input[type=email]', 'trener@primjer.example');
    await page.type('input[type=password]', 'demo-upute-2026');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.keyboard.press('Enter')]);
    await page.setViewport({ width: 1100, height: 760, deviceScaleFactor: 2 });
  };
  await login();
  await jobs.run({ page, go, shot, scrollTo, sleep });
  await browser.close();
}

(async () => {
  // Prolaz 1: Članarine isključene (kao što ih vide treneri danas)
  await pass(BASE_OFF, {
    loginShot: '01-prijava',
    run: async ({ page, go, shot, scrollTo, sleep }) => {
      await go('/polaznici'); await shot('02-popis');
      await go('/polaznici?termin=visnjik-pon'); await shot('03-filter-termin');
      await go('/polaznici');
      await page.click('button[aria-label="Uredi: Probni Ana"]'); await sleep(1200);
      await shot('04-forma-polaznik');
      await scrollTo('Liječnički'); await shot('05-forma-ljecnicki');
      await scrollTo('Natjecanja'); await shot('06-forma-natjecanja');
      await page.keyboard.press('Escape'); await sleep(400);
      await go('/natjecanja'); await shot('07-natjecanja', 380);
      await go('/kata-timovi'); await shot('08-kata-timovi', 420);
      await go('/popisi-natjecanja'); await shot('09-popisi', 300);
      await go('/popisi-natjecanja/1'); await shot('10-popis-detalj');
      await page.setViewport({ width: 390, height: 760, deviceScaleFactor: 2, isMobile: true });
      await go('/polaznici');
      await page.click('button[aria-label="Izbornik"]'); await sleep(500);
      await shot('14-mobitel-izbornik');
    },
  });

  // Prolaz 2: Članarine uključene (samo slike Članarina)
  await pass(BASE_ON, {
    run: async ({ page, go, shot, scrollTo, sleep }) => {
      await go('/clanarine/tarife'); await shot('11-clanarine-tarife', 420);
      await go('/polaznici');
      await page.click('button[aria-label="Uredi: Probni Ana"]'); await sleep(1200);
      await scrollTo('Članarina'); await shot('13-clanarine-forma');
      await page.keyboard.press('Escape'); await sleep(400);
      await go('/polaznici');
      await page.click('button[aria-label="Uredi: Probić Filip"]'); await sleep(1200);
      await scrollTo('Zaduženja'); await shot('15-clanarine-zaduzenja');
      await page.evaluate(() => [...document.querySelectorAll('button')].find((b) => b.textContent.includes('Evidentiraj uplatu'))?.click());
      await sleep(800);
      await page.evaluate(() => {
        const l = [...document.querySelectorAll('[role=dialog] label, [role=dialog] span')].find((x) => x.textContent.includes('Iznos (EUR)'));
        if (l) l.scrollIntoView({ block: 'center' });
      });
      await shot('16-clanarine-uplata');
    },
  });
})();
