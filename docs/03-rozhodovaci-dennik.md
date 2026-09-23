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
