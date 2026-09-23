import json
from pathlib import Path
import tempfile

from nastroje_prace import upstream
from nastroje_prace.common import git, run, write_json

MESSAGE = 'Kontrola chrání předání ověřené změny (#1)\n\nDůvod: Zachovat dohledatelnost konkrétního úkolu.\nOvěření: Prošla izolovaná sada kontrolních scénářů.\n'
BODY = '## Cíl\n\nPředání změny vyžaduje doložené ověření a záznam úkolu.\n\n## Hotovo, když\n\n- [ ] Neověřená změna se nepředá do dalšího kroku.\n'


def draft():
    return {'title': 'Předání změny vyžaduje doložené ověření', 'body': BODY,
            'labels': ['enhancement', 'bez-rozhrani'], 'assignees': ['Terms4Ever']}


def commit(root, message=MESSAGE):
    git(root, 'add', '.')
    run(['git', '-C', root, '-c', 'core.hooksPath=', 'commit', '-m', message])
    return git(root, 'rev-parse', 'HEAD')


def fixture():
    temp = tempfile.TemporaryDirectory(prefix='nastroje-prace-test-', ignore_cleanup_errors=True)
    root = Path(temp.name)
    git(root, 'init', '-b', 'main')
    git(root, 'config', 'user.name', 'Zkouska')
    git(root, 'config', 'user.email', 'test@example.invalid')
    git(root, 'config', 'core.autocrlf', 'false')
    (root / '.gitignore').write_text('.local/\n.cache/\n', encoding='utf-8')
    (root / 'AGENTS.md').write_text('# Pravidla\n\nZměny vyžadují doklad ověření.\n', encoding='utf-8')
    (root / 'CLAUDE.md').write_text('@AGENTS.md\n', encoding='utf-8')
    write_json(root / '.prace.json', {'version': 1, 'repository': 'Terms4Ever/nastroje-prace',
        'tests': [['{python}', '-c', 'print("Overeno")']], 'svn': {'enabled': False, 'url': '', 'allow': []}})
    write_json(root / '.readme-kontrola.json', {'profil': 'plny', 'docs-kontrola': True, 'docs-pomlcky': 'blokovat'})
    write_json(root / '.tasks/1.json', {'issue': 1, 'mantis': None, 'technical_reason': 'Izolované ověření společných kontrol.', 'visual': False, 'delivery': 'git'})
    (root / 'docs/ukoly').mkdir(parents=True)
    for name in ('00-stav-projektu.md', '03-rozhodovaci-dennik.md'):
        (root / 'docs' / name).write_text('# Přehled projektu\n\nProjekt slouží k izolovanému ověření.\n', encoding='utf-8')
    (root / 'docs/ukoly/1.md').write_text('# Úkol 1\n\n' + '\n\n'.join('## ' + h + '\n\nKonkrétní ověřený popis dané části úkolu.' for h in ('Zadání','Změna','Ověření','Předání')) + '\n', encoding='utf-8')
    header = '# 🧰 Zkušební projekt\n\n**Izolované ověření pravidel**\n\nProjekt pro kontrolní scénáře.\n\n![Test](https://img.shields.io/badge/test-local-blue)\n\n---\n\n'
    headings = ['✨ Hlavní funkce','🛠️ Tech Stack','📁 Struktura projektu','🚀 Instalace (lokální vývoj)','📦 Nasazení','📄 Licence']
    readme = header + '\n\n'.join('## ' + h + '\n\nIzolované ověření bez produkčních služeb.' for h in headings)
    readme += '\n\n## 📚 Dokumentace\n\n| Dokument | Účel |\n|---|---|\n| `docs/00-stav-projektu.md` | Současný stav. |\n| `docs/03-rozhodovaci-dennik.md` | Důvody řešení. |\n| `docs/ukoly/1.md` | Záznam ověření. |\n'
    (root / 'README.md').write_text(readme, encoding='utf-8')
    (root / 'src').mkdir()
    (root / 'src/main.txt').write_text('původní obsah\n', encoding='utf-8')
    # The generator needs a born branch to determine the main branch consistently.
    commit(root)
    upstream.check('stav-projektu.php', [str(root), '--zapsat'])
    commit(root)
    return temp, root
