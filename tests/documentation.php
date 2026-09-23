<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;

use NastrojePrace\{Policy, Upstream};
use function NastrojePrace\{readFile, writeFile};

test('Pracovní README má stejný formát dokumentace jako nastroje', fn() => Policy::readme(\NastrojePrace\TOOL_ROOT));

test('Dřívější pojmenované odkazy a prosté cesty se v dokumentaci odmítnou', fn() => fixture(function ($root): void {
    $original = readFile($root . '/README.md');
    foreach ([['`docs/00-stav-projektu.md`', '[Stav projektu](docs/00-stav-projektu.md)'],
        ['`docs/ukoly/`', 'docs/ukoly/']] as [$before, $after]) {
        writeFile($root . '/README.md', str_replace($before, $after, $original));
        Upstream::check('kontrola-readme.php', [$root]);
        fails(fn() => Policy::readme($root), 'cesty v řádkovém kódu');
    }
}));

test('Dokumentace potřebuje společné popisy a větu o aktuálním stavu', fn() => fixture(function ($root): void {
    $original = readFile($root . '/README.md');
    foreach ([['živý stav: co je hotové, co se dělá, co je dál, a které repozitáře jsou zapojené', 'Současné možnosti a hranice.', 'Společné popisy'],
        ['co bylo kdy rozhodnuto a proč. Nové rozhodnutí je nový záznam, staré se nepřepisuje', 'Důvody hlavních pravidel.', 'Společné popisy'],
        ['Stav vždy platný je v `docs/00-stav-projektu.md`, ne v tomhle souboru.', 'Aktuální stav patří do dokumentace.', 'aktuálního stavu']] as [$before, $after, $error]) {
        writeFile($root . '/README.md', str_replace($before, $after, $original));
        Upstream::check('kontrola-readme.php', [$root]);
        fails(fn() => Policy::readme($root), $error);
    }
}));
