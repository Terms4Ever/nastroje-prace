# 🧰 Nástroje pro práci

**Osobní kontroly dokumentace, issues a předání změn**

Privátní pracovní nadstavba společných nástrojů. Ověřuje konkrétní změnu,
její záznam a důkazy. Připravuje kontrolované předání do SVN a ponechává
kolegům jejich stávající postup.

![Python](https://img.shields.io/badge/Python-3.12-blue)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4)
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

## 🛠️ Tech Stack

| Část | Prostředí |
|---|---|
| Pracovní kontroly | Python 3.12, standardní knihovna |
| Sdílená pravidla | PHP 8.3+ s mbstring, přesný commit v upstream.lock.json |
| Historie | Git a GitHub REST API |
| Volitelné předání | SVN CLI; integrační testy používají také svnadmin |

## 📁 Struktura projektu

```text
nastroje_prace/    # kontrolní příkazy a adaptéry
tests/            # pozitivní a negativní scénáře
scripts/          # hook a CI vstupní body
.githooks/        # lokální kontrola commitu a pushe
.github/          # workflow a šablona issue
.tasks/           # metadata jednotlivých úkolů
docs/             # stav, pravidla, postupy a záznamy
```

## 🚀 Instalace (lokální vývoj)

Na Windows spusť z kořene projektu PowerShell:

```powershell
.\prace.ps1 bootstrap --php-windows
.\prace.ps1 install-hooks
.\prace.ps1 doctor
```

Spouštěč použije nastavený Python nebo dostupný místní runtime. Vlastní cestu
lze nastavit proměnnou NASTROJE_PRACE_PYTHON. PHP je možné určit proměnnou
NASTROJE_PHP. Stažené PHP se kontroluje SHA-256 a instaluje pouze lokálně.
Na Linuxu s Pythonem, Gitem a PHP s mbstring spusť python -m nastroje_prace bootstrap.

```text
python -m unittest discover -s tests -v
python -m nastroje_prace check --base HEAD^ --online
```

U první změny použij ROOT, později skutečný výchozí commit. Přesný postup
pro issues a SVN popisují dokumenty níže. Přihlášení poskytuje Git Credential
Manager nebo proměnná GH_TOKEN/GITHUB_TOKEN; hodnoty se neukládají do projektu.

## 📦 Nasazení

Tento repozitář nemá produkční deploy. Nová verze prochází Windows a Linux CI;
souhrnná kontrola Povinne kontroly slouží jako podmínka přijetí na main.
Issues se ověřují po změnách i denně. Automatika issues neopravuje ani neuzavírá.

Připnuté nástroje se stahují do soukromé pracovní cache. Existující nastroje
ani jiné repozitáře se neaktualizují. Pracovní projekty nejsou touto instalací
připojeny automaticky. Přenosový balíček sám nic do SVN nezapisuje.

## 📚 Dokumentace

| Dokument | Účel |
|---|---|
| `docs/00-stav-projektu.md` | Současné možnosti a hranice. |
| `docs/01-postup-prace.md` | Příkazy pro issue, commit a dokončení. |
| `docs/02-predani-svn.md` | Výchozí stav, balíček a revize. |
| `docs/03-rozhodovaci-dennik.md` | Důvody hlavních pravidel. |
| `docs/04-overeni.md` | Co testy dokazují a co nedokazují. |
| `docs/ukoly/1.md` | Zavedení první verze. |

## 📄 Licence

Proprietární software. Veškerá práva vyhrazena. Použité společné nástroje
zůstávají v původním repozitáři a používají se v připnuté verzi.
