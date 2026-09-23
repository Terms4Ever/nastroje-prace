# Postup práce a issues

## Začátek

Zkontroluj doctor a vyhledej odpovídající issue. Úkoly aplikace patří k aplikaci,
úkoly těchto nástrojů do tohoto repozitáře. Mantis zůstává původním zdrojem
zadání; issue sleduje osobní zpracování a odkazuje na Mantis, pokud existuje.

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
zavede kontrolu zprávy a před pushem celý check --online. Základ při novém
pracovním branchi je společný předek s origin/main, při prvním commitu ROOT.
Před novou prací aktualizuj vzdálené reference.

Trvalá větev je main. Pracovní větev pojmenuj ukol/CISLO-kratky-popis podle
skutečného issue a ponech ji jen po dobu rozpracované práce.

GitHub CI spouští stejné jádro na Windows a Linuxu. Ochrana main vyžaduje
souhrnný stav Povinne kontroly. Běžný postup je push pracovní větve, zelené CI
a následné přijetí ověřeného commitu do main. Ruční schválení jiné osoby se
nevyžaduje. Případné změny kódu či pravidel zneplatní starý místní doklad.

## Dokončení

Po splnění aktualizuj checklist přes issue-update a opakuj online check.
Issue-close kontroluje místní doklad, živé issue, snímky, úspěšné CI stejného
commitu a shodu s main. U SVN také ověřený záznam předání. Doplní krátký
komentář a issue uzavře. Nedokončený úkol zůstává otevřený.

Součástí dokončení je úklid větve. Po přijetí ověřeného commitu na main přepni
na main, ověř převzetí obsahu a nepřítomnost otevřených pull requestů. Odstraň
vlastní dokončenou vzdálenou i místní větev a proveď git fetch --prune.
Při zjištění dalšího nepřevzatého commitu nemaž větev automaticky. Výslovně
prověřený jednorázový negativní test můžeš před odstraněním zálohovat místně;
odkaz na CI a SHA zůstane v záznamu úkolu. Testovací větev není trvalý archiv.

Na GitHubu zapni Automatically delete head branches. Toto nastavení odstraňuje
větev po sloučení pull requestu. Při přímém přijetí commitu na main musí úklid
provést agent. [Pravidla GitHubu](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/configuring-pull-request-merges/managing-the-automatic-deletion-of-branches).

```text
php prace.php issue-close 1 --summary "Kontrola prokazatelně zachytí neověřené předání."
```

Přímý zápis přes jiného API klienta může obejít místní kontrolu. GitHub workflow
ho následně zkontroluje, nedokáže jej předem zablokovat. Standardní cesta agenta
je proto příkaz této sady, nezávislý na konkrétním asistentovi.
