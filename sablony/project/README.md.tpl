# 🧰 {{NAME}}

**{{DESCRIPTION}}**

Osobní privátní pracovní kopie projektu. Postup napojení a hranice předání
popisuje dokumentace; společný pracovní postup kolegů zůstává v SVN.

[![Pravidla: nastroje-prace](https://img.shields.io/badge/pravidla-nastroje--prace-8250df)](https://github.com/Terms4Ever/nastroje-prace)
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
├── .pravidla.json          # jediná hlavní sada pravidel
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
| `docs/00-stav-projektu.md` | živý stav: co je hotové, co se dělá, co je dál, a které repozitáře jsou zapojené |
| `docs/03-rozhodovaci-dennik.md` | co bylo kdy rozhodnuto a proč. Nové rozhodnutí je nový záznam, staré se nepřepisuje |
| `docs/05-napojeni-pravidel.md` | dokončení napojení a ověřovací checklist |
| `docs/ukoly/` | stručné záznamy podle čísla issue |

Stav vždy platný je v `docs/00-stav-projektu.md`, ne v tomhle souboru.

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
online ověření i úspěšné poslední CI stejného aktuálního main. Ochrana větve
se nezakládá; kontroly issues reagují na události bez denního plánu.

**Nasazení aplikace má samostatný postup.** Z CI neběží produkční nasazení
ani předání do SVN. Připravenost potvrzuje `project-check --online`.
Konkrétní předání aplikace musí mít vlastní doložený postup a výslednou revizi.

Sadu `nastroje-prace` určuje `.pravidla.json`. Stejný výběr uvádí odznak,
AGENTS.md, GitHub topic `pravidla-nastroje-prace` a Actions
`Pravidla / nastroje-prace`. Ověří je `php prace.php rules-check` a online
`metadata-check`. Přesná verze kopie dál zůstává v `nastroje-prace.lock.json`.

---

## 📄 Licence

Proprietární software. Veškerá práva vyhrazena.
