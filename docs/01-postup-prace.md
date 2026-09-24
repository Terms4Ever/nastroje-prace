# Postup práce a issues

## Začátek

Zkontroluj doctor a vyhledej odpovídající issue. Úkoly aplikace patří k aplikaci,
úkoly těchto nástrojů do tohoto repozitáře. Mantis zůstává původním zdrojem
zadání; issue sleduje osobní zpracování a odkazuje na Mantis, pokud existuje.
Nastroje-prace jsou veřejné a obsahují pouze obecné nástroje. Skutečná pracovní
zadání, interní konfigurace a snímky aplikací patří do příslušných privátních repozitářů.
Pracuje vždy jeden agent a jeden úkol najednou, pouze na main. Před úpravami
na čistém stromu proveď git fetch origin a git pull --ff-only origin main.
Při rozcházející se historii nejprve vyřeš stav; force push nepoužívej.

Vlastník může založit stručný otevřený nápad bez sekcí, štítků a odpovědného,
i jen s názvem. Audit jej ohlásí jako nápad k přepsání. Před zahájením práce
ho agent upraví přes issue-update; metadata .tasks už označují převzatý úkol
a vyžadují plný tvar. Založení agentem, kontrola změny a dokončení tuto výjimku nemají.
GitHub pod stejným osobním účtem nerozliší člověka od agenta; pravidlo proto
vynucují vstupy agenta a kontrola převzatého úkolu, nikoli domnělý původ kliknutí.

V centrálních nástrojích mohou lidé mimo správce a spolupracovníky vložit
veřejný podnět bez pracovního tvaru. Audit jej ohlásí k posouzení, dokud nemá
metadata v .tasks; platí to i pro podnět uzavřený bez převzetí. Před převzetím
jej agent upraví přes issue-update a doplní vlastní záznam. Vstupy create,
update a close zůstávají přísné. Komentáře návštěvníků nepodléhají stylu agenta,
komentáře vlastníka a spolupracovníků ano. Chybějící údaj o autorovi nebo jeho
vztahu k repozitáři výjimku nezakládá. V privátních aplikacích se nic nemění.

Agent připraví JSON mimo verzované soubory, například v .local/issue.json:

```json
{
  "title": "Předání změny vyžaduje doložené ověření",
  "body": "## Cíl\n\nPředání vyžaduje ověření konkrétní verze.\n\n## Hotovo, když\n\n- [ ] Chybějící ověření předání zastaví.\n",
  "labels": ["enhancement", "bez-rozhrani"],
  "assignees": ["Terms4Ever"]
}
```

```text
php prace.php issue-check .local/issue.json
php prace.php issue-create .local/issue.json
```

Kontrola běží i uvnitř issue-create; samostatné issue-check není jedinou pojistkou.
Na Windows nahraď php prace.php spouštěčem .\prace.ps1.
Vstupy jsou předány jako strukturovaná data, nikoli vložené příkazy shellu.
Při shodném názvu otevřeného issue nebo nedostupném API založení selže.
Existující issue upravuje issue-update CISLO SOUBOR. Aktualizace nemění stav issue.

## Dokumentace a metadata

Pro issue vytvoř docs/ukoly/CISLO.md s oddíly Zadání, Změna, Ověření a Předání.
Stručně vysvětli změnu a důkazy; nepřepisuj celé zadání a průběžné komentáře.
Současnou architekturu aktualizuj v odpovídajícím dokumentu.
V Markdown dokumentaci lze dlouhou pomlčku citovat v řádkovém kódu. V běžném
textu, neuzavřené citaci a bloku kódu se dál hlásí. Pravidla commitů a issues
se tímto návratem původní dokumentační výjimky nemění.

V .tasks/CISLO.json jsou issue, mantis (kladné číslo nebo null), technical_reason
(důvod bez Mantis), visual (true/false) a delivery (git/svn). Příklad existuje
v .tasks/1.json. Změna kódu s pouhým nesouvisejícím obrázkem nestačí: v rozsahu
musí být změněn textový záznam issue uvedeného v commitu.

## Snímky

Změna rozhraní má štítek rozhrani a visual=true. Ještě před úpravou pořiď
skutečný původní stav, po testu srovnatelný nový stav. Ulož je do složky
docs/snimky/CISLO-popis jako pred-pohled.png a po-pohled.png. Dvojice mají stejný
zbytek názvu. PNG, JPEG a WebP jsou podporované. Snímky nejprve commitni.

Do sekce Snímky v issue vlož oba obrázky pomocí Markdownu. URL má tvar
https://github.com/OWNER/REPO/blob/PLNE_SHA/docs/snimky/CISLO-popis/pred-pohled.png?raw=true.
Použij skutečné hodnoty, ne tyto zástupné názvy. Odkaz na main ani pouhý textový
odkaz nestačí. Kontrola ověřuje, že odkazovaný Git blob odpovídá místnímu souboru.
Snímky musí být čitelné, srovnatelné a bez osobních či přihlašovacích údajů.

Každý obrázek uveď viditelným nadpisem Před změnou nebo Po změně a názvem
pohledu. Pod nadpis napiš krátký popis původního problému nebo výsledné změny.
Samotný alternativní text uvnitř zápisu obrázku nestačí, při načteném obrázku
jej GitHub nezobrazuje. Použij tento tvar se skutečnými odkazy:

```markdown
### Před změnou: dokumentace v README

Pojmenované odkazy a odlišná věta pod tabulkou.

![Před změnou: dokumentace](https://github.com/OWNER/REPO/blob/PLNE_SHA/docs/snimky/CISLO-popis/pred-pohled.png?raw=true)

### Po změně: dokumentace v README

Cesty souborů a sjednocená věta o aktuálním stavu.

![Po změně: dokumentace](https://github.com/OWNER/REPO/blob/PLNE_SHA/docs/snimky/CISLO-popis/po-pohled.png?raw=true)
```

Obrázky řaď pod sebe v plné šířce. Při více pohledech dokonči nejdřív jednu
dvojici a teprve potom přidej další. Dvěma sloupci nezmenšuj čitelný text.
Otevři skutečné issue a ověř viditelné nadpisy, načtení obrázků i jejich pořadí.

### Výjimka pouze se souhlasem vlastníka

Výjimku schvaluje vlastník pro každý konkrétní případ. Agent nejprve zkusí
snímek pořídit, případně obnovit dostupný původní stav. Pohodlí, vynechaný
pokus nebo pouhé tvrzení o nemožnosti nestačí. Předloží překážku, záznam pokusu,
přesný seznam chybějících pohledů a dostupný náhradní důkaz. Neprovedený test
nesmí označit za provedený; neexistující náhradní ověření nesmí vymyslet.
Je-li dostupný jen záznam selhání, předloží jej a jeho omezení vlastníkovi.

Do metadat vizuálního úkolu přidej screenshot_exception. Ukázka je pouze tvar,
nikoli udělená výjimka; všechny hodnoty musí odpovídat konkrétnímu případu:

```json
{
  "missing": ["docs/snimky/17-detail/pred-formular.png"],
  "reason": "Původní služba odmítá spojení a pohled nelze otevřít.",
  "attempt": "Pokus o spuštění služby a načtení původního pohledu skončil chybou spojení.",
  "evidence": ["docs/prilohy/17-pokus.txt"]
}
```

Přiložené důkazy ulož bez citlivých údajů do docs/prilohy a přidej do Gitu. Záznam
konkrétního pokusu zachovej; odkaz na později měněný přehled není vhodný důkaz.
Příkaz pouze připraví podklady, souhlas ani komentář nevytváří:

```text
php prace.php snimky-zadost 17
```

Vlastník rozhoduje nad těmito podklady. Po jeho výslovném souhlasu v chatu
agent zaznamená do stejného issue komentář: první řádek je přesný approval_text
z výstupu příkazu, druhý stručně cituje souhlas vlastníka a uvádí kontext jeho
rozhodnutí. Pokud tento komentář vložil sám vlastník, použije se přímo.
Číselné ID komentáře ulož do screenshot_exception.approval_comment; odkaz
na něj vlož do sekce Snímky. Změna metadat potřebuje běžný commit a ověření.
Souhlas s obecnými pravidly nebo celým úkolem není souhlas s konkrétní výjimkou.

Při dokončení, kontrole zavřeného issue a jeho úpravě se přes API znovu ověří
komentář vlastníka, stejné issue a otisk přesných cest, překážky, pokusu i obsahu
důkazů. Změna podkladů, odvolaný či smazaný komentář a nedostupné API výjimku
zastaví. Samotný štítek nestačí. Chybějící protějšky mimo schválený seznam,
neplatné odkazy a totožné dostupné obrázky nadále neprojdou.
Odvolání souhlasu zaznamenej změnou nebo odstraněním původního schvalovacího komentáře.

GitHub nerozliší člověka a agenta používající stejný osobní účet. Kontrola
proto ověřuje dohledatelný záznam a jeho vazby; pravdivost překážky posuzuje
vlastník a agent nesmí jeho souhlas sám vytvořit. Při čekání zůstává issue otevřené.

## Commit a push

```text
Předání ověřuje změny souborů od převzetí (#1)

Důvod: Novější změna kolegy se nesmí přepsat.
Ověření: Integrační test zachytil souběžnou změnu v dočasném SVN.
```

Commit uvádí skutečně provedené ověření. Nepoužívej automatické uzavírání
pomocí Closes/Fixes: dokončení ověřuje samostatný příkaz. Lokální install-hooks
zavede kontrolu zprávy a před pushem celý check --online. Commit mimo main
je odmítnut. Push může obsahovat pouze místní HEAD z main do vzdáleného main;
další větev, tag nebo odstranění jsou odmítnuté před API a testy.
Základem kontroly je skutečné předchozí SHA vzdáleného main, při jediném prvním
commitu ROOT. Pracovní větve, pull requesty ani paralelní worktree nezakládej.

Po git push origin main spustí GitHub stejné jádro na Windows a Linuxu.
GitHub ochrana main se nepoužívá. Místní hook ověří push před zápisem,
GitHub CI po něm. Chybu v CI oprav novým commitem na main.
Dokud není CI zelené, nepředávej změnu a nezačínej další úkol.
Ruční spuštění workflow vyžaduje explicitní base; zadej celý původní rozsah,
ne jen poslední opravný commit. Opakování původního běhu zachovává jeho rozsah.
Případné změny kódu či pravidel zneplatní starý místní doklad.

## Dokončení

Po splnění aktualizuj checklist přes issue-update. Platný online doklad stejného
čistého commitu lze použít z pre-push kontroly; při změně obsahu nebo pravidel
opakuj check. Samotná aktualizace checklistu nevyžaduje opakovat nezměněné testy.
Issue-close kontroluje místní doklad, živé issue, snímky, úspěšné CI stejného
commitu a shodu s main. U SVN také ověřený záznam předání. Doplní krátký
komentář a issue uzavře. Nedokončený úkol zůstává otevřený.

Kontrola vyžaduje poslední běh workflow kontroly.yml pro stejné SHA na main,
jeho dokončení s úspěchem a úspěšnou souhrnnou úlohu Povinne kontroly.
Čekající běh, opakování s chybou, chybějící souhrn nebo nedostupné API zastaví
dokončení. Starší úspěšný běh tento stav nenahradí. Úklid větví není potřeba.

```text
php prace.php issue-close 1 --summary "Kontrola prokazatelně zachytí neověřené předání."
```

Přímý zápis přes jiného API klienta může obejít místní kontrolu. GitHub workflow
ho následně zkontroluje, nedokáže jej předem zablokovat. Standardní cesta agenta
je proto příkaz této sady, nezávislý na konkrétním asistentovi.
Workflow reaguje na změny issues i komentářů a lze jej spustit ručně.
Denní plán je odstraněn. Způsob hlášení výsledku workflow se touto změnou nemění.
Vlastník může také obejít lokální Git hooky. Main-only pravidla a hooky řídí
standardní postup; nejde o zákaz vytváření větví přes jiné API klienty.
