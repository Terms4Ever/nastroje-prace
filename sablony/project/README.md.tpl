# 🧰 {{NAME}}

**{{DESCRIPTION}}**

Osobní privátní pracovní kopie projektu. Postup napojení a hranice předání
popisuje dokumentace; společný pracovní postup kolegů zůstává v SVN.

![Pravidla](https://img.shields.io/badge/pravidla-nastroje--prace-blue)
![License](https://img.shields.io/badge/license-proprietary-red)
[![Kontroly](https://github.com/{{REPOSITORY}}/actions/workflows/kontroly.yml/badge.svg)](https://github.com/{{REPOSITORY}}/actions/workflows/kontroly.yml)

---

## ✨ Hlavní funkce

{{DESCRIPTION}}

Připojená pracovní pravidla zajišťují:

- **Zadání** - dohledatelné změny propojené s issues a případným zadáním Mantis.
- **Ověření** - dokumentaci a skutečné testy aplikace před předáním.
- **Pravidla** - připnutou kopii kontrol bez dalšího přístupového tokenu v CI.

---

## 🛠️ Tech Stack

| Vrstva | Technologie |
|---|---|
| Aplikace | {{STACK}} |
| Pracovní kontroly | PHP 8.3+, rozšíření mbstring, curl, zip a SimpleXML |
| Brána před pushem | Git hooky ve složce `hooky/` |
| Kontrola po pushi | GitHub Actions na Windows a Linuxu |

---

## 📁 Struktura projektu

```text
./
├── .nastroje-prace/         # připnutá kopie kontrol
├── nastroje-prace.lock.json # původ a otisky kopie
├── .prace.json             # nastavení a testy aplikace
├── .tasks/                 # metadata jednotlivých issues
├── .github/                # workflow kontrol
├── hooky/                  # kontrola commitu a pushe
├── prace.php               # vstupní bod kontrol
├── prace.ps1               # spouštěč na Windows
└── docs/                   # stav, návody a záznamy úkolů
```

---

## 📚 Dokumentace

| Dokument | K čemu |
|---|---|
| [Stav projektu](docs/00-stav-projektu.md) | Současný stav a hranice projektu. |
| [Rozhodovací deník](docs/03-rozhodovaci-dennik.md) | Důvody zvoleného pracovního postupu. |
| [Napojení pravidel](docs/05-napojeni-pravidel.md) | Dokončení napojení a ověřovací checklist. |
| [Záznamy úkolů](docs/ukoly/) | Stručné záznamy podle čísla issue. |

---

## 🚀 Instalace (lokální vývoj)

Příkazy spouštěj z kořene místní kopie projektu.

**Windows (PowerShell):**

```powershell
.\prace.ps1 bootstrap --php-windows
.\prace.ps1 install-hooks
.\prace.ps1 doctor
```

**Linux (PHP 8.3+ s rozšířeními z tabulky výše):**

```bash
php prace.php bootstrap
php prace.php install-hooks
php prace.php doctor
```

Tyto příkazy připravují pracovní kontroly. Prostředí a závislosti aplikace
musí odpovídat jejímu skutečnému stacku. [Postup napojení](docs/05-napojeni-pravidel.md)
zahrnuje převzetí zdrojů, první issue i nastavení GitHubu.

Skutečné testovací příkazy určuje `.prace.json`. Hook je spouští před pushem;
ruční kontrola používá `check --base VYCHOZI_SHA --online`, kde nahradíš
`VYCHOZI_SHA` skutečným základem změny. Pro jediný první commit použij `ROOT`.

---

## 📦 Nasazení

**Pracuje vždy jeden agent pouze na `main`.** Místní hooky ověřují přímý push,
GitHub CI jej ověří po zápisu. Předání do SVN a dokončení issue vyžadují místní
online ověření i úspěšné poslední CI stejného aktuálního main.

**Nasazení aplikace má samostatný postup.** Z CI neběží produkční nasazení
ani předání do SVN. Připravenost potvrzuje `project-check --online`.
Konkrétní předání aplikace musí mít vlastní doložený postup a výslednou revizi.

---

## 📄 Licence

Proprietární software. Veškerá práva vyhrazena.
