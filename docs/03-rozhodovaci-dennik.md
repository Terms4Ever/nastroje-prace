# Rozhodovací deník

## P1 - Samostatná privátní nadstavba

Pracovní postup a pozdější interní nastavení patří do samostatného repozitáře.
Obecné validátory zůstávají v nastroje. Instalace nemění jiné projekty.

## P2 - Pevná verze společných pravidel

Lock soubor určuje přesné SHA, bootstrap kontroluje čistotu vlastní cache.
Nevyužívá se pohyblivé sdílené workflow. Změna společného main nezmění pravidla
již nainstalované pracovní sady.

## P3 - Povinné ověření se nesmí tiše přeskočit

Neznámý základ, chybějící docs, nedostupné API, nesprávný runtime a selhání
testu končí chybou. Pracovní sada doplňuje tyto kontroly bez oprav upstreamu.
Samotný obrázek neplní požadavek na textový záznam změny.

## P4 - Jednotný zápis issues a dokončení s důkazy

Přebírá se stávající český tvar a limity textu. Nově je výslovně určeno, zda
jde o změnu rozhraní. Takové dokončení vyžaduje skutečné vložené dvojice
snímků. Stroj kontroluje soubory a vazby; pravdivost obsahu zůstává součástí review.

## P5 - Commit v nové sadě

Globální pravidla původního počítače nejsou dostupná. Tato sada proto
výslovně zavádí konkrétní český nadpis s (#issue), Důvod: a Ověření:.
Netvrdí úplnou shodu s nedostupným globálním commit hookem.

## P6 - SVN bez skrytých zápisů

Přenos má explicitní seznam souborů, kontrolní otisky a záznam výsledné revize.
Nástroj nevykonává SVN commit a neslibuje zámek mezi kontrolou a předáním.
Samostatné zavedení do projektu musí prověřit jeho vlastnosti, externals a build.

## P7 - Jediný jazyk kontrol

Na žádost vlastníka používá od verze 0.2 celé jádro, testy i CI PHP stejně jako
společné nastroje. Python byl z aktivní implementace odstraněn. PowerShell pouze
spouští PHP a instaluje připnutý místní runtime. Nevzniká druhá sada pravidel
ani závislost na runtime konkrétního asistenta.

## P8 - README a GitHub metadata mají ověřitelnou podobu

Dokumentace patří za Strukturu projektu a před Instalaci, stejně jako ve
společných nastroje. Původní validátor tuto pozici nehlídal; doplňuje ji místní
kontrola a regrese s nesprávně přesunutou sekcí. Sdílený repozitář se nemění.
Topics určuje .prace.json a online kontroly ověřují jejich přítomnost i privátní
viditelnost. Nastavení není doloženo pouze textovým tvrzením v README.
