from pathlib import Path, PurePosixPath
import re
import urllib.parse

from . import upstream
from .common import Failure, config, digest, git, meaningful, no_long_dash, read_json, require, run, safe_path, tracked

KINDS = {'bug', 'enhancement', 'documentation'}
VISUAL = {'rozhrani', 'bez-rozhrani'}


def names(values):
    return [v['name'] if isinstance(v, dict) else v for v in values]


def sections(body):
    matches = list(re.finditer(r'^## (.+)$', body, re.M))
    return {m.group(1): body[m.end():matches[i + 1].start() if i + 1 < len(matches) else len(body)].strip()
            for i, m in enumerate(matches)}


def validate_issue(root, item, closed=None):
    title, body = item.get('title', ''), item.get('body', '') or ''
    labels = names(item.get('labels', []))
    assignees = [a['login'] if isinstance(a, dict) else a for a in item.get('assignees', [])]
    closed = item.get('state') == 'closed' if closed is None else closed
    meaningful(title, 'Název issue', 15)
    require(len(title) <= 100 and '\n' not in title, 'Název issue musí mít jeden řádek do 100 znaků.')
    no_long_dash(title, 'Název issue')
    require(not re.fullmatch(r'(?i)(opravy|úpravy|aktualizace|fix|update)( systému| projektu)?', title.strip()),
            'Název issue je příliš obecný.')
    require(len(set(labels) & VISUAL) == 1, 'Issue musí mít právě jeden štítek rozhrani / bez-rozhrani.')
    args = ['-', '--prisne', '--titulek', title, '--stitky', ','.join(labels), '--odpovedni', ','.join(assignees)]
    if closed:
        args += ['--zavrene']
    upstream.check('kontrola-tvaru-issue.php', args, input=body.encode())
    parts = sections(body)
    meaningful(parts.get('Problém', parts.get('Cíl', '')), 'Zadání issue')
    for task in re.findall(r'^\s*- \[[ xX]\]\s*(.*)$', body, re.M):
        meaningful(task, 'Podmínka dokončení')
    if closed:
        require(bool(item.get('number')), 'Uzavřené issue musí mít číslo.')
        pictures(root, item['number'], body, 'rozhrani' in labels, config(root)['repository'])
    return True


def pictures(root, number, body, visual, repository):
    images = re.findall(r'!\[[^\]]*\]\(([^\s)]+)\)', body)
    before, after = {}, {}
    for url in images:
        parsed = urllib.parse.urlparse(url)
        require(parsed.scheme == 'https' and parsed.netloc == 'github.com', 'Snímky musí být uložené v tomto GitHub repozitáři.')
        pattern = '/' + re.escape(repository) + r'/blob/([0-9a-f]{40})/(docs/snimky/' + str(number) + r'-[^/]+/(pred|po)-([^/]+))'
        match = re.fullmatch(pattern, urllib.parse.unquote(parsed.path))
        require(match is not None, 'Snímek musí mít správné issue, cestu a neměnný Git commit.')
        sha, path, kind, suffix = match.groups()
        file = safe_path(root, path)
        require(file.is_file(), 'Snímek z issue v projektu neexistuje.')
        data = file.read_bytes()
        valid = data.startswith(b'\x89PNG\r\n\x1a\n') or data.startswith(b'\xff\xd8\xff') or (data[:4] == b'RIFF' and data[8:12] == b'WEBP')
        require(valid, 'Soubor snímku není rozpoznaný PNG/JPEG/WebP.')
        blob = run(['git', '-C', root, 'show', sha + ':' + path]).stdout
        require(blob == data, 'Odkaz na snímek neodpovídá souboru v ověřované verzi.')
        (before if kind == 'pred' else after)[suffix] = digest(data)
    if visual:
        require(bool(before) and set(before) == set(after), 'Chybí úplné dvojice snímků před a po.')
        require(all(before[k] != after[k] for k in before), 'Snímek před a po nesmí být totožný soubor.')


def validate_comment(text):
    meaningful(text, 'Komentář', 5)
    # Upstream counts image-only lines too; this profile uses its stricter existing rule.
    upstream.check('kontrola-tvaru-issue.php', ['-', '--komentar'], input=text.encode())


def validate_commit(message):
    lines = message.strip().splitlines()
    require(bool(lines), 'Prázdná zpráva commitu.')
    no_long_dash(message, 'Commit')
    require(re.fullmatch(r'[^\n]{15,100} \(#\d+\)', lines[0]),
            'Nadpis commitu má být konkrétní česká věta zakončená (#číslo).')
    require(not re.match(r'(?i)(fix|feat|chore|update|opravy|úpravy)[:! ]', lines[0]), 'Commit má popisovat konkrétní výsledný stav.')
    for section in ('Důvod', 'Ověření'):
        match = re.search(r'^' + section + r':\s*(.+)$', message, re.M)
        require(match is not None, f'Commit nemá řádek {section}:')
        meaningful(match.group(1), 'Commit ' + section)
    return int(re.search(r'\(#(\d+)\)$', lines[0]).group(1))


def task_record(root, number):
    meta = read_json(Path(root) / '.tasks' / f'{number}.json')
    require(meta.get('issue') == number, 'Nesedí číslo v záznamu úkolu.')
    mantis = meta.get('mantis')
    if mantis is None:
        meaningful(meta.get('technical_reason', ''), 'Důvod technického úkolu bez Mantis')
    else:
        require(isinstance(mantis, int) and not isinstance(mantis, bool) and mantis > 0, 'Neplatné Mantis ID.')
    require(isinstance(meta.get('visual'), bool), 'Záznam musí určit, zda mění rozhraní.')
    require(meta.get('delivery') in ('git', 'svn'), 'Neznámý způsob předání.')
    path = Path(root) / 'docs' / 'ukoly' / f'{number}.md'
    require(path.is_file(), 'Chybí textový záznam úkolu.')
    text = path.read_text(encoding='utf-8')
    no_long_dash(text, 'Záznam úkolu')
    parts = sections(text)
    for section in ('Zadání', 'Změna', 'Ověření', 'Předání'):
        meaningful(parts.get(section, ''), 'Záznam ' + section)
    return meta


def repository_content(root):
    require((Path(root) / 'docs').is_dir(), 'Povinná složka docs/ neexistuje.')
    require((Path(root) / 'AGENTS.md').is_file(), 'Chybí AGENTS.md.')
    require((Path(root) / 'CLAUDE.md').read_text(encoding='utf-8').strip() == '@AGENTS.md', 'CLAUDE.md musí odkazovat na AGENTS.md.')
    for file in tracked(root):
        path = safe_path(root, file)
        require(path.is_file(), 'Verzovaný soubor neexistuje: ' + file)
        parts = set(PurePosixPath(file).parts)
        forbidden = {'.env', '.local', '.cache', 'node_modules', 'asc-profile', '__pycache__'}
        require(not parts & forbidden and not file.endswith(('.sqlite', '.sqlite3', '.db', '.dump', '.pfx', '.p12', '.local.json')),
                'Nechtěný nebo lokální soubor v Gitu: ' + file)
        require(path.stat().st_size <= 10 * 1024 * 1024, 'Soubor přesahuje limit 10 MiB: ' + file)
        data = path.read_bytes()
        if b'\0' in data:
            continue
        try:
            text = data.decode('utf-8')
        except UnicodeDecodeError:
            raise Failure('Text není UTF-8: ' + file) from None
        if file.endswith('.md'):
            no_long_dash(text, file)
        patterns = [r'gh[pousr]_[A-Za-z0-9]{30,}', r'github_pat_[A-Za-z0-9_]{40,}',
                    r'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----',
                    r'(?im)^\s*(?:password|passwd|api_key|secret|token)\s*[:=]\s*[\"\']?(?!\$|<|example|dummy|test|\{)[A-Za-z0-9/+_=.-]{12,}']
        require(not any(re.search(p, text) for p in patterns), 'Podezření na tajný údaj v ' + file + '; hodnota se nevypisuje.')
