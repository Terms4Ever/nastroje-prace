# Rozhodovací deník

## P1 - Osobní privátní Git a společné SVN

Git eviduje osobní zpracování úkolů. Kolegům zůstává obvyklý postup v SVN.
Oba pracovní stromy jsou oddělené a osobní metadata se do SVN nepředávají.

## P2 - Připnutá kopie pravidel

Projekt obsahuje ověřenou kopii konkrétního commitu nastroje-prace. CI kvůli
pravidlům nepotřebuje přístupový token k jinému privátnímu repozitáři.
Aktualizace pravidel je samostatný úkol s kontrolou rozdílu a novým ověřením.

## P3 - Jeden agent a jediná větev main

Práce probíhá přímo na main bez pracovních větví a pull requestů. Místní
kontroly předcházejí pushi, GitHub CI jej následuje. Předání a dokončení čeká
na úspěšné ověření stejného aktuálního main. Historii chrání zákaz přepsání a smazání.
