# Ověření funkčnosti

## Automatická sada

Příkaz php tests/run.php používá skutečné připnuté PHP kontroly a dočasné Git
repozitáře. Jde o PHP sadu bez Composeru a dalších testovacích závislostí.
Před testy běží lint všech PHP zdrojů, testů a vstupních bodů.
Ověřují přijetí správného issue a odmítnutí syrového textu od agenta, dlouhých pomlček,
chybějících štítků, odpovědného, nesprávné struktury a příliš dlouhého textu.
Neplatný návrh nesmí ani zavolat zápis na GitHub. Testuje se také duplicita a chyba API.
Otevřený nápad vlastníka projde bez šablony; cizí, převzaté nebo zavřené issue
výjimku nedostane. Skutečné kontroly README, dokumentace a pracovního profilu
přijmou citaci pomlčky v řádkovém kódu, odmítnou běžný text i blok kódu.
Workflow a šablona musí zachovat události a ruční spuštění bez denního plánu.

Kontrola změn ověřuje pozitivní případ i odmítnutí neznámého základu,
kódu bez textové dokumentace, nesouvisejícího obrázku, neúspěšného příkazu,
špinavého stromu, změny commitu a lokálně upravených společných nástrojů.
Neúspěšné opakované ověření smaže dřívější lokální potvrzení úspěchu.

Main-only scénáře ověřují první i další push, skutečný commit hook mimo main,
zamítnutí další větve, tagu, odstranění a více referencí před API. CI přijme
odpojený HEAD přesného main SHA, odmítne jinou větev, událost nebo chybějící základ.
Ruční CI vyžaduje explicitní rozsah. Tyto záporné případy žijí jen v dočasných kopiích.

Snímky mají pozitivní dvojici a negativní případy chybějícího protějšku,
jiného čísla issue, pouhého textového odkazu a změněného souboru. Syntetické
testovací soubory nejsou důkazy změny aplikace.

Výjimky mají pozitivní případ chybějícího snímku před, po i obou po souhlasu
vlastníka. Negativní případy pokrývají samotný štítek, chybějící souhlas,
cizí účet, jiný úkol, změněné podklady, prázdný důkaz a nedostupné API.
Výjimka nesmí skrýt další chybějící snímek, chybný odkaz ani totožnou dostupnou
dvojici. Příkaz žádosti nic neschvaluje. Dokončení čte souhlas znovu a při
odvolání nezapíše komentář ani neuzavře issue. API odpovědi jsou v těchto
testech izolované; nevzniká skutečné schválení vlastníka ani výjimka úkolu.

README se ověřuje samostatným příkazem i uvnitř úplné kontroly. Regrese přesune
Dokumentaci za Nasazení: původní upstream tuto chybu přijme, pracovní kontrola
ji odmítne. Další testy hlídají tabulku dokumentace, duplicitní sekci, topics
a privátní viditelnost aplikací. Dokončení issue má pozitivní i negativní scénáře CI a main.
Samostatné regrese odmítají pojmenované odkazy v tabulce dokumentace, jiné
popisy základních dokumentů a chybějící větu o zdroji aktuálního stavu.

Sada pravidel má vlastní pozitivní CLI a záporné scénáře osobního nebo
chybějícího výběru, podvojných GitHub topics a neplatných očekávaných topics.
Plný check nesmí vystavit doklad pro jinou sadu. Export doplní pracovní topic
a osobní topic odmítne ještě před zápisem. Skutečně exportovaná připnutá
aplikace projde kontrolou, smazaný výběr nebo přímé osobní workflow neprojde.
Detailní syntaxi značek ověřuje stejný společný validátor jako osobní sada.
Společná podmínka předání odmítne offline doklad, neexistující, čekající,
zrušený, přeskočený či neúspěšný poslední běh a běh jiné větve nebo workflow.
Testuje souhrnnou úlohu včetně další stránky API, chybu API a posun main během čtení.

Procesní testy ověřují Unicode, zvláštní znaky v argumentech bez shellu,
nenulový návratový kód a velký výstup, který nesmí zablokovat Windows.

Zavedení projektu testuje export přesného čistého commitu do prázdné složky,
skutečný Git commit hook, místní konfiguraci, nový clone a výpočet malé zkušební
aplikace. Chybný výpočet, poškozená kopie, chybějící lock, vnořený repozitář,
cizí hooky a neúplné zadání jsou záporné případy. GitHub připravenost má
simulované odpovědi pro štítky, neúspěšné CI a jiné main. Ochranu větve nevyžaduje
a nepotřebuje administrativní přístup k jejímu nastavení.
Nevytváří se skutečný aplikační GitHub. Regrese s 500 záznamy úkolů ověřuje,
že README zůstává rozcestníkem bez jednotlivých řádků pro každý úkol.

## Reálné SVN v izolaci

Linux CI vyžaduje svn a svnadmin. Zakládá místní jednorázový repozitář,
připraví balíček, ověří jeho obsah a skutečnou výslednou revizi. Další scénáře
provádějí změnu kolegy před přípravou i po ní, poškození ZIPu a pokus o zápis
neplatné výsledné revize. Nedotýkají se žádného firemního SVN.
Součástí jsou také přidání souboru a odstranění posledního souboru s prázdným ZIPem.
Nové selhání CI po přípravě skutečného balíčku blokuje kontrolu i zápis předání.
Také bez SVN CLI se testuje, že všechny tři vstupy předání odmítnou neplatný
důkaz ještě před přístupem k SVN, vytvořením balíčku nebo zápisem výsledku.

Na počítači bez SVN CLI se tyto testy hlásí jako přeskočené. Proměnná
NASTROJE_REQUIRE_SVN=1 způsobí v takovém prostředí neúspěch sady; Linux CI
ji nastavuje povinně. Windows CI ověřuje běh základních kontrol a PHP.

## Rozsah důkazů

Zavedení označení sad v issue 11 má sedm nových regresí, které byly nejprve
červené a následně prošly. Po připnutí publikovaného upstream prošla i úplná
místní online brána nad skutečným pracovním commitem: 98 úspěšných testů,
nula chyb a devět SVN scénářů přeskočených bez místního SVN CLI.
Novější upstream odmítá snímek odkazující na větev main dříve;
očekávané hlášení starého scénáře je přizpůsobené a zákaz zůstává ověřený.

Skutečná pracovní kopie připíná upstream 7e7e1539d2f23484a76ceedd438044af818bb00b.
Rules-check, readme-check, doctor a živý metadata-check nad touto cache prošly.
Cache je čistá a origin zůstává https://github.com/Terms4Ever/nastroje.git.
Upstream commit je publikovaný a prošel vlastními kontrolami na Linuxu i Windows.
Snímky označení README před a po jsou skutečné pohledy z GitHubu ve složce
docs/snimky/11-sada-pravidel. Přesné CI pracovního commitu dokládá jeho běh Actions;
CI upstreamu samo o sobě pracovní ověření nenahrazuje.

Veřejný režim má samostatné pozitivní a negativní scénáře. Přijme veřejné
centrální nástroje, ale odmítne veřejnou aplikaci i při zkopírovaném názvu
repozitáře. Nadále vyžaduje správnou identitu, skutečnou viditelnost a topics.
Veřejné podněty bez převzetí a komentáře návštěvníků neblokují audit, převzaté
úkoly, příspěvky správce a vstupy agenta zůstávají přísné. Testy veřejných
příspěvků používají izolovaného klienta, nikoli cizí účet nebo skutečný spam.

Výsledek konkrétní verze je v GitHub Actions. Místní check ukládá návratové
kódy, otisky testovacích výstupů, commit, tree a verzi pravidel do ignorované
místní složky. Neexistuje produkční nasazení těchto nástrojů ani provedený
test proti firemním systémům. Bezpečnostní pravidla nejsou úplný secret scanner.

Oficiální podklady pro nastavení:

- [Ochrana větví](https://docs.github.com/en/rest/branches/branch-protection#delete-branch-protection) - odstranění dříve zavedené ochrany na pokyn vlastníka.
- [Běhy workflow](https://docs.github.com/en/rest/actions/workflow-runs) - výsledek posledního běhu přesného SHA.
- [Bezpečné Actions](https://docs.github.com/en/actions/reference/security/secure-use) - připnutí akcí a oprávnění.
- [Vstupy v Actions](https://docs.github.com/en/actions/concepts/security/script-injections) - obsah issues se nevkládá do shellu.
- [Stav PHP procesu](https://www.php.net/manual/en/function.proc-get-status.php) - návratové kódy na PHP 8.3 a novějším.
