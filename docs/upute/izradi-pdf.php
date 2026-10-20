<?php

declare(strict_types=1);

/**
 * Builds docs/upute/Upute-za-trenere.pdf from docs/upute/upute.md.
 *
 * Markdown (GitHub flavour, for tables) -> HTML (predlozak.html) -> headless Chrome -> PDF.
 * Usage: php docs/upute/izradi-pdf.php   (set CHROME_BIN to override the macOS default path)
 */

use League\CommonMark\GithubFlavoredMarkdownConverter;

require __DIR__.'/../../vendor/autoload.php';

$dir = __DIR__;
$chrome = getenv('CHROME_BIN') ?: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
$pdf = $dir.'/Upute-za-trenere.pdf';
$html = $dir.'/_upute-build.html';

if (! is_executable($chrome)) {
    fwrite(STDERR, "Chrome nije pronađen: {$chrome} (postavi CHROME_BIN)\n");
    exit(1);
}

$converter = new GithubFlavoredMarkdownConverter(['html_input' => 'allow']);
$body = (string) $converter->convert((string) file_get_contents($dir.'/upute.md'));

// First <h1> + the paragraph after it become the cover; <h2> headings feed the table of contents.
preg_match('~<h1>(.*?)</h1>\s*<p>(.*?)</p>~s', $body, $cover);
$body = preg_replace('~<h1>.*?</h1>\s*<p>.*?</p>~s', '', $body, 1);

$toc = '';
$n = 0;
$body = (string) preg_replace_callback('~<h2>(.*?)</h2>~s', static function (array $m) use (&$toc, &$n): string {
    $n++;
    $toc .= '<li>'.$m[1].'</li>';

    // Odjeljci 3, 4 i 6 su kratki: nastavljaju se na istoj stranici umjesto da počnu novu.
    $continues = $n === 1 || preg_match('~^[346]\.~', trim(strip_tags($m[1]))) === 1;

    return '<h2 id="s'.$n.'"'.($continues ? ' class="'.($n === 1 ? 'first' : 'nobreak').'"' : '').'>'.$m[1].'</h2>';
}, $body);

$logo = 'file://'.realpath($dir.'/../../public/images/KKoptimist.png');
$page = strtr((string) file_get_contents($dir.'/predlozak.html'), [
    '{{LOGO}}' => $logo,
    '{{TITLE}}' => $cover[1] ?? 'Upute',
    '{{SUBTITLE}}' => $cover[2] ?? '',
    '{{TOC}}' => $toc,
    '{{BODY}}' => $body,
]);
file_put_contents($html, $page);

@unlink($pdf);
$cmd = sprintf(
    '%s --headless=new --disable-gpu --no-pdf-header-footer --print-to-pdf=%s %s 2>&1',
    escapeshellarg($chrome),
    escapeshellarg($pdf),
    escapeshellarg('file://'.$html),
);
exec($cmd, $out, $code);
@unlink($html);

if ($code !== 0 || ! is_file($pdf)) {
    fwrite(STDERR, "Izrada PDF-a nije uspjela (kod {$code}):\n".implode("\n", $out)."\n");
    exit(1);
}

echo 'PDF izrađen: '.$pdf.' ('.round(filesize($pdf) / 1024).' KB)'."\n";
