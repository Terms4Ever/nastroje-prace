# 🧰 Nástroje pro práci

**Osobní kontroly dokumentace, issues a předání změn**

Privátní pracovní nadstavba společných nástrojů. Ověřuje konkrétní změnu,
její záznam a důkazy. Připravuje kontrolované předání do SVN a ponechává
kolegům jejich stávající postup.

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)
![Závislosti](https://img.shields.io/badge/z%C3%A1vislosti-nastroje-blue)
![License](https://img.shields.io/badge/license-proprietary-red)
[![Kontroly](https://github.com/Terms4Ever/nastroje-prace/actions/workflows/kontroly.yml/badge.svg)](https://github.com/Terms4Ever/nastroje-prace/actions/workflows/kontroly.yml)

---

## ✨ Hlavní funkce

- **Issues** - agent dodržuje strukturu, zařazení a checklist; tvoje stručné nápady smí počkat na zpracování.
- **Dokumentace** - české texty, krátké pomlčky, přesné commity a vlastní textový záznam každého úkolu.
- **Snímky před a po** - skutečné dvojice obrázků u změn rozhraní, vložené do issue s neměnnými odkazy.
- **Ověření** - místní testy a GitHub CI pro stejný commit; jeden agent pracuje pouze na `main`.
- **Předání do SVN** - balíček povolených souborů, odhalení změn kolegů a ověření výsledné revize.
- **Nový projekt** - připnutá kopie kontrol, jednotný README, privátní GitHub s topics a ověřené napojení.

---

## 🛠️ Tech Stack

| Vrstva | Technologie |
|---|---|
| Kontroly a testy | PHP 8.3+, rozšíření mbstring, curl, zip a SimpleXML |
| Sdílená pravidla | PHP kontroly z nastroje, připnuté v [upstream.lock.json](upstream.lock.json) |
| Spouštění na Windows | PowerShell a místní PHP |
| Brána před pushem | Git hooky ve složce `hooky/` |
| Kontrola po pushi | GitHub Actions na Windows a Linuxu |
| Volitelné předání | SVN CLI; integrační testy navíc používají `svnadmin` |

---

## 📁 Struktura projektu

```text
nastroje-prace/
├── prace.php             # jednotný vstup kontrol
├── prace.ps1             # spouštěč pro Windows
├── src/                  # PHP kontroly a adaptéry
├── tests/                # pozitivní a negativní scénáře
├── scripts/              # vstupy pro hooky a CI
├── hooky/                # kontrola commitu a pushe
├── pravidla/             # pravidla připojených aplikací
├── sablony/              # kostra nového projektu
├── .github/              # workflow a šablona issue
├── .tasks/               # metadata jednotlivých úkolů
├── .prace.json           # nastavení kontrol a testů
├── upstream.lock.json    # připnutá verze nastroje
└── docs/                 # stav, návody a záznamy úkolů
```

---

## 📚 Dokumentace

| Dokument | K čemu |
|---|---|
| [Stav projektu](docs/00-stav-projektu.md) | Současné možnosti a hranice. |
| [Postup práce](docs/01-postup-prace.md) | Issue, dokumentace, commit a dokončení. |
| [Předání do SVN](docs/02-predani-svn.md) | Výchozí stav, balíček a výsledná revize. |
| [Rozhodovací deník](docs/03-rozhodovaci-dennik.md) | Důvody hlavních pravidel. |
| [Ověření](docs/04-overeni.md) | Rozsah testů a jejich omezení. |
| [Nový pracovní projekt](docs/05-novy-pracovni-projekt.md) | Založení repozitáře, napojení a aktualizace. |
| [Záznamy úkolů](docs/ukoly/) | Stručné záznamy podle čísla issue. |

Aktuální stav patří do dokumentace. Jednotlivé úkoly sledují
[GitHub Issues](https://github.com/Terms4Ever/nastroje-prace/issues).

---

## 🚀 Instalace (lokální vývoj)

Nejprve naklonuj privátní repozitář pomocí přihlášeného Gitu:

```bash
git clone https://github.com/Terms4Ever/nastroje-prace.git
cd nastroje-prace
```

**Windows (PowerShell):**

```powershell
.\prace.ps1 bootstrap --php-windows
.\prace.ps1 install-hooks
.\prace.ps1 doctor
```

Spouštěč stáhne a ověří vlastní PHP do `.cache/`. Existující PHP lze určit
proměnnou `NASTROJE_PHP`. Hooky se nastavují jen v tomto repozitáři.

**Linux (PHP 8.3+ s rozšířeními z tabulky výše):**

```bash
php prace.php bootstrap
php prace.php install-hooks
php prace.php doctor
```

**Ověření instalace:** na Windows spusť `./.cache/php/php.exe tests/run.php`,
na Linuxu `php tests/run.php`. Podrobné scénáře a podmínky SVN testů popisuje
[ověření funkčnosti](docs/04-overeni.md).

Přihlášení poskytuje Git Credential Manager nebo proměnná
`GH_TOKEN`/`GITHUB_TOKEN`. Hodnoty se neukládají do projektu. První úkol,
správný základ kontroly a dokončení popisuje [postup práce](docs/01-postup-prace.md).

---

## 📦 Nasazení

**Práce probíhá pouze na `main`.** Místní hook prověří přímý push, GitHub CI
ověří stejný commit po něm. Úspěšný poslední běh a jeho souhrnná úloha
`Povinne kontroly` podmiňují předání do SVN a dokončení issue. Ochrana větve
na GitHubu se nepoužívá; pracovní postup agenta hlídají místní hooky.

**Nový projekt dostane připnutou kopii kontrol.** Příkaz `project-init`
připraví kostru; `project-check --online` ověří skutečné napojení.
[Úplný postup](docs/05-novy-pracovni-projekt.md) zahrnuje také aktualizaci pravidel.

**SVN předání zůstává samostatným krokem.** CI nenasazuje aplikaci a balíček
sám neprovádí SVN commit. [Postup předání](docs/02-predani-svn.md) ověřuje
výchozí stav, případné změny kolegů a výslednou revizi.

Issues a metadata se kontrolují po změnách issues a při ručním spuštění, bez denního plánu. Kontroly vyžadují
privátní repozitář a topics z `.prace.json`; žádné jiné repozitáře nemění.

---

## 📄 Licence

Proprietární software. Veškerá práva vyhrazena.
