# Stav projektu

Verze 0.4 osobních kontrol je samostatná privátní PHP nadstavba. Zdrojové
pracovní projekty se sem nekopírují a jejich nastavení se při instalaci nemění.

<!-- generovano nastroji, needitovat -->
```
hlavní větev:     main
```
<!-- konec generovaneho bloku -->

## Dostupné schopnosti

Validace agentem vytvářeného issue, komentářů a commitů; kontrola dokumentace
změny; povinné testovací příkazy; doklad ověření přesného Git commitu;
kontrola snímků při dokončení; čtení SVN a balíček výslovně povolených změn.
Celý běh i testy používají PHP. PowerShell je místní spouštěč a instalátor PHP.
Kontrola README doplňuje pořadí sekce Dokumentace před Instalací. Online check
ověřuje také privátní viditelnost a požadované GitHub topics.
Project-init vytváří kostru projektu s přesnou kopií kontrol a pravidel pro
agenta. Project-check ověřuje napojení, s --online také ochranu main, metadata
a důkazy stejného commitu. Sada byla ověřena na dočasné aplikaci; skutečné
pracovní aplikace se připojí samostatně podle vlastních zdrojů a testů.

Jedinou větví je main a pracuje vždy jeden agent. Lokální hooky kontrolují
commit a push před zápisem, GitHub CI po něm. Předání do SVN, dokončení issue
a online připravenost vyžadují místní online doklad i úspěšné CI stejného SHA
na aktuálním main. Ochrana main zakazuje přepsání a odstranění také správci.

Sdílené PHP validátory jsou připnuté na commit v upstream.lock.json. Jejich
známé mezery v chybějící dokumentaci a rozsahu změny kryje pracovní kontrola.
Nepoužívá se upstream workflow na main ani jeho hromadná kontrola issues.

## Hranice současné verze

- Význam textu, češtinu a pravdivost snímků musí posoudit člověk nebo agent.
- Detekce citlivých souborů je omezená sada pravidel, nikoli úplný bezpečnostní audit.
- GitHub kontrola issues běží po zápisu; před zápisem blokuje standardní příkaz issue-create/update.
- Lokální potvrzení je doklad pracovního postupu, nikoli kryptografická ochrana proti vlastníkovi počítače.
- Main může po pushi dočasně obsahovat chybu zjištěnou až v CI; předání zůstane blokované do opravy a úspěšného ověření.
- Snímky mají povinné páry, existující obsah a neměnné odkazy. Neexistuje automatická výjimka pro chybějící snímek.
- Předání do SVN je balíček a kontrola; automatický zápis do pracovní kopie ani SVN commit nejsou implementovány.
- V tomto repozitáři je SVN vypnuto. Zapnutí patří do pozdějšího samostatného zavedení projektu.
- Lokální počítač bez SVN CLI přeskočí SVN integrační testy; Linux CI je vyžaduje.

## Aktualizace

Změnu upstream verze proveď vědomou úpravou lock souboru s testy. Bootstrap
nepřepíná existující cache tiše na jiný commit. Instalace hooků je pouze lokální
a odmítne přepsat již nastavenou jinou cestu hooků.
