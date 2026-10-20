<?php

declare(strict_types=1);

/**
 * Provjera da tehnička dokumentacija odgovara kodu i bazi.
 *
 * Pokretanje: php docs/tehnicka/alati/provjeri.php [shema|rute|livewire|env|dogadjaji|linkovi|pii]
 * (bez argumenta radi sve provjere; izlazni kod 1 ako išta ne valja).
 *
 * Koristi lokalnu bazu SAMO za metapodatke (information_schema) i, za provjeru osobnih podataka, za čitanje
 * prezimena radi usporedbe; nikad ne ispisuje imena, samo brojeve pogodaka.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$docs = $root.'/docs/tehnicka';
$failures = 0;

function read(string $path): string
{
    return is_file($path) ? (string) file_get_contents($path) : '';
}

function report(string $name, array $problems, int $checked): void
{
    global $failures;
    $status = $problems === [] ? 'OK' : 'GREŠKE';
    echo sprintf("[%s] %s — provjereno: %d, problema: %d\n", $status, $name, $checked, count($problems));
    foreach (array_slice($problems, 0, 40) as $p) {
        echo "    - {$p}\n";
    }
    if (count($problems) > 40) {
        echo '    … i još '.(count($problems) - 40)." problema\n";
    }
    $failures += count($problems);
}

/** @return list<string> */
function mdFiles(string $dir): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && $f->getExtension() === 'md') {
            $out[] = $f->getPathname();
        }
    }
    sort($out);

    return $out;
}

$only = $argv[1] ?? 'sve';
$run = static fn (string $name): bool => $only === 'sve' || $only === $name;

// ---------------------------------------------------------------- shema
if ($run('shema')) {
    $doc = read($docs.'/02-baza-podataka.md');
    $problems = [];
    $checked = 0;

    // sekcije "### `tablica`" i redovi tablice "| `stupac` | tip | da/ne | ..."
    $documented = [];
    $current = null;
    foreach (preg_split('/\R/', $doc) as $line) {
        if (preg_match('/^###\s+`([a-z0-9_]+)`/', $line, $m)) {
            $current = $m[1];
            $documented[$current] ??= [];

            continue;
        }
        if ($current !== null && preg_match('/^\|\s*`([a-z0-9_]+)`\s*\|\s*([^|]+?)\s*\|\s*(da|ne)\s*\|/i', $line, $m)) {
            $documented[$current][$m[1]] = ['tip' => strtolower(str_replace(' ', '', $m[2])), 'null' => strtolower($m[3])];
        }
    }

    $norm = static fn (string $t): string => preg_replace('/\b(bigint|int|smallint|tinyint|mediumint)\(\d+\)/', '$1', strtolower(str_replace(' ', '', $t)));

    $rows = DB::select('select table_name as t, column_name as c, is_nullable as n, column_type as ct from information_schema.columns where table_schema = database() order by table_name, ordinal_position');
    $live = [];
    foreach ($rows as $r) {
        $live[$r->t][$r->c] = ['tip' => $norm($r->ct), 'null' => $r->n === 'YES' ? 'da' : 'ne'];
    }

    foreach ($live as $table => $cols) {
        if (! isset($documented[$table])) {
            $problems[] = "tablica `{$table}` nema odjeljak u 02-baza-podataka.md";

            continue;
        }
        foreach ($cols as $col => $meta) {
            $checked++;
            if (! isset($documented[$table][$col])) {
                $problems[] = "`{$table}.{$col}` nije dokumentiran";

                continue;
            }
            $d = $documented[$table][$col];
            if ($norm($d['tip']) !== $meta['tip'] && ! str_starts_with($meta['tip'], $norm($d['tip']))) {
                $problems[] = "`{$table}.{$col}` tip: dokumentirano {$d['tip']}, baza {$meta['tip']}";
            }
            if ($d['null'] !== $meta['null']) {
                $problems[] = "`{$table}.{$col}` null: dokumentirano {$d['null']}, baza {$meta['null']}";
            }
        }
    }
    foreach ($documented as $table => $cols) {
        if (! isset($live[$table])) {
            $problems[] = "dokumentirana tablica `{$table}` ne postoji u bazi";

            continue;
        }
        foreach (array_keys($cols) as $col) {
            if (! isset($live[$table][$col])) {
                $problems[] = "dokumentirani stupac `{$table}.{$col}` ne postoji u bazi";
            }
        }
    }
    report('shema (02-baza-podataka.md ↔ information_schema)', $problems, $checked);
}

// ---------------------------------------------------------------- rute
if ($run('rute')) {
    $doc = read($docs.'/03-http-i-livewire.md');
    $problems = [];
    $checked = 0;
    $json = shell_exec('cd '.escapeshellarg($root).' && php artisan route:list --json 2>/dev/null');
    foreach (json_decode((string) $json, true) ?? [] as $route) {
        $uri = $route['uri'];
        if (str_starts_with($uri, 'livewire-') || str_starts_with($uri, 'storage/') || $uri === '/') {
            continue;
        }
        $checked++;
        if (! str_contains($doc, '/'.ltrim($uri, '/')) && ! str_contains($doc, $uri)) {
            $problems[] = "ruta `{$uri}` ({$route['name']}) nije u 03-http-i-livewire.md";
        }
        if (! empty($route['name']) && ! str_contains($doc, (string) $route['name'])) {
            $problems[] = "ime rute `{$route['name']}` nije u 03-http-i-livewire.md";
        }
    }
    report('rute (route:list ↔ 03-http-i-livewire.md)', $problems, $checked);
}

// ---------------------------------------------------------------- livewire
if ($run('livewire')) {
    $problems = [];
    $checked = 0;
    $map = [
        'Auth\\Login' => 'prijava.md',
        'StudentsList' => 'polaznici.md', 'StudentForm' => 'polaznici.md', 'StudentDelete' => 'polaznici.md',
        'StudentResults' => 'polaznici.md', 'StudentDeclaration' => 'polaznici.md',
        'CompetitionsList' => 'natjecanja.md',
        'CompetitionRostersList' => 'popisi-natjecanja.md', 'CompetitionRosterShow' => 'popisi-natjecanja.md',
        'KataTeamsList' => 'kata-timovi.md',
        'FeePlansList' => 'clanarine.md', 'StudentFeePlans' => 'clanarine.md', 'StudentCharges' => 'clanarine.md',
        'ClubBillingSettingsForm' => 'clanarine.md', 'EracuniPartnerLinks' => 'clanarine.md',
    ];
    $all = '';
    foreach (glob($docs.'/04-ekrani/*.md') as $f) {
        $all .= read($f);
    }

    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app/Livewire', FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (! $f->isFile() || $f->getExtension() !== 'php') {
            continue;
        }
        $rel = str_replace([$root.'/app/Livewire/', '.php', '/'], ['', '', '\\'], $f->getPathname());
        $class = 'App\\Livewire\\'.$rel;
        if (! isset($map[$rel])) {
            $problems[] = "komponenta `{$class}` nije u mapi provjere (dodaj je u alati/provjeri.php)";

            continue;
        }
        $doc = read($docs.'/04-ekrani/'.$map[$rel]);
        $ref = new ReflectionClass($class);
        if (! str_contains($doc, $ref->getShortName())) {
            $problems[] = "klasa `{$ref->getShortName()}` nije spomenuta u {$map[$rel]}";
        }
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
            if ($m->class !== $class || $m->isConstructor() || str_starts_with($m->name, '_') || $m->name === 'render') {
                continue;
            }
            $checked++;
            if (! str_contains($doc, $m->name)) {
                $problems[] = "{$ref->getShortName()}::{$m->name}() nije u {$map[$rel]}";
            }
        }
        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $p) {
            if ($p->class !== $class) {
                continue;
            }
            $checked++;
            if (! str_contains($doc, '$'.$p->name) && ! str_contains($doc, '`'.$p->name.'`')) {
                $problems[] = "svojstvo {$ref->getShortName()}::\${$p->name} nije u {$map[$rel]}";
            }
        }
    }

    // ciljevi iz Blade pogleda
    $targets = [];
    $vit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/views', FilesystemIterator::SKIP_DOTS));
    foreach ($vit as $f) {
        if (! $f->isFile() || ! str_ends_with($f->getFilename(), '.blade.php')) {
            continue;
        }
        $src = read($f->getPathname());
        if (preg_match_all('/wire:(?:click|submit|change)(?:\.[a-z.]+)?="\s*([A-Za-z_]\w*)/', $src, $mm)) {
            foreach ($mm[1] as $t) {
                $targets[$t] = $f->getFilename();
            }
        }
        if (preg_match_all('/\$wire\.([A-Za-z_]\w*)\(/', $src, $mm)) {
            foreach ($mm[1] as $t) {
                $targets[$t] = $f->getFilename();
            }
        }
        if (preg_match_all('/wire:model(?:\.[a-z0-9.]+)?="\s*([A-Za-z_]\w*)/', $src, $mm)) {
            foreach ($mm[1] as $t) {
                $targets[$t] = $f->getFilename();
            }
        }
        if (preg_match_all('/\$dispatch\(\s*\'([a-z-]+)\'/', $src, $mm)) {
            foreach ($mm[1] as $t) {
                $targets[$t] = $f->getFilename();
            }
        }
    }
    foreach ($targets as $t => $file) {
        $checked++;
        if (! str_contains($all, $t) && ! str_contains(read($docs.'/03-http-i-livewire.md'), $t)) {
            $problems[] = "cilj `{$t}` iz {$file} nije u dokumentima ekrana";
        }
    }
    report('livewire (klase, metode, svojstva, Blade ciljevi ↔ 04-ekrani)', $problems, $checked);
}

// ---------------------------------------------------------------- env
if ($run('env')) {
    $doc = read($docs.'/08-konfiguracija.md');
    $problems = [];
    $checked = 0;
    $names = [];
    foreach (glob($root.'/config/*.php') as $f) {
        if (preg_match_all('/env\(\s*[\'"]([A-Z0-9_]+)[\'"]/', read($f), $mm)) {
            foreach ($mm[1] as $n) {
                $names[$n] = basename($f);
            }
        }
    }
    foreach ($names as $n => $file) {
        $checked++;
        if (! str_contains($doc, $n)) {
            $problems[] = "env varijabla `{$n}` ({$file}) nije u 08-konfiguracija.md";
        }
    }
    report('env (config/*.php ↔ 08-konfiguracija.md)', $problems, $checked);
}

// ---------------------------------------------------------------- događaji
if ($run('dogadjaji')) {
    $doc = read($docs.'/03-http-i-livewire.md');
    $problems = [];
    $names = [];
    $scan = static function (string $dir) use (&$names, $root): void {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (! $f->isFile() || ! preg_match('/\.php$/', $f->getFilename())) {
                continue;
            }
            $src = (string) file_get_contents($f->getPathname());
            foreach ([
                '/dispatch\(\s*\'([a-z-]+)\'/',
                '/#\[On\(\s*\'([a-z-]+)\'\s*\)\]/',
                '/\$dispatch\(\s*\'([a-z-]+)\'/',
            ] as $re) {
                if (preg_match_all($re, $src, $mm)) {
                    foreach ($mm[1] as $n) {
                        $names[$n] = str_replace($root.'/', '', $f->getPathname());
                    }
                }
            }
        }
    };
    $scan($root.'/app');
    $scan($root.'/resources/views');
    foreach ($names as $n => $file) {
        if (! str_contains($doc, $n)) {
            $problems[] = "događaj `{$n}` ({$file}) nije u 03-http-i-livewire.md";
        }
    }
    report('događaji (dispatch / #[On] ↔ 03-http-i-livewire.md)', $problems, count($names));
}

// ---------------------------------------------------------------- linkovi
if ($run('linkovi')) {
    $problems = [];
    $checked = 0;
    foreach (array_merge(mdFiles($docs), mdFiles($root.'/docs/decisions')) as $file) {
        $src = read($file);
        $dir = dirname($file);
        if (preg_match_all('/\]\(([^)#\s]+)(#[^)]*)?\)/', $src, $mm)) {
            foreach ($mm[1] as $href) {
                if (preg_match('~^(https?:|mailto:)~', $href)) {
                    continue;
                }
                $checked++;
                if (! file_exists($dir.'/'.$href)) {
                    $problems[] = str_replace($root.'/', '', $file)." → neispravan link `{$href}`";
                }
            }
        }
        // reference file:line
        if (preg_match_all('~`?((?:app|resources|routes|config|database|tests|public|bootstrap)/[\w/.\-]+\.(?:php|js|css|json|html)):(\d+)`?~', $src, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                $checked++;
                $path = $root.'/'.$m[1];
                if (! is_file($path)) {
                    $problems[] = str_replace($root.'/', '', $file)." → datoteka ne postoji: {$m[1]}";

                    continue;
                }
                $lines = count(file($path));
                if ((int) $m[2] > $lines) {
                    $problems[] = str_replace($root.'/', '', $file)." → {$m[1]}:{$m[2]} ima samo {$lines} redaka";
                }
            }
        }
    }
    report('linkovi i reference datoteka:redak', $problems, $checked);
}

// ---------------------------------------------------------------- pii i tajne
if ($run('pii')) {
    $problems = [];
    $files = array_merge(mdFiles($docs), mdFiles($root.'/docs/decisions'), glob($docs.'/alati/*') ?: []);
    $blob = '';
    foreach ($files as $f) {
        if (is_file($f) && ! str_ends_with($f, 'provjeri.php')) {
            $blob .= "\n".read($f);
        }
    }

    // 1) adrese e-pošte: dopušteno samo @primjer.example (i @example.*)
    preg_match_all('/[A-Za-z0-9._%+\-]+@([A-Za-z0-9.\-]+\.[A-Za-z]{2,})/', $blob, $mm, PREG_SET_ORDER);
    $bad = array_filter($mm, static fn ($m) => ! preg_match('/(^|\.)(primjer|example)\./i', $m[1].'.') && ! str_ends_with($m[1], 'example.com'));
    foreach ($bad as $b) {
        $problems[] = 'adresa e-pošte u dokumentaciji (domena '.$b[1].')';
    }

    // 2) vrijednosti tajni iz .env
    $env = read($root.'/.env');
    foreach (['APP_KEY', 'DB_PASSWORD', 'OPTIMIST_DEFAULT_PASSWORD'] as $k) {
        if (preg_match('/^'.$k.'=(.*)$/m', $env, $m)) {
            $v = trim($m[1], " \"'");
            if (strlen($v) >= 6 && str_contains($blob, $v)) {
                $problems[] = "vrijednost tajne {$k} pojavljuje se u dokumentaciji";
            }
        }
    }

    // 3) stvarna imena i prezimena iz baze (samo broj pogodaka)
    $hits = 0;
    $names = [
        ...DB::table('students')->distinct()->pluck('last_name')->all(),
        ...DB::table('students')->distinct()->pluck('first_name')->all(),
        ...DB::table('users')->distinct()->pluck('name')->all(),
    ];
    foreach (array_unique($names) as $ln) {
        if (mb_strlen((string) $ln) >= 4 && preg_match('/\b'.preg_quote((string) $ln, '/').'\b/u', $blob)) {
            $hits++;
        }
    }
    if ($hits > 0) {
        $problems[] = "{$hits} stvarnih imena/prezimena iz baze pojavljuje se u dokumentaciji";
    }
    report('osobni podaci i tajne', $problems, count($files));
}

echo $failures === 0 ? "\nSVE PROVJERE PROLAZE\n" : "\nUKUPNO PROBLEMA: {$failures}\n";
exit($failures === 0 ? 0 : 1);
