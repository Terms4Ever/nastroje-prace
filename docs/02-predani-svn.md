# Kontrolované předání do SVN

## Rozsah

Nástroje pouze čtou SVN, vytvářejí místní ZIP a ověřují výslednou revizi.
Nemění pracovní kopie kolegů, nenahrávají soubory a samy neprovádějí commit.
První instalace tohoto repozitáře proto nemění W4SN ani DOMIQ.

## Nastavení projektu

Projekt potřebuje vlastní .prace.json, metadata úkolu, dokumentaci a testovací
příkazy. Parametr --root musí být před názvem příkazu. Nástroj spouštěj ze svého
kořene, cíl určuj absolutní cestou. Pro SVN doplň enabled=true, URL bez hesel a
explicitní allow seznam, například src/**. Osobní metadata, docs, hooky a skryté
cesty se nepředávají ani při příliš širokém allow. V repozitáři nástrojů je SVN vypnuté.

Výchozí stav zachyť po převzetí aktuálního SVN a před vlastní změnou. Příkaz
svn-zaklad CISLO vyžaduje čistou Git kopii a ověří shodu obsahu povolených
souborů se SVN. Uloží SVN revizi, celé Git SHA a otisky. Již existující základ
nepřepisuje. Následující check musí používat právě toto celé Git SHA jako základ.

## Předání

1. Dokonči změnu, textový záznam a commit.
2. Proveď check --base VYCHOZI_GIT_SHA --online; musí projít povinné příkazy.
3. Proveď predani-priprav CISLO. Nástroj porovná dotčené SVN soubory se základem.
4. Prohlédni seznam změn v manifestu a připravený ZIP. Osobní dokumentace není v balíčku.
5. Bezprostředně před předáním proveď predani-over CESTA_K_MANIFESTU.
6. V řádné SVN pracovní kopii načti aktuální stav, aplikuj povolené změny a zkontroluj diff. Odstranění jsou výslovně v manifestu, nikoli v ZIPu.
7. Proveď obvyklý SVN commit. Při konfliktu či odmítnutí nejprve sluč změny a opakuj ověření.
8. Proveď predani-zapis CESTA_K_MANIFESTU --revision SKUTECNA_REVIZE. Obsah v této revizi se musí shodovat s ověřenou změnou.

Výsledný záznam propojí Git commit, GitHub issue, případné Mantis ID a SVN
revizi. Dokud ověření revize neprojde, předání se nezapíše jako úspěšné.

## Co kontrola nezaručí

Mezi posledním čtením SVN a commitem může vzniknout další změna. Místní čtení
není zámek serveru; konečnou ochranu poskytuje správná pracovní kopie a SVN
kontrola zastaralé revize při commitu. Nikdy nekopíruj starý celý strom přes
novější kopii bez sloučení. Automatický obousměrný synchronizační nástroj se
nepoužívá.

Verze 0.1 porovnává obsah souborů. SVN vlastnosti, externals, symlinky a
změny oprávnění nejsou podporovanou součástí automatického předání. Tyto
případy vyžadují samostatný postup a jeho ověření před zapojením projektu.
