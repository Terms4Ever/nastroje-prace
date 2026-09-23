import json
from pathlib import Path
import re
import sys

from . import upstream
from .common import TOOL_ROOT, changed, clean, config, digest, git, now, policy_hash, read_json, require, resolve_commit, run, write_json
from .policy import repository_content, task_record, validate_commit, validate_issue


def receipt_path(root, sha):
    return Path(root) / '.local' / 'overeni' / (sha + '.json')


def receipt(root):
    clean(root)
    sha = resolve_commit(root, 'HEAD')
    value = read_json(receipt_path(root, sha))
    require(value.get('commit') == sha and value.get('tree') == git(root, 'rev-parse', 'HEAD^{tree}'), 'Ověření patří jiné verzi.')
    require(value.get('policy') == policy_hash(root), 'Pravidla se po ověření změnila.')
    require(value.get('success') is True and value.get('tests'), 'Chybí úspěšné ověření.')
    upstream.verify()
    return value


def verify(root, base, online=False, client=None):
    root = Path(root).resolve()
    settings = config(root)
    clean(root)
    sha = resolve_commit(root, 'HEAD')
    destination = receipt_path(root, sha)
    if destination.exists():
        destination.unlink()  # Never retain an earlier success when a repeated check fails.
    policy = policy_hash(root)
    upstream.verify()
    repository_content(root)
    files = changed(root, base, sha)
    require(files, 'Rozsah neobsahuje žádnou změnu.')
    commits = git(root, 'rev-list', '--reverse', sha if base == 'ROOT' else resolve_commit(root, base) + '..' + sha).splitlines()
    issues = set()
    for commit in commits:
        issues.add(validate_commit(git(root, 'show', '-s', '--format=%B', commit)))
    records = {n: task_record(root, n) for n in issues}
    text_changes = {p for status, p in files if status != 'D' and re.fullmatch(r'docs/ukoly/\d+\.md', p)}
    require(any(f'docs/ukoly/{n}.md' in text_changes for n in issues), 'Změna nemá aktualizovaný textový záznam příslušného úkolu.')
    for script in ('kontrola-readme.php', 'kontrola-dokumentace.php'):
        upstream.check(script, [str(root)])
    if online:
        require(client is not None, 'Chybí klient GitHubu.')
        for number, record in records.items():
            issue = client.issue(number)
            validate_issue(root, issue)
            labels = {x['name'] for x in issue['labels']}
            require(('rozhrani' in labels) == record['visual'], 'Issue a záznam nesouhlasí o změně rozhraní.')
    tests = []
    for index, command in enumerate(settings['tests']):
        args = [sys.executable if x == '{python}' else x for x in command]
        result = run(args, cwd=root, timeout=600, check=False)
        log = Path(root) / '.local' / 'overeni' / (sha + f'-test-{index}.log')
        log.parent.mkdir(parents=True, exist_ok=True)
        log.write_bytes(result.stdout + result.stderr)
        print((result.stdout + result.stderr).decode('utf-8', errors='replace'))
        require(result.returncode == 0, f'Povinné ověření {index + 1} selhalo; viz místní protokol.')
        tests.append({'command': command, 'exit': result.returncode, 'log_sha256': digest(log.read_bytes())})
    clean(root)
    require(resolve_commit(root, 'HEAD') == sha and policy_hash(root) == policy, 'Obsah nebo pravidla se změnily během testů.')
    result = {'version': 1, 'success': True, 'commit': sha, 'tree': git(root, 'rev-parse', 'HEAD^{tree}'),
              'policy': policy, 'upstream': upstream.verify(), 'base': base, 'issues': sorted(issues),
              'online': online, 'tests': tests, 'time': now()}
    write_json(destination, result)
    return result
