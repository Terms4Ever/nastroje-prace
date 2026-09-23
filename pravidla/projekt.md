# Pravidla pracovního projektu

Tato pravidla platí pro osobní Git kopii pracovního projektu. Verzi určuje
nastroje-prace.lock.json. Pravidla ani jejich PHP kopii neupravuj přímo v projektu;
změna vzniká v nastroje-prace a do projektu přichází jako ověřená aktualizace.

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

- Piš česky, konkrétně, s krátkou pomlčkou. Nepoužívej dlouhé Unicode pomlčky.
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
Při nemožnosti snímek získat popiš překážku a issue nech otevřené.
Nevizuální změna dokládá odpovídající test nebo kontrolní postup.

## Commit, testy a dokončení

Commit má konkrétní český nadpis zakončený (#ČÍSLO) a řádky Důvod: a Ověření:.
Neuzavírej issue pomocí Closes/Fixes. Před pushem vyžaduj čistý strom a celý
check --base VÝCHOZÍ_SHA --online. ROOT patří výhradně prvnímu commitu.
Testy jsou skutečné příkazy dané aplikace; pouhé echo nebo exit 0 není ověření.
Nedostupný build, API nebo kontrola znamená překážku, nikoli úspěch.

Hooky instaluj jen do tohoto projektu. Nepřepisuj jiné hooky, nepoužívej
globální core.hooksPath ani --no-verify při běžném předání. Chráněný main
vyžaduje stav Povinne kontroly pro přijímaný commit, i pro vlastníka.
Issue zavři přes issue-close po splnění checklistu a shodě místního ověření,
úspěšného CI a aktuálního main. U SVN je nutné ověření výsledné revize.
Neúplně připojený repozitář se nesmí označit jako připravený k práci.

Trvalá větev je main. Pro rozpracovaný úkol používej ukol/CISLO-kratky-popis.
Po přijetí ověřené změny na main odstraň vlastní dokončenou větev na GitHubu
i místně a proveď fetch --prune. Ověř, že obsah převzalo main a větev nemá
otevřený pull request. Nepřevzaté změny nemaž automaticky. Důkazy testů drž
v protokolech a záznamech úkolů; nepotřebují trvale ponechanou testovací větev.
