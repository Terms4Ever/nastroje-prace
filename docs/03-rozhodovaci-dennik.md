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

## P13 - Výjimky snímků schvaluje vlastník (23. 9. 2026)

Vlastník rozhodl: „Vyjimky schvaluji já“. Výjimka je přípustná pouze při
skutečné nemožnosti nad konkrétní překážkou, provedeným pokusem a dostupným
náhradním důkazem. Agent každý případ předloží a čeká na výslovný souhlas.
Toto rozhodnutí doplňuje P4; samo nepovoluje vynechat snímky konkrétního úkolu.

Metadata určují přesné chybějící cesty. Komentář vlastníka se váže otiskem
na tyto cesty a obsah podkladů; dokončení znovu ověří živý souhlas. Schválení
v chatu smí agent zaznamenat až po skutečném rozhodnutí, s citací a kontextem.
GitHub prokáže účet, ne fyzického autora za stejnými přihlašovacími údaji.
Testy neprokazují nemožnost pořízení ani pravdivost zaznamenaného souhlasu.

## P14 - Dokumentace v README má společný viditelný formát (23. 9. 2026)

Vlastník snímkem doložil rozdíl proti nastroje a zadal sjednocení. Tabulka
proto používá skutečné cesty v řádkovém kódu, společné popisy stavu a deníku
a stejnou větu o platném stavu pod tabulkou. Pořadí sekcí ani obecná kontrola
odkazů tuto odlišnost dosud nezachytily; readme-check nyní ověřuje i tento tvar.
Seznam ostatních dokumentů patří konkrétnímu projektu, úkoly zůstávají složkou.
Tento požadavek nahrazuje pojmenované odkazy zavedené při předchozí opravě README.

## P15 - Veřejné obecné nástroje, privátní aplikace (24. 9. 2026)

Vlastník výslovně zadal zveřejnění nastroje-prace s tím, že důležitý pracovní
obsah bude přímo v projektech. Toto rozhodnutí nahrazuje privátní centrální
sadu z P1 a P8. Zdrojové kódy aplikací, interní konfigurace, zadání z Mantis
a provozní snímky zůstávají v příslušných privátních aplikačních repozitářích.
Licence zůstává proprietární, způsob distribuce připnutou kopií se nemění.

Výjimka viditelnosti se pozná podle identity a skutečného kořene centrálních
nástrojů; shodný název v aplikační konfiguraci ji nezapne. Centrální sada
připouští i privátní stav během přípravy zveřejnění, takže lze nejprve ověřit
a publikovat kompatibilní kontroly a až poté změnit viditelnost GitHubu.

Veřejný podnět návštěvníka bez převzetí do .tasks čeká na posouzení. Cizí
komentář nemusí dodržovat formát agenta. Převzatý úkol, příspěvky správce
a spolupracovníků i všechny zápisové vstupy agenta zůstávají kontrolované.
Veřejná výjimka neplatí pro aplikace a nenahrazuje souhlas vlastníka s výjimkou snímků.

Před zveřejněním se prověří dostupná historie, issues, přílohy a protokoly.
Historické záznamy rozhodnutí se nepřepisují; dřívější údaje o privátní sadě
popisují tehdejší stav. Aktualizované návody používají obecné názvy projektů.

## P16 - Jediná viditelná hlavní sada pravidel (24. 9. 2026)

Vlastník schválil rozlišení nastroje a nastroje-prace souborem .pravidla.json,
odznakem README, úvodem AGENTS.md, topic a názvem Actions. Projekt vybírá
právě jednu sadu; chybějící, neznámé nebo rozporné označení zastaví kontrolu.
Kontrola ověřuje také skutečné zapojení workflow, samotné přejmenování nestačí.

Pracovní adaptér používá společný PHP validátor z ověřeného upstream commitu.
Nastroje v upstream.lock.json zůstávají závislostí pracovní sady a neznamenají
současně osobní pracovní postup. Místní rules-check, plný check a napojení
projektu ověřují soubory; metadata-check přidává živé GitHub topics.
Project-init pracovní topic doplní a rozpor odmítne před zápisem do cíle.

Verzovaná kopie pracovních kontrol ani osobní workflow na main se tímto
rozhodnutím nemění. Aktuální změna nepřipojuje pracovní aplikace, nezveřejňuje
jejich obsah, nezavádí ochranu main, denní plán ani pracovní větve.
