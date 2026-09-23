# 🧰 Nástroje pro práci

**Osobní kontroly dokumentace, issues a předání změn**

Privátní pracovní nadstavba společných nástrojů. Ověřuje konkrétní změnu,
její záznam a důkazy. Připravuje kontrolované předání do SVN a ponechává
kolegům jejich stávající postup.

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4)
![Kontroly](https://github.com/Terms4Ever/nastroje-prace/actions/workflows/kontroly.yml/badge.svg)
![Licence](https://img.shields.io/badge/licence-proprietarni-red)

---

## ✨ Hlavní funkce

- Validace issue před založením, správný druh, odpovědný a ověřitelný checklist.
- Kontrola krátkých pomlček, zpráv commitů a textové dokumentace změny.
- Povinné vložené dvojice snímků před a po u dokončených změn rozhraní.
- Testy vázané na přesný commit; chybějící nebo neprovedené ověření není úspěch.
- Příprava balíčku vybraných souborů a detekce souběžných změn v SVN.
- Evidence propojení issue, Mantis, Git commitu a ověřené SVN revize.
- Lokální hooky, GitHub kontroly na Windows i Linuxu a samostatná kontrola issues.
- Ověření privátního repozitáře, GitHub topics a jednotného pořadí README.

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
.githooks/        # lokální kontrola commitu a pushe
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
| `docs/ukoly/1.md` | Zavedení první verze. |
| `docs/ukoly/2.md` | Sjednocení PHP, README a GitHub topics. |

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

Tento repozitář nemá produkční deploy. Nová verze prochází Windows a Linux CI;
souhrnná kontrola Povinne kontroly slouží jako podmínka přijetí na main.
Issues a metadata repozitáře se ověřují po změnách issues i denně. Automatika
issues neopravuje ani neuzavírá. Povinné topics určuje .prace.json; metadata-check
ověří jejich přítomnost a privátní viditelnost bez změn nastavení GitHubu.

Připnuté nástroje se stahují do soukromé pracovní cache. Existující nastroje
ani jiné repozitáře se neaktualizují. Pracovní projekty nejsou touto instalací
připojeny automaticky. Přenosový balíček sám nic do SVN nezapisuje.

---

## 📄 Licence

Proprietární software. Veškerá práva vyhrazena. Použité společné nástroje
zůstávají v původním repozitáři a používají se v připnuté verzi.
