from __future__ import annotations

import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import subprocess
from datetime import datetime, timezone

TOOL_ROOT = Path(__file__).resolve().parent.parent


class Failure(Exception):
    """Očekávaný neúspěch kontroly, bez citlivých diagnostických údajů."""


def require(condition, message):
    if not condition:
        raise Failure(message)


def run(args, cwd=None, input=None, timeout=120, check=True):
    try:
        result = subprocess.run([str(a) for a in args], cwd=cwd, input=input,
                                stdout=subprocess.PIPE, stderr=subprocess.PIPE,
                                timeout=timeout, shell=False)
    except (OSError, subprocess.TimeoutExpired) as exc:
        raise Failure(f"Nelze spustit {Path(str(args[0])).name}: {type(exc).__name__}") from None
    if check and result.returncode:
        # Arbitrary command output may contain credentials; callers can store test logs locally.
        raise Failure(f"Příkaz {Path(str(args[0])).name} skončil kódem {result.returncode}.")
    return result


def git(root, *args, check=True):
    return run(['git', '-c', 'core.quotepath=false', '-C', root, *args], input=b'', check=check).stdout.decode('utf-8').strip()


def read_json(path):
    try:
        return json.loads(Path(path).read_text(encoding='utf-8-sig'))
    except (OSError, ValueError):
        raise Failure(f"Nelze načíst platný JSON: {Path(path).name}") from None


def write_json(path, data):
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')


def digest(data):
    return hashlib.sha256(data).hexdigest()


def now():
    return datetime.now(timezone.utc).isoformat()


def safe_path(root, relative):
    require(isinstance(relative, str) and relative and '\\' not in relative,
            'Cesta musí být neprázdná relativní cesta s lomítky.')
    parts = PurePosixPath(relative).parts
    require(not relative.startswith('/') and not any(x in ('..', '.git', '.svn') or ':' in x for x in parts),
            'Nepovolená cesta mimo projekt nebo do metadat.')
    root = Path(root).resolve()
    path = root.joinpath(*parts)
    current = root
    for part in parts:
        current = current / part
        require(not current.is_symlink(), 'Symbolické odkazy nejsou v předávaných cestách povoleny.')
    require(path.resolve().is_relative_to(root), 'Cesta opouští projekt.')
    return path


def config(root):
    data = read_json(Path(root) / '.prace.json')
    require(data.get('version') == 1, 'Neznámá verze .prace.json.')
    require(re.fullmatch(r'[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+', data.get('repository', '')), 'Neplatný repozitář.')
    commands = data.get('tests')
    require(isinstance(commands, list) and commands, 'Chybí povinné ověřovací příkazy.')
    require(all(isinstance(c, list) and c and all(isinstance(a, str) and a for a in c) for c in commands),
            'Testovací příkazy musí být neprázdná pole argumentů.')
    require(isinstance(data.get('svn'), dict) and isinstance(data['svn'].get('enabled'), bool), 'Chybí nastavení SVN.')
    return data


def tracked(root):
    raw = run(['git', '-C', root, 'ls-files', '-z']).stdout.decode('utf-8')
    return [p for p in raw.split('\0') if p]


def clean(root):
    require(not git(root, 'status', '--porcelain', '--untracked-files=normal'),
            'Pracovní strom není čistý. Ověření musí patřit přesnému commitu.')


def resolve_commit(root, ref):
    require(isinstance(ref, str) and ref and not ref.startswith('-'), 'Neplatný odkaz na commit.')
    value = git(root, 'rev-parse', '--verify', ref + '^{commit}')
    require(re.fullmatch('[0-9a-f]{40}', value), 'Nelze určit commit.')
    return value


def changed(root, base, head='HEAD'):
    head = resolve_commit(root, head)
    if base == 'ROOT':
        # ROOT is allowed only for the first commit of a new repository.
        require(int(git(root, 'rev-list', '--count', head)) == 1, 'ROOT je pouze pro první commit.')
        base = git(root, 'hash-object', '-t', 'tree', '--stdin')
    else:
        base = resolve_commit(root, base)
        result = run(['git', '-C', root, 'merge-base', '--is-ancestor', base, head], check=False)
        require(result.returncode == 0, 'Základ není předkem ověřované změny.')
    raw = run(['git', '-C', root, 'diff', '--no-renames', '--name-status', '-z', base, head]).stdout.decode('utf-8')
    parts = raw.split('\0')
    return [(parts[i], parts[i + 1]) for i in range(0, len(parts)-1, 2)]


def no_long_dash(text, description):
    require(not any(c in text for c in ('\u2013', '\u2014')), f'{description}: používej pouze krátké pomlčky.')


def meaningful(text, description, minimum=12):
    require(isinstance(text, str) and len(text.strip()) >= minimum, f'{description}: chybí konkrétní popis.')
    require(not re.search(r'(?i)\b(TODO|TBD|doplnit|placeholder|lorem ipsum)\b', text), f'{description}: zůstal zástupný text.')


def policy_hash(root):
    files = [TOOL_ROOT / 'upstream.lock.json', Path(root) / '.prace.json']
    files += sorted((TOOL_ROOT / 'nastroje_prace').glob('*.py'))
    return digest(b'\0'.join(p.name.encode() + b'\0' + p.read_bytes() for p in files))
