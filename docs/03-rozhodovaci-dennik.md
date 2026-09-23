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

## P9 - Připnutá kopie uvnitř aplikace

Vlastník vybral kopii kontrol v projektu bez dalšího tokenu pro privátní
repozitář nástrojů. Export zaznamená zdrojový commit a otisky, připravenost
odděluje od vygenerování souborů. Aktualizace jsou vědomé a testované.
Budoucí veřejná centrální sada by umožnila i přímé stahování; soukromé
aplikační repozitáře ani požadavek na přesnou verzi by to neměnilo.

## P10 - Názvosloví a velikost README

Složky hooky a sablony odpovídají českému názvosloví původních nástrojů.
Zde hooky obsahují lokální Git commit-msg a pre-push; původní hooky obsahují
i hooky pro agenta, nejde o totožnou funkci. Globální Git se nemění.
README drží rozcestník dokumentace, záznamy jednotlivých úkolů jsou ve složce
docs/ukoly. Odznaky PHP a licence mají stejný vzhled jako nastroje; závislost
na nastroje je uvedena pravdivě místo tvrzení o žádných závislostech.

## P11 - Pouze main pro jednoho agenta

Vlastník zvolil od verze 0.4 přímou práci na main. Nahrazuje tím předchozí
postup s dočasnými větvemi a následným úklidem z úkolu 4. Jeden agent dokončí
ověření a případné předání úkolu, teprve potom začne další. Hooky odmítají
commit mimo main a push jiné reference. Testovací větve jsou pouze v izolovaných testech.

GitHub CI se spouští až po pushi; jeho předchozí úspěch proto nemůže být
podmínkou přijetí nového commitu. Zákaz přepsání, smazání a nelineární historie
zůstává i pro správce. Místní online doklad a poslední úspěšné CI přesného
aktuálního main vyžadují všechny tři kroky SVN předání, uzavření issue a
dokončení zavedení. Starý úspěšný běh nenahrazuje nový čekající či neúspěšný běh.

## P12 - Výslovná rozhodnutí vlastníka mají přednost (23. 9. 2026)

Vlastník po porovnání původních nastroje schválil odstranění celé ochrany main
a denního auditu issues. Nahrazuje tím ochranu popsanou v P11. Pouze main,
místní hooky a úspěšné CI před dokončením a SVN předáním zůstávají. Nové
projekty ochranu nezakládají; project-check ji nevyžaduje a nemění.

Otevřený stručný nápad vlastníka smí zůstat bez šablony a zařazení. Agent před
převzetím úkolu zajistí plný tvar; vlastní založení, aktualizace a dokončení
agenta zůstávají přísné. Audit rozlišuje autora, otevřený stav a nepřevzetí
do .tasks. GitHub s jedním osobním účtem neumí doložit, kdo fyzicky text zadal.

Obnovuje se dokumentační výjimka N10: pomlčka citovaná v řádkovém kódu
Markdownu je přípustná, v běžném textu a bloku kódu se dál hlásí. Původní
commitové a issue kontroly se tímto bodem nerozvolňují.

Nové pravidlo nebo zpřísnění potřebuje předchozí výslovné rozhodnutí vlastníka.
Samotný úspěch testů ani souhlas se založením nástrojů takovým rozhodnutím není.
