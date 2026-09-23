# Postup práce a issues

## Začátek

Zkontroluj doctor a vyhledej odpovídající issue. Úkoly aplikace patří k aplikaci,
úkoly těchto nástrojů do tohoto repozitáře. Mantis zůstává původním zdrojem
zadání; issue sleduje osobní zpracování a odkazuje na Mantis, pokud existuje.
Pracuje vždy jeden agent a jeden úkol najednou, pouze na main. Před úpravami
na čistém stromu proveď git fetch origin a git pull --ff-only origin main.
Při rozcházející se historii nejprve vyřeš stav; force push nepoužívej.

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
CI ještě nemůže blokovat přijetí nového SHA, proto ochrana main nevyžaduje
předchozí výsledek CI ani pull request. Zůstává zákaz force pushe, smazání
a nelineární historie i pro správce. Chybu v CI oprav novým commitem na main.
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
Vlastník může také obejít lokální Git hooky. Main-only pravidla a hooky řídí
standardní postup; nejde o zákaz vytváření větví přes jiné API klienty.
