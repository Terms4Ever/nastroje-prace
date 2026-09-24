# Kontrolované předání do SVN

## Rozsah

Nástroje pouze čtou SVN, vytvářejí místní ZIP a ověřují výslednou revizi.
Nemění pracovní kopie kolegů, nenahrávají soubory a samy neprovádějí commit.
První instalace tohoto repozitáře proto nemění žádnou pracovní aplikaci.

## Nastavení projektu

Projekt potřebuje vlastní .prace.json, metadata úkolu, dokumentaci a testovací
příkazy. Založení a připnutí kontrol popisuje docs/05-novy-pracovni-projekt.md.
Používej prace.php nebo prace.ps1 přímo z kořene připojené aplikace. Pro SVN doplň enabled=true, URL bez hesel a
explicitní allow seznam, například src/**. Osobní metadata, docs, hooky a skryté
cesty se nepředávají ani při příliš širokém allow. V repozitáři nástrojů je SVN vypnuté.

Výchozí stav zachyť po převzetí aktuálního SVN a před vlastní změnou. Příkaz
svn-zaklad CISLO vyžaduje čistou Git kopii a ověří shodu obsahu povolených
souborů se SVN. Uloží SVN revizi, celé Git SHA a otisky. Již existující základ
nepřepisuje. Následující check musí používat právě toto celé Git SHA jako základ.

## Předání

1. Dokonči změnu, textový záznam a commit.
2. Proveď git push origin main s místními hooky a počkej na úspěšné GitHub CI tohoto SHA.
3. Proveď check --base VYCHOZI_GIT_SHA --online, pokud doklad z hooku používá jiný základ než celé SHA uloženého SVN základu. Potom proveď predani-priprav CISLO; porovná dotčené SVN soubory se základem.
4. Prohlédni seznam změn v manifestu a připravený ZIP. Osobní dokumentace není v balíčku.
5. Bezprostředně před předáním proveď predani-over CESTA_K_MANIFESTU.
6. V řádné SVN pracovní kopii načti aktuální stav, aplikuj povolené změny a zkontroluj diff. Odstranění jsou výslovně v manifestu, nikoli v ZIPu.
7. Proveď obvyklý SVN commit. Při konfliktu či odmítnutí nejprve sluč změny a opakuj ověření.
8. Proveď predani-zapis CESTA_K_MANIFESTU --revision SKUTECNA_REVIZE. Obsah v této revizi se musí shodovat s ověřenou změnou.

Výsledný záznam propojí Git commit, GitHub issue, případné Mantis ID a SVN
revizi. Dokud ověření revize neprojde, předání se nezapíše jako úspěšné.
Příprava, kontrola i zápis předání pokaždé čtou GitHub. Vyžadují místní online
doklad a poslední úspěšný běh kontroly.yml se souhrnem Povinne kontroly pro
stejný aktuální main. Offline doklad, cizí SHA, čekající či neúspěšné CI a chyba
API předání zastaví ještě před vytvořením balíčku nebo zápisem úspěchu.
Přístup musí umožnit čtení Actions. Dokonči předání před dalším úkolem na main.

## Co kontrola nezaručí

Mezi posledním čtením SVN a commitem může vzniknout další změna. Místní čtení
není zámek serveru; konečnou ochranu poskytuje správná pracovní kopie a SVN
kontrola zastaralé revize při commitu. Nikdy nekopíruj starý celý strom přes
novější kopii bez sloučení. Automatický obousměrný synchronizační nástroj se
nepoužívá.
Nástroj nedokáže zabránit ručnímu SVN commitu mimo tento postup.

Současná verze porovnává obsah souborů. SVN vlastnosti, externals, symlinky a
změny oprávnění nejsou podporovanou součástí automatického předání. Tyto
případy vyžadují samostatný postup a jeho ověření před zapojením projektu.
