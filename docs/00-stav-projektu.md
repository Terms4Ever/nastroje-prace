# Stav projektu

První verze osobních kontrol je samostatná privátní nadstavba. Zdrojové
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

Sdílené PHP validátory jsou připnuté na commit v upstream.lock.json. Jejich
známé mezery v chybějící dokumentaci a rozsahu změny kryje pracovní kontrola.
Nepoužívá se upstream workflow na main ani jeho hromadná kontrola issues.

## Hranice první verze

- Význam textu, češtinu a pravdivost snímků musí posoudit člověk nebo agent.
- Detekce citlivých souborů je omezená sada pravidel, nikoli úplný bezpečnostní audit.
- GitHub kontrola issues běží po zápisu; před zápisem blokuje standardní příkaz issue-create/update.
- Lokální potvrzení je doklad pracovního postupu, nikoli kryptografická ochrana proti vlastníkovi počítače.
- Snímky mají povinné páry, existující obsah a neměnné odkazy. Neexistuje automatická výjimka pro chybějící snímek.
- Předání do SVN je balíček a kontrola; automatický zápis do pracovní kopie ani SVN commit nejsou implementovány.
- V tomto repozitáři je SVN vypnuto. Zapnutí patří do pozdějšího samostatného zavedení projektu.
- Lokální počítač bez SVN CLI přeskočí SVN integrační testy; Linux CI je vyžaduje.

## Aktualizace

Změnu upstream verze proveď vědomou úpravou lock souboru s testy. Bootstrap
nepřepíná existující cache tiše na jiný commit. Instalace hooků je pouze lokální
a odmítne přepsat již nastavenou jinou cestu hooků.
