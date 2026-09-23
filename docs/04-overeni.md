# Ověření funkčnosti

## Automatická sada

Příkaz php tests/run.php používá skutečné připnuté PHP kontroly a dočasné Git
repozitáře. Jde o PHP sadu bez Composeru a dalších testovacích závislostí.
Před testy běží lint všech PHP zdrojů, testů a vstupních bodů.
Ověřují přijetí správného issue a odmítnutí syrového textu, dlouhých pomlček,
chybějících štítků, odpovědného, nesprávné struktury a příliš dlouhého textu.
Neplatný návrh nesmí ani zavolat zápis na GitHub. Testuje se také duplicita a chyba API.

Kontrola změn ověřuje pozitivní případ i odmítnutí neznámého základu,
kódu bez textové dokumentace, nesouvisejícího obrázku, neúspěšného příkazu,
špinavého stromu, změny commitu a lokálně upravených společných nástrojů.
Neúspěšné opakované ověření smaže dřívější lokální potvrzení úspěchu.

Snímky mají pozitivní dvojici a negativní případy chybějícího protějšku,
jiného čísla issue, pouhého textového odkazu a změněného souboru. Syntetické
testovací soubory nejsou důkazy změny aplikace.

README se ověřuje samostatným příkazem i uvnitř úplné kontroly. Regrese přesune
Dokumentaci za Nasazení: původní upstream tuto chybu přijme, pracovní kontrola
ji odmítne. Další testy hlídají tabulku dokumentace, duplicitní sekci, topics
a privátní viditelnost. Dokončení issue má pozitivní i negativní scénáře CI a main.

Procesní testy ověřují Unicode, zvláštní znaky v argumentech bez shellu,
nenulový návratový kód a velký výstup, který nesmí zablokovat Windows.

## Reálné SVN v izolaci

Linux CI vyžaduje svn a svnadmin. Zakládá místní jednorázový repozitář,
připraví balíček, ověří jeho obsah a skutečnou výslednou revizi. Další scénáře
provádějí změnu kolegy před přípravou i po ní, poškození ZIPu a pokus o zápis
neplatné výsledné revize. Nedotýkají se žádného firemního SVN.
Součástí jsou také přidání souboru a odstranění posledního souboru s prázdným ZIPem.

Na počítači bez SVN CLI se tyto testy hlásí jako přeskočené. Proměnná
NASTROJE_REQUIRE_SVN=1 způsobí v takovém prostředí neúspěch sady; Linux CI
ji nastavuje povinně. Windows CI ověřuje běh základních kontrol a PHP.

## Rozsah důkazů

Výsledek konkrétní verze je v GitHub Actions. Místní check ukládá návratové
kódy, otisky testovacích výstupů, commit, tree a verzi pravidel do ignorované
místní složky. Neexistuje produkční nasazení těchto nástrojů ani provedený
test proti firemním systémům. Bezpečnostní pravidla nejsou úplný secret scanner.

Oficiální podklady pro nastavení:

- [Ochrana větví](https://docs.github.com/en/rest/branches/branch-protection) - povinné výsledky kontrol.
- [Bezpečné Actions](https://docs.github.com/en/actions/reference/security/secure-use) - připnutí akcí a oprávnění.
- [Vstupy v Actions](https://docs.github.com/en/actions/concepts/security/script-injections) - obsah issues se nevkládá do shellu.
- [Stav PHP procesu](https://www.php.net/manual/en/function.proc-get-status.php) - návratové kódy na PHP 8.3 a novějším.
