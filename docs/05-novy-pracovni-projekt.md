# Založení a napojení pracovního repozitáře

Tento postup platí pro nový osobní Git projektu se společným SVN. Samotné
vygenerování souborů není dokončené zavedení. Agent postupuje podle tohoto
dokumentu a doloží místní i vzdálené kontroly. Existující projekt nepřepisuje.

## 1. Vstupy a převzetí zdrojů

Zjisti skutečný název a účel projektu, vlastníka GitHub repozitáře, samostatnou
místní cestu, zdrojový SVN adresář a revizi, způsob sestavení a testy aplikace.
Chybějící údaje řeš dialogem. Názvy a oprávnění si nevymýšlej.

Nejdříve prohlédni aktuální stav zdrojů a necommitované změny v existující
pracovní kopii. Git zakládej v nové prázdné místní složce, mimo jiné Git/SVN
kopie a sdílené disky. Nekopíruj celý adresář naslepo: vyber zdrojové soubory,
prověř tajné údaje, osobní data, licence, generované soubory, závislosti a velikost.
Vyřaď .git, .svn, profily, databáze, lokální konfiguraci i provozní data.
Původ, revizi a případné místní odchylky zapiš do docs/00-stav-projektu.md.

Šablona zachovává bajty aplikačních souborů, převody konců řádků nastavuje jen
pro vlastní nástroje a dokumentaci. Před importem prověř kolize názvů souborů
se šablonou; existující README a pravidla sluč vědomě. Kontroly vyžadují UTF-8
pro text. U starého kódování zastav zavedení a navrhni zvláštní ověřený postup,
nepřeváděj zdroje automaticky. SVN vlastnosti a externals vyžadují samostatné posouzení.

## 2. Prázdný privátní GitHub

Založ schválený repozitář jako private, s pravdivým popisem a zapnutými issues.
Nevytvářej automatický README, licenci ani první commit. Aplikační repozitář
zůstává privátní i při případném budoucím zveřejnění obecných nástrojů.
Nastav topics podle technologie a účelu; stejný seznam bude v .prace.json.
Zajisti štítky bug, enhancement, documentation, rozhrani a bez-rozhrani.
Jedinou větví bude main. Pracovní větve a pull requesty se nepoužívají;
na projektu pracuje vždy jeden agent a jeden úkol najednou.

Při použití REST API odpovídá vytvoření osobního repozitáře POST /user/repos
s name, description, private=true, has_issues=true a auto_init=false.
Ověř skutečnou odpověď API; název organizace vyžaduje odpovídající organizační postup.
Tokeny se nevkládají do URL, souborů, commitů ani výstupů.

## 3. Připnutá kopie bez dalšího tokenu

V centrálním nastroje-prace vyber konkrétní commit s úspěšným CI. Pracovní strom
musí být čistý. Příkaz exportuje tento commit, nikoli pohyblivé main. Příklad
specifikace je v sablony/projekt.example.json; nahraď údaje skutečným projektem
a existujícími ověřovacími příkazy. Ukázkový příkaz není náhradou testů aplikace.

```powershell
.\prace.ps1 project-init .local\novy-projekt.json --dest C:\vyvoj\osobni-kopie\nazev-projektu
```

Cílová složka musí předem existovat a být prázdná. Příkaz nezakládá vzdálený
repozitář, nekopíruje aplikační zdroje a neprovádí Git init ani SVN zápis.
Vytvoří pravidla, dokumentaci, hooky, workflow a .prace.json se SVN vypnutým.
.nastroje-prace obsahuje pouze běhové soubory, bez historie, testů a místní cache.
Nastroje-prace.lock.json zaznamená celé zdrojové SHA a SHA-256 každého souboru.
AGENTS.md výslovně načítá .nastroje-prace/pravidla/projekt.md; CLAUDE.md odkazuje na AGENTS.md.

CI čte tuto kopii ze svého vlastního checkoutu. Další token ke čtení privátního
nastroje-prace nepotřebuje. Bootstrap stahuje jen veřejné nastroje, také z pevného
SHA v upstream.lock.json. Přihlášení pro vlastní GitHub issues poskytne místně
Credential Manager a v CI standardní token daného repozitáře s contents: read a issues: read.

## 4. Místní Git, skutečné testy a první issue

V cílové složce proveď git init -b main, nastav origin na skutečný privátní
repozitář a převezmi prověřené zdroje. Nikdy nepřepisuj existující cizí hooky
nebo globální Git konfiguraci. Spusť:

```powershell
.\prace.ps1 bootstrap --php-windows
.\prace.ps1 install-hooks
.\prace.ps1 doctor
```

Na Linuxu použij PHP 8.3+ s požadovanými rozšířeními a php prace.php.
Uprav .prace.json tak, aby tests obsahovalo skutečné ověření aplikace jako
pole argumentů. Příkazy se spouštějí bez shellu; {php} označuje právě běžící PHP.
Nepoužívej pouhé echo, exit 0 ani test kontrol místo testu aplikace.
Pro Java/Node a další prostředí připrav příslušné runtime, závislosti a testy
v obou úlohách .github/workflows/kontroly.yml. Šablona instaluje pouze kontrolní PHP.
Neproveditelný test je překážka, žádná automatická výjimka.

Přes issue-create založ první skutečné issue dle docs/01-postup-prace.md v centrálních
nástrojích. Převezmi vrácené číslo, nepředpokládej #1. Metadata mají podobu:

```json
{"issue": 7, "mantis": null, "technical_reason": "Zavedení osobních kontrol projektu.", "visual": false, "delivery": "git"}
```

Číslo 7 v příkladu nahraď skutečným číslem i v názvech .tasks/7.json,
docs/ukoly/7.md a commitu. Záznam má Zadání, Změna, Ověření a Předání.
README odkazuje na celou složku docs/ukoly/, nikoli na seznam jednotlivých úkolů.
Základní README šablony popisuje připojené kontroly. Před dokončením doplň
skutečné funkce aplikace, její zdrojové složky, instalaci závislostí a způsob
nasazení. Zachovej tabulku technologií, strom, dokumentaci s cestami v řádkovém
kódu podle společného vzoru a příkazy v kódových blocích. Nevydávej instalaci
PHP kontrol za instalaci celé aplikace.
U následných změn rozhraní platí skutečné snímky před a po z pravidel projektu.

## 5. První commit bez ochrany main

Zkontroluj git status, git diff --cached a seznam všech přidávaných souborů.
První commit má český konkrétní nadpis s (#ČÍSLO), Důvod: a Ověření:.
Po prvním místním commitu spusť state-update. Případnou aktualizaci generovaného
stavu přidej a amenduj do dosud nepublikovaného prvního commitu. Tím zůstane
ROOT platným základem. Neamenduj již zveřejněnou historii.

```text
php prace.php readme-check
php prace.php project-check
php prace.php check --base ROOT --online
```

První main se vytvoří jediným ověřeným pushem. Pro nový repozitář ještě před
prvním commitem nelze doložit CI pro toto SHA. Po úspěchu obou platforem a souhrnné
kontroly nastav main jako výchozí. Ochranu větve ani ruleset pro main nezakládej.
Tento postup platí i pro další přímé pushe na main. Místní hooky kontrolují
změnu před pushem, GitHub CI po něm. Úspěšné CI přesného main se vyžaduje před
předáním do SVN a dokončením issue. Žádnou další větev kvůli CI nezakládej.
Ve verzi 0.5 vlastník odstranění celé ochrany schválil pro nastroje-prace.
U jiného již existujícího projektu nejprve zjisti jeho vlastní pravidla;
aktualizace sady sama není souhlasem měnit cizí nastavení GitHubu.
Project-check ochranu nevyžaduje, nemění a nečte její administrativní API.

## 6. Důkaz dokončení a SVN

Proveď čistý clone do jiné dočasné složky a opakuj bootstrap, install-hooks,
project-check a check --online se správným základem. Místní Git config ani
runtime se klonováním nepřenášejí. Ověř také, že neplatný commit hook odmítne
a že změna bez záznamu úkolu neprojde kontrolou; negativní pokusy nepatří na main.

Project-check --online navíc čte GitHub metadata, štítky a seznam větví,
vyžaduje úspěšné místní online ověření, CI stejného SHA a shodu s aktuálním main.
Odmítne další větev. Spouštěj jej místně s přístupem k projektu a výsledkům CI;
kvůli ochraně větve nepotřebuje administrativní oprávnění.
Vygenerovaný checklist v docs/05-napojeni-pravidel.md odškrtni jen podle důkazů.
Pro čtení výsledku CI potřebuje místní přístup také Actions: read.
Dokončení zaváděcího issue proveď přes issue-close po splnění všech kroků.

SVN zapínej samostatně až po ověření zdrojové revize a výslovného allow seznamu.
Vytvoř svn-zaklad před první aplikační změnou. Předání se řídí docs/02-predani-svn.md;
kopie nástrojů, hooky, jejich spouštěče ani dokumentace se do SVN nepřenášejí.

## Aktualizace a hranice

Při přechodu z verze 0.1 zopakuj bootstrap a install-hooks; místní konfigurace
se převede z Pythonu na PHP. Od verze 0.3 používají Git hooky složku hooky
místo .githooks. Také tehdy zopakuj install-hooks. Staré doklady ověření
nahraď kontrolou nové verze. Aktuální režim pouze main bez ochrany popisuje krok 5.

Aktualizace je samostatné issue. Z nové ověřené centrální verze vytvoř export
do další prázdné složky se stejnou specifikací projektu. Posuď diff běhové kopie,
locku, spouštěčů, pravidel a workflow. Přenes ověřené změny těchto souborů;
vlastní AGENTS, README, .prace.json a dokumentaci aplikace vědomě sluč.
Nekopíruj nový export přes celý existující projekt. Opakuj kontroly i čistý clone.
Automatická aktualizace na nejnovější main ani hromadné zapojení projektů neexistují.

Otisky odhalují neúmyslně změněnou kopii. Nejsou podpisem autora a nebrání
vlastníkovi změnit nástroje i lock současně. Smysl testů, pravdivost dokumentace,
úplnost CI prostředí a citlivost převzatých souborů vyžadují review.

Podklady: [vytvoření repozitáře](https://docs.github.com/en/rest/repos/repos#create-a-repository-for-the-authenticated-user),
[ochrana větví](https://docs.github.com/en/rest/branches/branch-protection) a
[checkout a oprávnění tokenu](https://github.com/actions/checkout).
