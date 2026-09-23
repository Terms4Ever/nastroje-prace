import argparse
import json
import os
from pathlib import Path
import sys

from . import __version__, gate, issues, svn, upstream
from .common import Failure, TOOL_ROOT, config, git, read_json, require
from .github import GitHub
from .policy import validate_commit, validate_issue


def main():
    parser = argparse.ArgumentParser(description='Kontroly pracovních změn, issues a předání.')
    parser.add_argument('--root', type=Path, default=Path.cwd())
    sub = parser.add_subparsers(dest='command', required=True)
    p = sub.add_parser('bootstrap'); p.add_argument('--php-windows', action='store_true')
    sub.add_parser('doctor')
    sub.add_parser('install-hooks')
    p = sub.add_parser('check'); p.add_argument('--base', required=True); p.add_argument('--online', action='store_true')
    p = sub.add_parser('commit-check'); p.add_argument('file', type=Path)
    for name in ('issue-check', 'issue-create'):
        p = sub.add_parser(name); p.add_argument('file', type=Path)
    p = sub.add_parser('issue-update'); p.add_argument('number', type=int); p.add_argument('file', type=Path)
    p = sub.add_parser('issue-close'); p.add_argument('number', type=int); p.add_argument('--summary', required=True)
    p = sub.add_parser('issues-check'); p.add_argument('--number', type=int)
    p = sub.add_parser('svn-zaklad'); p.add_argument('number', type=int)
    p = sub.add_parser('predani-priprav'); p.add_argument('number', type=int)
    p = sub.add_parser('predani-over'); p.add_argument('manifest', type=Path)
    p = sub.add_parser('predani-zapis'); p.add_argument('manifest', type=Path); p.add_argument('--revision', type=int, required=True)
    args = parser.parse_args()
    root = args.root.resolve()
    try:
        cmd = args.command
        if cmd == 'bootstrap':
            result = {'upstream': upstream.bootstrap(args.php_windows)}
        elif cmd == 'doctor':
            result = {'version': __version__, 'python': sys.version.split()[0], 'git': git(root, '--version'),
                      'upstream': upstream.verify(), 'repository': config(root)['repository'],
                      'hooks': git(root, 'config', '--get', 'core.hooksPath', check=False) or 'nenainstalované'}
        elif cmd == 'install-hooks':
            current = git(root, 'config', '--local', '--get', 'core.hooksPath', check=False)
            require(not current or current == '.githooks', 'Projekt již má jiné hooky; nebudou přepsány.')
            require(root == TOOL_ROOT, 'V této verzi se hooky instalují pouze do repozitáře nastroje-prace.')
            git(root, 'config', '--local', 'core.hooksPath', '.githooks')
            git(root, 'config', '--local', 'nastrojePrace.python', sys.executable)
            result = {'hooks': '.githooks', 'scope': 'local'}
        elif cmd == 'commit-check':
            result = {'issue': validate_commit(args.file.read_text(encoding='utf-8'))}
        elif cmd == 'issue-check':
            validate_issue(root, read_json(args.file)); result = {'success': True}
        elif cmd == 'check':
            result = gate.verify(root, args.base, args.online, GitHub(config(root)['repository']) if args.online else None)
        elif cmd.startswith('issue'):
            client = GitHub(config(root)['repository'])
            if cmd == 'issue-create':
                item = issues.create(root, read_json(args.file), client)
                result = {'number': item['number'], 'url': item['html_url']}
            elif cmd == 'issue-update':
                item = issues.update(root, args.number, read_json(args.file), client)
                result = {'number': item['number'], 'url': item['html_url']}
            elif cmd == 'issue-close':
                item = issues.close(root, args.number, args.summary, client)
                result = {'number': item['number'], 'state': item['state']}
            else:
                result = {'checked': issues.audit(root, client, args.number)}
        elif cmd == 'svn-zaklad':
            result = svn.baseline(root, args.number)
        elif cmd == 'predani-priprav':
            result = {'manifest': svn.prepare(root, args.number)}
        elif cmd == 'predani-over':
            result = svn.verify_package(root, args.manifest)
        elif cmd == 'predani-zapis':
            result = svn.record_delivery(root, args.manifest, args.revision)
        print(json.dumps(result, ensure_ascii=False, indent=2))
        return 0
    except (Failure, OSError, ValueError, KeyError, TypeError) as exc:
        # Do not dump JSON inputs, subprocess environments or credentials on malformed input.
        print(str(exc) if isinstance(exc, Failure) else f'Neúspěšné ověření: {type(exc).__name__}.', file=sys.stderr)
        return 1


if __name__ == '__main__':
    raise SystemExit(main())
