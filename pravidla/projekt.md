# Pravidla pracovního projektu

Tato pravidla platí pro osobní Git kopii pracovního projektu. Verzi určuje
nastroje-prace.lock.json. Pravidla ani jejich PHP kopii neupravuj přímo v projektu;
změna vzniká v nastroje-prace a do projektu přichází jako ověřená aktualizace.
Nové pravidlo nebo zpřísnění nejdřív vysvětli vlastníkovi a vyžádej si jeho
výslovné rozhodnutí. Samotné zadání úkolu nesmí obrátit dříve zamítnuté pravidlo.

## Začátek práce

1. Přečti README.md, docs/00-stav-projektu.md a odpovídající issue.
2. Spusť doctor a project-check. Proveď fetch a zjisti, odkud se přebírá aktuální zdrojový stav.
3. Zadání patří do issue tohoto projektu. Vyhledej duplicitu a nové issue zakládej přes issue-create s validovaným JSON.
4. Uveď skutečné Mantis ID nebo důvod technického úkolu. V .tasks/ČÍSLO.json nastav issue, mantis, technical_reason, visual a delivery.
5. Vytvoř docs/ukoly/ČÍSLO.md se sekcemi Zadání, Změna, Ověření a Předání.
6. Před změnou aplikace vysvětli konkrétní postup. Chybějící zadání, konfiguraci nebo přístup řeš dialogem s uživatelem.

## Oddělení od SVN a kolegů

SVN zůstává společným zdrojem předávaných změn. Git je osobní pracovní kopie.
Pracuj v oddělené složce; nezakládej Git uvnitř existující pracovní kopie SVN,
jiného Gitu ani na sdíleném disku. Kopíruj pouze zkontrolované zdrojové soubory.
Nikdy nepřenášej .svn, .git, hesla, profily, databáze ani provozní data.
Do produkce, sdílených složek a cizích projektů bez zadání nezapisuj.

Před zahájením aplikační změny převezmi aktuální SVN stav a zachyť svn-zaklad.
Při souběžné změně kolegy sluč změny a opakuj ověření. Balíček nepoužívej
k přepsání celého starého stromu přes novější pracovní kopii.
Nástroje samy neprovádějí SVN commit; předání probíhá kontrolovaným běžným postupem.

## Psaní a dokumentace

- Piš česky, konkrétně, s krátkou pomlčkou. Markdown dokumentace smí citovat dlouhou pomlčku v řádkovém kódu, nikoli v běžné větě nebo bloku kódu.
- Vlastník smí zapsat stručný otevřený nápad bez šablony a zařazení. Před převzetím do .tasks jej agent převede do plného tvaru; při založení agentem a při dokončení tato výjimka neplatí.
- Issue má Problém nebo Cíl a Hotovo, když. Volitelné sekce mají pořadí Jak to poznat, Hotovo, když, Kde to žije a Snímky.
- Tělo issue má nejvýše 40 neprázdných řádků, jeden konkrétní checklist, právě jeden druh bug/enhancement/documentation a odpovědného.
- Urči právě jeden štítek rozhrani/bez-rozhrani; musí souhlasit s visual v metadatech úkolu.
- Komentář má nejvýše pět neprázdných řádků. Delší rozbor patří do dokumentace. Nepřidávej podpis asistenta.
- README má pořadí Hlavní funkce, Tech Stack, Struktura projektu, Dokumentace, Instalace, Nasazení, Licence.
- README odkazuje na složku docs/ukoly/, nevypisuje jednotlivé úkoly.
- Dokumentace popisuje současné fungování a důležitá rozhodnutí. Každé issue v rozsahu commitů potřebuje vlastní změněný textový záznam.
- Technické omezení nebo neprovedený test popiš pravdivě. Nepoužívej zástupný text jako dokončenou dokumentaci.

## Snímky před a po

Před úpravou rozhraní pořiď skutečný původní stav; po ověření stejný pohled.
Ulož dvojice do docs/snimky/ČÍSLO-popis/pred-pohled.png a po-pohled.png.
Podporované jsou PNG, JPEG a WebP. Oba snímky vlož jako obrázky do issue
s odkazy na konkrétní celé Git SHA. Odkaz na main ani textový odkaz nestačí.
Snímky musí být srovnatelné a bez citlivých údajů. Testovací obrázky nejsou důkaz.
Výjimku schvaluje pouze vlastník pro konkrétní případ skutečné nemožnosti.
Dolož překážku, provedený pokus a dostupný náhradní důkaz; pohodlí ani vynechaný
pokus nestačí. Dostupný původní stav nejdřív zkus obnovit pro pořízení snímku.
Do .tasks/ČÍSLO.json připrav screenshot_exception s missing (přesné chybějící
cesty), reason, attempt a evidence (pole existujících verzovaných důkazů v docs/prilohy).
Příkaz snimky-zadost ČÍSLO vypíše podklady a approval_text, nic neschvaluje.
Vlastníkovi předlož podklady a vyžádej jeho výslovné rozhodnutí; bez něj issue nezavírej.
Po skutečném souhlasu v chatu smíš do stejného issue zapsat komentář s prvním
řádkem approval_text a druhým řádkem s citací a kontextem souhlasu vlastníka.
Pokud souhlas vložil přímo vlastník, použij jeho komentář. Jeho číselné ID ulož
do screenshot_exception.approval_comment a odkaz na komentář do sekce Snímky.
Agent nesmí rozhodnutí vlastníka nahrazovat svým rozhodnutím ani si souhlas vymýšlet.
Změna rozsahu nebo podkladů vyžaduje nový souhlas; dostupné obrázky se dál kontrolují.
GitHub ověří účet, komentář a vazbu na podklady, nikoli člověka používajícího
stejný účet jako agent. Pravdivost překážky a vlastní rozhodnutí zůstávají na vlastníkovi.
Nevizuální změna dokládá odpovídající test nebo kontrolní postup.

## Commit, testy a dokončení

Jedinou větví je main. Pracuje vždy jeden agent a jeden úkol najednou, bez
pracovních větví, pull requestů a paralelních worktree. Před úpravami na čistém
main proveď fetch a případně pull --ff-only. Nepřepisuj publikovanou historii.

Commit má konkrétní český nadpis zakončený (#ČÍSLO) a řádky Důvod: a Ověření:.
Neuzavírej issue pomocí Closes/Fixes. Před pushem vyžaduj čistý strom a celý
check --base VÝCHOZÍ_SHA --online. ROOT patří výhradně prvnímu commitu.
Testy jsou skutečné příkazy dané aplikace; pouhé echo nebo exit 0 není ověření.
Nedostupný build, API nebo kontrola znamená překážku, nikoli úspěch.

Hooky instaluj jen do tohoto projektu. Nepřepisuj jiné hooky, nepoužívej
globální core.hooksPath ani --no-verify při běžném předání. Hooky blokují commit
mimo main a push jiné větve, tagu či odstranění. Push míří přímo do main.
GitHub CI běží až po pushi. Ochranu main nezapínej; sadu nepodmiňuje ani předchozí
CI, ani pull request. Kontrola issues reaguje na události a ruční spuštění bez denního plánu.
Při selhání CI oprav chybu novým commitem. Nezačínej další úkol před dokončením.
Issue zavři přes issue-close po splnění checklistu a shodě místního ověření,
úspěšného CI a aktuálního main. U SVN je nutné ověření výsledné revize.
Příprava, kontrola i zápis předání také vyžadují místní online doklad a úspěšný
poslední běh CI stejného aktuálního main. Neúspěch nebo chyba API zastaví předání.
Neúplně připojený repozitář se nesmí označit jako připravený k práci.
