# Napojení pracovních pravidel

Přesný původ kontrol je v nastroje-prace.lock.json. Založení a připojení se řídí
dokumentem docs/05-novy-pracovni-projekt.md v repozitáři nastroje-prace.

## Dokončení zavedení

- [ ] GitHub {{REPOSITORY}} je privátní, má popis, topics, zapnuté issues a hlavní větev main.
- [ ] .pravidla.json, odznak README, AGENTS.md, topic a Actions shodně označují nastroje-prace; rules-check a metadata-check prošly.
- [ ] Jsou založené štítky bug, enhancement, documentation, rozhrani a bez-rozhrani.
- [ ] Existuje pouze main; pracuje vždy jeden agent bez pracovních větví a pull requestů.
- [ ] Původ zdrojů a případná SVN revize jsou zdokumentované; zdrojový strom neobsahuje cizí metadata ani provozní data.
- [ ] První skutečné issue má odpovědného, metadata .tasks a textový záznam v docs/ukoly.
- [ ] Příkazy v .prace.json ověřují aplikaci a odpovídající prostředí je dostupné v obou CI úlohách.
- [ ] Bootstrap, místní hooky, README, dokumentace a project-check prošly v čisté nové kopii.
- [ ] Lokální check --online a GitHub Povinne kontroly prošly pro stejné SHA.
- [ ] Main je výchozí větev s přímým pushem; nová ochrana ani ruleset se nezakládají.
- [ ] Project-check --online prošel a zaváděcí issue má odkazy na důkazy.

Doklady patří k zaváděcímu issue a jeho textovému záznamu. Neodškrtávej
neprovedené kroky. Před první aplikační změnou zkontroluj aktuální SVN základ.

## Příkazy

```text
php prace.php bootstrap
php prace.php install-hooks
php prace.php doctor
php prace.php rules-check
php prace.php project-check
php prace.php check --base VYCHOZI_SHA --online
php prace.php project-check --online
```

Na Windows lze místo php prace.php použít .\prace.ps1.
První commit používá ROOT; po něm použij skutečné výchozí SHA, pro SVN celé SHA základu.
Po přímém pushi počkej na úspěšné CI stejného main před předáním a dokončením.
Pravidla, spouštěče a workflow se posuzují při aktualizaci připnuté verze.
