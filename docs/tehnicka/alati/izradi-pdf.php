<?php

declare(strict_types=1);

/**
 * Builds docs/tehnicka/Tehnicka-dokumentacija.pdf from docs/tehnicka/** and docs/decisions/**.
 *
 * Markdown (GitHub flavour) -> one HTML file -> headless Chrome (puppeteer-core) renders Mermaid and prints PDF.
 * Needs temporary tools outside the repo (see mermaid.cjs): mkdir -p /tmp/mermaid-provjera && cd /tmp/mermaid-provjera && npm i mermaid puppeteer-core
 * Usage: php docs/tehnicka/alati/izradi-pdf.php   (MERMAID_DIR and CHROME_BIN override the defaults)
 */

use League\CommonMark\GithubFlavoredMarkdownConverter;

require __DIR__.'/../../../vendor/autoload.php';

$root = realpath(__DIR__.'/../../..');
$tech = $root.'/docs/tehnicka';
$pdf = $tech.'/Tehnicka-dokumentacija.pdf';
$html = sys_get_temp_dir().'/tehnicka-dokumentacija-build.html';

// Reading order. Key = path relative to docs/, value = chapter group label.
$order = [];
$add = static function (string $group, array $files) use (&$order): void {
    foreach ($files as $f) {
        $order[$f] = $group;
    }
};
$add('Pregled', ['tehnicka/README.md', 'tehnicka/01-arhitektura.md']);
$add('Baza podataka', ['tehnicka/02-baza-podataka.md']);
$add('HTTP i Livewire', ['tehnicka/03-http-i-livewire.md']);
$add('Ekrani', [
    'tehnicka/04-ekrani/prijava.md', 'tehnicka/04-ekrani/polaznici.md', 'tehnicka/04-ekrani/natjecanja.md',
    'tehnicka/04-ekrani/popisi-natjecanja.md', 'tehnicka/04-ekrani/kata-timovi.md', 'tehnicka/04-ekrani/clanarine.md',
]);
$add('Domena i integracije', ['tehnicka/05-domena-i-servisi.md', 'tehnicka/06-eracuni.md']);
$add('Sigurnost i konfiguracija', ['tehnicka/07-autorizacija-i-sigurnost.md', 'tehnicka/08-konfiguracija.md']);
$add('Razvoj i operacije', ['tehnicka/09-razvoj-i-testovi.md', 'tehnicka/10-deploy-i-operacije.md', 'tehnicka/11-poznata-ogranicenja.md']);
$decisions = glob($root.'/docs/decisions/0*.md');
sort($decisions);
$add('Arhitekturne odluke (ADR)', array_merge(['decisions/README.md'], array_map(static fn (string $p): string => 'decisions/'.basename($p), $decisions)));

$ids = [];
foreach (array_keys($order) as $i => $rel) {
    $ids[$rel] = 'd'.($i + 1);
}

$slug = static function (string $text): string {
    $text = mb_strtolower(trim(strip_tags(html_entity_decode($text))));
    $text = preg_replace('~[^\p{L}\p{N}\s-]~u', '', $text);

    return trim((string) preg_replace('~\s+~u', '-', $text), '-');
};

$converter = new GithubFlavoredMarkdownConverter(['html_input' => 'strip']);
$chapters = '';
$toc = '';
$lastGroup = '';
foreach ($order as $rel => $group) {
    $path = $root.'/docs/'.$rel;
    if (! is_file($path)) {
        fwrite(STDERR, "Nedostaje datoteka: {$rel}\n");
        exit(1);
    }
    $id = $ids[$rel];
    $dir = dirname($rel);
    $body = (string) $converter->convert((string) file_get_contents($path));

    // Mermaid blocks: <pre><code class="language-mermaid"> -> <pre class="mermaid">
    $body = (string) preg_replace_callback(
        '~<pre><code class="language-mermaid">(.*?)</code></pre>~s',
        static fn (array $m): string => '<pre class="mermaid">'.$m[1].'</pre>',
        $body,
    );

    // Heading ids, so that links of the form file.md#fragment keep working inside the single PDF.
    $body = (string) preg_replace_callback('~<h([1-6])>(.*?)</h\1>~s', static function (array $m) use ($id, $slug): string {
        return '<h'.$m[1].' id="'.$id.'-'.$slug($m[2]).'">'.$m[2].'</h'.$m[1].'>';
    }, $body);

    // Relative links to other documents -> internal anchors; other relative links -> plain text.
    $body = (string) preg_replace_callback('~<a href="([^"#]*)(#[^"]*)?">(.*?)</a>~s', static function (array $m) use ($ids, $dir, $id): string {
        [$all, $href, $frag, $text] = $m + [2 => ''];
        if (preg_match('~^(https?:|mailto:)~', $href)) {
            return $all;
        }
        if ($href === '') {
            return '<a href="#'.$id.'-'.ltrim((string) $frag, '#').'">'.$text.'</a>';
        }
        $parts = [];
        foreach (explode('/', $dir.'/'.$href) as $seg) {
            if ($seg === '..') {
                array_pop($parts);
            } elseif ($seg !== '.' && $seg !== '') {
                $parts[] = $seg;
            }
        }
        $target = implode('/', $parts);
        if (! isset($ids[$target])) {
            return $text;
        }

        return '<a href="#'.$ids[$target].($frag !== '' ? '-'.ltrim((string) $frag, '#') : '').'">'.$text.'</a>';
    }, $body);

    preg_match('~<h1[^>]*>(.*?)</h1>~s', $body, $h1);
    $title = trim(html_entity_decode(strip_tags($h1[1] ?? $rel)));
    $groupHtml = '';
    if ($group !== $lastGroup) {
        $groupHtml = '<div class="group">'.htmlspecialchars($group).'</div>';
        $toc .= '<li class="tg">'.htmlspecialchars($group).'</li>';
        $lastGroup = $group;
    }
    $toc .= '<li><a href="#'.$id.'">'.htmlspecialchars($title).'</a></li>';
    $chapters .= '<section class="chapter" id="'.$id.'">'.$groupHtml.$body.'</section>';
}

$state = trim((string) shell_exec('git -C '.escapeshellarg($root).' rev-parse --short HEAD 2>/dev/null')) ?: 'nepoznato';
$logo = 'file://'.realpath($root.'/public/images/KKoptimist.png');

$page = <<<HTML
<!doctype html>
<html lang="hr"><head><meta charset="utf-8"><title>Optimist — tehnička dokumentacija</title>
<style>
  @page { size: A4 landscape; margin: 14mm 14mm 16mm 14mm; }
  :root { --acc: #2D3E7B; --ink: #14171F; --muted: #5b6475; --line: #d8dce6; }
  * { box-sizing: border-box; }
  body { font-family: "Helvetica Neue", Arial, sans-serif; font-size: 9pt; line-height: 1.4; color: var(--ink); }
  h1, h2, h3, h4 { color: var(--acc); line-height: 1.25; break-after: avoid; }
  h1 { font-size: 20pt; margin: 0 0 4mm; padding-bottom: 2mm; border-bottom: 2px solid var(--acc); }
  h2 { font-size: 14pt; margin: 7mm 0 2mm; }
  h3 { font-size: 11pt; margin: 5mm 0 2mm; }
  h4 { font-size: 10pt; margin: 4mm 0 1mm; }
  .chapter { break-before: page; }
  .group { font-size: 8.5pt; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin-bottom: 2mm; }
  p, li { orphans: 3; widows: 3; }
  code { font-family: Menlo, Consolas, monospace; font-size: 8pt; background: #f1f3f9; padding: 0 1mm; border-radius: 2px; overflow-wrap: anywhere; }
  pre { background: #f6f7fb; border: 1px solid var(--line); padding: 2mm 3mm; font-size: 7.5pt; white-space: pre-wrap; overflow-wrap: anywhere; break-inside: avoid; }
  pre code { background: none; padding: 0; }
  pre.mermaid { background: #fff; border: 1px solid var(--line); text-align: center; white-space: normal; }
  pre.mermaid svg { max-width: 100%; max-height: 165mm; height: auto; }
  table { width: 100%; border-collapse: collapse; margin: 2mm 0 4mm; font-size: 8pt; }
  th { background: var(--acc); color: #fff; text-align: left; padding: 1.4mm 2mm; }
  td { padding: 1.2mm 2mm; border-bottom: 1px solid var(--line); vertical-align: top; overflow-wrap: anywhere; }
  tr { break-inside: avoid; }
  tr:nth-child(even) td { background: #f6f7fb; }
  a { color: var(--acc); text-decoration: none; }
  blockquote { margin: 3mm 0; padding: 2mm 3mm; background: #fff4e5; border-left: 4px solid #e08a00; }
  .cover { break-after: page; }
  .cover img { width: 22mm; margin-top: 6mm; }
  .cover h1 { font-size: 28pt; border: 0; margin: 4mm 0 2mm; }
  .cover .sub { font-size: 12pt; color: var(--muted); margin: 0 0 6mm; }
  .toc { columns: 3; column-gap: 8mm; list-style: none; padding: 0; margin: 0; font-size: 8pt; line-height: 1.5; }
  .toc li.tg { font-weight: bold; color: var(--muted); text-transform: uppercase; font-size: 8pt; letter-spacing: .06em; margin-top: 2mm; break-after: avoid; }
</style></head><body>
<section class="cover">
  <img src="{$logo}" alt="">
  <h1>Optimist — tehnička dokumentacija</h1>
  <p class="sub">Za programera: arhitektura, baza, ekrani, pozivi, eRačuni, sigurnost, deploy i odluke (ADR). Stanje: grana feature/clanarine, commit {$state}.</p>
  <ul class="toc">{$toc}</ul>
</section>
{$chapters}
</body></html>
HTML;

file_put_contents($html, $page);

$node = getenv('NODE_BIN') ?: 'node';
$cmd = sprintf('%s %s %s %s 2>&1', escapeshellarg($node), escapeshellarg(__DIR__.'/pdf.cjs'), escapeshellarg($html), escapeshellarg($pdf));
exec($cmd, $out, $code);
@unlink($html);

if ($code !== 0 || ! is_file($pdf)) {
    fwrite(STDERR, "Izrada PDF-a nije uspjela (kod {$code}):\n".implode("\n", $out)."\n");
    exit(1);
}
echo implode("\n", $out)."\n".'PDF izrađen: '.$pdf.' ('.round(filesize($pdf) / 1024).' KB)'."\n";
