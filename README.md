# 🧰 Nástroje pro práci

**Osobní kontroly dokumentace, issues a předání změn**

Privátní pracovní nadstavba společných nástrojů. Ověřuje konkrétní změnu,
její záznam a důkazy. Připravuje kontrolované předání do SVN a ponechává
kolegům jejich stávající postup.

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)
![Závislosti](https://img.shields.io/badge/z%C3%A1vislosti-nastroje-blue)
![License](https://img.shields.io/badge/license-proprietary-red)
![Kontroly](https://github.com/Terms4Ever/nastroje-prace/actions/workflows/kontroly.yml/badge.svg)

---

## ✨ Hlavní funkce

- Validace issue před založením, správný druh, odpovědný a ověřitelný checklist.
- Kontrola krátkých pomlček, zpráv commitů a textové dokumentace změny.
- Povinné vložené dvojice snímků před a po u dokončených změn rozhraní.
- Testy vázané na přesný commit; chybějící nebo neprovedené ověření není úspěch.
- Příprava balíčku vybraných souborů a detekce souběžných změn v SVN.
- Evidence propojení issue, Mantis, Git commitu a ověřené SVN revize.
- Lokální hooky, GitHub kontroly na Windows i Linuxu a samostatná kontrola issues.
- Práce jednoho agenta pouze na main; úspěšné CI před předáním a dokončením úkolu.
- Ověření privátního repozitáře, GitHub topics a jednotného pořadí README.
- Založení kostry pracovního projektu s připnutou kopií pravidel a kontrolou napojení.

---

## 🛠️ Tech Stack

| Část | Prostředí |
|---|---|
| Kontroly a testy | PHP 8.3+ s mbstring, curl, zip a SimpleXML |
| Sdílená pravidla | PHP, přesný commit v upstream.lock.json |
| Spouštění na Windows | PowerShell, instalace vlastního lokálního PHP |
| Historie | Git a GitHub REST API |
| Volitelné předání | SVN CLI; integrační testy používají také svnadmin |

---

## 📁 Struktura projektu

```text
prace.php         # jednotný PHP vstupní bod
prace.ps1         # spouštěč pro Windows
src/              # kontrolní příkazy a adaptéry v PHP
tests/            # pozitivní a negativní scénáře
scripts/          # hook a CI vstupní body
hooky/            # lokální kontrola commitu a pushe
pravidla/         # společná pravidla pro připojené aplikace
sablony/          # kostra nového projektu a vzor nastavení
.github/          # workflow a šablona issue
.tasks/           # metadata jednotlivých úkolů
docs/             # stav, pravidla, postupy a záznamy
```

---

## 📚 Dokumentace

| Dokument | Účel |
|---|---|
| `docs/00-stav-projektu.md` | Současné možnosti a hranice. |
| `docs/01-postup-prace.md` | Příkazy pro issue, commit a dokončení. |
| `docs/02-predani-svn.md` | Výchozí stav, balíček a revize. |
| `docs/03-rozhodovaci-dennik.md` | Důvody hlavních pravidel. |
| `docs/04-overeni.md` | Co testy dokazují a co nedokazují. |
| `docs/05-novy-pracovni-projekt.md` | Založení privátního projektu a napojení pravidel. |
| `docs/ukoly/` | Záznamy jednotlivých úkolů podle čísla issue. |

---

## 🚀 Instalace (lokální vývoj)

Na Windows spusť z kořene projektu PowerShell:

```powershell
.\prace.ps1 bootstrap --php-windows
.\prace.ps1 install-hooks
.\prace.ps1 doctor
```

PHP lze určit proměnnou NASTROJE_PHP. Bez ní spouštěč upřednostní vlastní
runtime v .cache/php, potom místní nastavení hooku a PHP dostupné v PATH.
Stažené PHP se kontroluje SHA-256 a instaluje pouze do tohoto repozitáře.
Při přechodu z verze 0.1 zopakuj bootstrap a install-hooks; odstraní se
původní místní nastavení Pythonu. Staré doklady ověření je nutné vytvořit znovu.
Od verze 0.3 používají místní Git hooky složku hooky místo .githooks; spusť
install-hooks také při této aktualizaci. Nastavení platí pouze pro tento projekt.

Na Linuxu s Gitem a PHP s uvedenými rozšířeními:

```text
php prace.php bootstrap
php prace.php install-hooks
php tests/run.php
php prace.php readme-check
php prace.php metadata-check
php prace.php check --base HEAD^ --online
```

Na Windows pro testy použij .\.cache\php\php.exe tests/run.php. Ostatní
příkazy lze spouštět přes .\prace.ps1, například .\prace.ps1 readme-check.

U první změny použij ROOT, později skutečný výchozí commit. Přesný postup
pro issues a SVN popisují dokumenty v tabulce výše. Přihlášení poskytuje Git Credential
Manager nebo proměnná GH_TOKEN/GITHUB_TOKEN; hodnoty se neukládají do projektu.

---

## 📦 Nasazení

Tento repozitář nemá produkční deploy. Od verze 0.4 se pracuje pouze na main,
bez pracovních větví a pull requestů. Místní hook ověřuje změnu před přímým
pushem; Windows a Linux CI ji ověří po pushi. Úspěšný poslední běh a jeho
souhrnná úloha Povinne kontroly podmiňují předání do SVN a dokončení issue.
Main chrání zákaz force pushe a smazání i pro správce, s lineární historií.
Při aktualizaci z verze 0.3 uprav ochranu podle postupu zavedení projektu:
povinné CI před pushem se vypíná, ostatní uvedené ochrany zůstávají zapnuté.
Issues a metadata repozitáře se ověřují po změnách issues i denně. Automatika
issues neopravuje ani neuzavírá. Povinné topics určuje .prace.json; metadata-check
ověří jejich přítomnost a privátní viditelnost bez změn nastavení GitHubu.

Připnuté nástroje se stahují do soukromé pracovní cache. Existující nastroje
ani jiné repozitáře se neaktualizují. Pracovní projekty nejsou touto instalací
připojeny automaticky. Přenosový balíček sám nic do SVN nezapisuje.

Pro nový projekt použij project-init a úplný postup v dokumentaci. Výsledkem
je připnutá kopie kontrol s otisky a místní checklist zavedení. Project-check
ověří napojení; varianta --online navíc kontroluje GitHub a úspěšné CI na main.

---

## 📄 Licence

Proprietární software. Veškerá práva vyhrazena. Použité společné nástroje
zůstávají v původním repozitáři a používají se v připnuté verzi.
