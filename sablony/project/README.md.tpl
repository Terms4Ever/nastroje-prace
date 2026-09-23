# 🧰 {{NAME}}

**{{DESCRIPTION}}**

Osobní privátní pracovní kopie projektu. Postup napojení a hranice předání
popisuje dokumentace; společný pracovní postup kolegů zůstává v SVN.

![Kontroly](https://github.com/{{REPOSITORY}}/actions/workflows/kontroly.yml/badge.svg)
![License](https://img.shields.io/badge/license-proprietary-red)

---

## ✨ Hlavní funkce

- Dohledatelné změny propojené s issues a zadáním Mantis.
- Povinná dokumentace a testy aplikace před předáním.
- Připnutá kopie pracovních pravidel bez dalšího přístupového tokenu v CI.

---

## 🛠️ Tech Stack

{{STACK}}

Pracovní kontroly používají PHP 8.3+ s mbstring, curl, zip a SimpleXML.

---

## 📁 Struktura projektu

```text
.nastroje-prace/          # připnutá kopie kontrol, neupravovat ručně
nastroje-prace.lock.json # původ a otisky kopie
.prace.json             # nastavení a skutečné testovací příkazy projektu
.tasks/                 # metadata jednotlivých issues
docs/                   # současný stav, rozhodnutí a záznamy úkolů
prace.php               # vstupní bod kontrol
prace.ps1               # spouštěč na Windows
```

---

## 📚 Dokumentace

| Dokument | Účel |
|---|---|
| `docs/00-stav-projektu.md` | Současný stav a hranice projektu. |
| `docs/03-rozhodovaci-dennik.md` | Důvody zvoleného pracovního postupu. |
| `docs/05-napojeni-pravidel.md` | Dokončení napojení a ověřovací checklist. |
| `docs/ukoly/` | Záznamy jednotlivých úkolů podle čísla issue. |

---

## 🚀 Instalace (lokální vývoj)

Na Windows spusť .\prace.ps1 bootstrap --php-windows, potom install-hooks a doctor.
Na Linuxu s uvedeným PHP spusť php prace.php bootstrap a php prace.php install-hooks.
Celý postup včetně prvního issue, převzetí zdrojů a nastavení GitHubu je v dokumentaci napojení.

Skutečné testovací příkazy určuje .prace.json. Před pushem běží
php prace.php check --base VYCHOZI_SHA --online. Pro první commit použij ROOT.

---

## 📦 Nasazení

GitHub CI provádí ověření. Produkční nasazení ani předání do SVN z něj neběží.
Repozitář je připraven k práci až po dokončení místního i online project-check.
Konkrétní předání aplikace musí mít vlastní doložený postup a výslednou revizi.

---

## 📄 Licence

Proprietární software. Veškerá práva vyhrazena.
