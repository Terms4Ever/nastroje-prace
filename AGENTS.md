# Pravidla práce

Tento privátní repozitář obsahuje osobní kontroly a postup předání změn.
Společné nastroje se používají z přesného commitu v upstream.lock.json.
Neupravuj kvůli tomuto projektu jiné repozitáře ani globální nastavení hooků.
Nové pravidlo nebo zpřísnění předem předlož vlastníkovi s důvodem a dopadem.
Zaveď je až po jeho výslovném schválení; testy toto rozhodnutí nenahrazují.

## Začátek úkolu

1. Přečti README.md, docs/00-stav-projektu.md a příslušné issue. Další dokumenty podle tématu.
2. Zkontroluj dostupné nástroje příkazem doctor. Chybějící ověření není úspěch.
3. Vyhledej existující issue. Nové založ přes issue-create s validovaným JSON.
4. Uveď Mantis ID nebo zdůvodnění technického úkolu bez Mantis.
5. Založ metadata v .tasks a stručný záznam v docs/ukoly. Názvy souborů jsou číslo issue.

## Psaní a dokumentace

- Piš česky, konkrétně, s krátkými pomlčkami. V Markdown dokumentaci lze dlouhou pomlčku citovat v řádkovém kódu; v běžné větě a bloku kódu zůstává zakázaná.
- Otevřený stručný nápad vlastníka bez sekcí smí čekat na přepsání bez šablony, štítků a odpovědného. Než ho převezmeš do práce, uprav jej do plného tvaru. Výjimka není pro issue zakládané agentem ani pro dokončení.
- Issue má povinné Problém nebo Cíl a Hotovo, když. Volitelné Jak to poznat, Kde to žije a Snímky mají pevné pořadí.
- Tělo má nejvýše 40 neprázdných řádků a jediný ověřitelný checklist.
- Každé issue má jeden druh bug/enhancement/documentation, odpovědného a rozhrani/bez-rozhrani.
- Komentář má nejvýše pět neprázdných řádků. Delší rozbor odkaž do dokumentace.
- Text popisuje práci a výsledek. Nevkládej reklamní podpis ani tvrzení, kterým asistentem byl napsán.
- Issue drží zadání a průběh. Dokumentace drží současné fungování a důležitá rozhodnutí; nekopíruj celé issue do deníku.
- Záznam úkolu má Zadání, Změna, Ověření a Předání. Neoznačuj neprovedený test za úspěšný.
- README odkazuje na složku docs/ukoly/, ne na jednotlivé úkoly. Odznaky mají jednotný vzhled a pravdivý obsah.
- Tabulka Dokumentace používá cesty v řádkovém kódu a společné popisy stavu a deníku jako nastroje. Pod tabulkou je stejná věta o zdroji aktuálního stavu; další dokumenty určuje projekt.

## Nový pracovní projekt

Postupuj podle docs/05-novy-pracovni-projekt.md a použij project-init se šablonami
ze sablony/project. Zjisti konkrétní název, cestu, původ zdrojů a skutečné testy;
chybějící informace řeš dialogem. Žádný další projekt se nezakládá automaticky.
Nový aplikační GitHub je privátní a místní Git leží mimo existující Git/SVN kopie.
Připojuj přesnou verzovanou kopii kontrol bez dalšího tokenu v CI. AGENTS.md
projektu musí odkazovat na připnutá pravidla. Neoznačuj zavedení za hotové bez
čistého clone, funkčních testů, CI a project-check --online. Ochranu main nezapínej.
O případném zveřejnění centrálních nástrojů rozhoduje vlastník po auditu; samo
napojení projektu k takové změně nedává oprávnění.

## Snímky před a po

U změny rozhraní pořiď původní stav před úpravou a stejný pohled po ověření.
Soubory patří do docs/snimky pod číslo issue a krátký název, s prefixy pred- a po-.
Vlož je do issue jako obrázky s odkazy na konkrétní commit. Textový odkaz nestačí.
Nad každý obrázek napiš viditelný nadpis třetí úrovně Před změnou nebo Po změně
s názvem pohledu a krátce popiš rozdíl. Alternativní text obrázku popisek nenahrazuje.
Dvojice řaď po sobě v plné šířce, aby zmenšení do tabulky nezhoršilo čitelnost.
Každý snímek před má protějšek po. Nezaměňuj testovací obrázky za důkaz z aplikace.
Výjimku pro skutečně nepořiditelný snímek schvaluje pouze vlastník, každý případ zvlášť.
Nejdřív dolož konkrétní překážku, provedený pokus a dostupný náhradní důkaz.
Předlož přesné chybějící snímky přes snimky-zadost; bez výslovného souhlasu nech issue otevřené.
Souhlas zaznamenej až po rozhodnutí vlastníka podle docs/01-postup-prace.md.
Agent nesmí souhlas vymyslet ani odvodit ze schválení obecných pravidel či celého úkolu.
Výjimka platí jen pro schválené snímky a podklady, ostatní obrázky zůstávají povinné.
U nevizuální změny dolož odpovídající test, výstup nebo kontrolní postup.

## Commit a ověření

Pracuj pouze na main. Na projektu pracuje vždy jeden agent a jeden úkol najednou.
Nezakládej pracovní ani testovací větve, pull requesty nebo paralelní worktree.
Negativní testy větví patří pouze do dočasných izolovaných testovacích repozitářů.
Před prací proveď fetch a případné aktualizace přijmi přes pull --ff-only na čistém stromu.
Rozcházející se historii vyřeš před úpravami; nepřepisuj publikované commity.

Nadpis commitu je česká věta o výsledném stavu zakončená (#číslo).
Tělo obsahuje konkrétní řádky Důvod: a Ověření:. Používej odkaz na issue bez automatického uzavírání.
Před pushem musí projít check s explicitním základem rozsahu a online ověřením issues.
ROOT je jen pro úplně první commit. Neznámý základ, špinavý strom, chyba API či neúspěšný test zastaví práci.
Hooky instaluj pouze lokálně příkazem install-hooks, nepoužívej --no-verify pro běžné předání.
Commit hook odmítne jinou větev; push hook dovolí pouze HEAD z main do main.
Push je přímý. GitHub CI ověřuje commit následně, chyba se opravuje novým commitem.
GitHub ochrana main je na rozhodnutí vlastníka odstraněna a při zavedení se nezapíná.
Předchozí úspěšné CI ani pull request nejsou podmínkou přijetí pushe.
Kontrola issues reaguje na události a ruční spuštění, denní plán se nepoužívá.

## Dokončení a SVN

Issue zavírej příkazem issue-close až po ověření stejného commitu lokálně a v GitHub CI na main.
Stejnou podmínku vyžadují predani-priprav, predani-over a predani-zapis. Čekající,
chybějící či neúspěšné CI a nedostupné API předání zastaví. Před dalším úkolem
dokonči ověření a případné SVN předání současného commitu.
Neodškrtávej nesplněné body. Předání do SVN musí mít ověřenou výslednou revizi.
Příkazy SVN pouze čtou vzdálený stav a vytvářejí místní balíček. Nikdy samy necommitují do SVN.
Při souběžné změně kolegy zastav předání, sluč obsah a opakuj testy.
Předání přes TortoiseSVN probíhá ze správně aktualizované pracovní kopie a s kontrolou diffu.

## Testování

Spusť php tests/run.php. Na Windows lze použít .\.cache\php\php.exe tests/run.php.
Kontroly, testy i CI piš v PHP. PowerShell slouží pouze ke spouštění a místní instalaci PHP.
Každé nové pravidlo musí mít pozitivní i negativní případ.
README má pořadí Hlavní funkce, Tech Stack, Struktura projektu, Dokumentace, Instalace, Nasazení, Licence.
Kontrola readme-check musí být součástí plného check. Metadata-check vyžaduje privátní repozitář a topics z .prace.json.
Linux CI povinně testuje skutečné dočasné SVN; místní absence CLI se hlásí jako přeskočení, nikoli ověření SVN.
Testy nemění jiné projekty, nepoužívají produkční databáze a neodesílají skutečné issues.
