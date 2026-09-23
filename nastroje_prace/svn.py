import fnmatch
import json
import os
from pathlib import Path
import urllib.parse
import xml.etree.ElementTree as ET
import zipfile

from .common import Failure, changed, clean, config, digest, git, now, read_json, require, run, safe_path, tracked, write_json
from .gate import receipt
from .policy import task_record

DENY = ('.git', '.svn', '.github', '.tasks', '.cache', '.local', 'docs', 'tests/fixtures')


def settings(root):
    value = config(root)['svn']
    require(value.get('enabled') is True, 'SVN není pro tento projekt zapnuté.')
    url = value.get('url', '').rstrip('/')
    parsed = urllib.parse.urlparse(url)
    require(parsed.scheme in ('https', 'svn', 'svn+ssh', 'file') and not parsed.username and not parsed.password,
            'SVN URL musí používat podporovaný protokol a nesmí obsahovat přihlašovací údaje.')
    require(bool(value.get('allow')) and all(isinstance(p, str) and p and '..' not in p for p in value['allow']),
            'Chybí explicitní seznam cest povolených k předání.')
    return {**value, 'url': url}


def allowed(relative, options):
    if any(relative == p or relative.startswith(p + '/') for p in DENY):
        return False
    if relative.startswith('.') or Path(relative).name in ('AGENTS.md', 'CLAUDE.md'):
        return False
    return any(fnmatch.fnmatchcase(relative, pattern) for pattern in options['allow'])


def command(*args):
    executable = os.environ.get('NASTROJE_SVN', 'svn')
    return run([executable, '--non-interactive', *args]).stdout


def xml(data):
    require(b'<!DOCTYPE' not in data.upper(), 'Nepovolená XML deklarace.')
    try:
        return ET.fromstring(data)
    except ET.ParseError:
        raise Failure('SVN nevrátilo platné XML.') from None


def remote_state(options, paths=None, revision='HEAD'):
    url = options['url']
    info = xml(command('info', '--xml', '-r', str(revision), url + '@' + str(revision)))
    entry = info.find('entry')
    require(entry is not None and entry.get('revision', '').isdigit(), 'Nelze určit revizi SVN.')
    revision = int(entry.get('revision'))
    listing = xml(command('list', '--xml', '-R', '-r', str(revision), url + '@' + str(revision)))
    existing = {e.findtext('name') for e in listing.findall('.//entry') if e.get('kind') == 'file'}
    selected = sorted(p for p in existing if allowed(p, options)) if paths is None else sorted(paths)
    files = {}
    for name in selected:
        safe_path(Path.cwd(), name)  # Validate traversal even when the path does not exist locally.
        if name not in existing:
            files[name] = None
            continue
        target = url + '/' + urllib.parse.quote(name, safe='/') + '@' + str(revision)
        content = command('cat', '-r', str(revision), target)
        files[name] = digest(content)
    return {'url': url, 'revision': revision, 'files': files}


def baseline(root, number):
    options = settings(root)
    task_record(root, number)
    clean(root)
    state = remote_state(options)
    local = {p: digest(safe_path(root, p).read_bytes()) for p in tracked(root) if allowed(p, options)}
    require(local == state['files'], 'Git kopie neodpovídá aktuálnímu SVN základu. Nejprve převzít a ověřit původní stav.')
    value = {**state, 'issue': number, 'git_commit': git(root, 'rev-parse', 'HEAD'), 'time': now(), 'settings': digest(json.dumps(options, sort_keys=True).encode())}
    path = Path(root) / '.local' / 'svn' / f'{number}-zaklad.json'
    require(not path.exists(), 'Výchozí stav již existuje; nesmí se tiše přepsat novějším stavem.')
    write_json(path, value)
    return value


def prepare(root, number):
    options = settings(root)
    proof = receipt(root)
    require(number in proof['issues'], 'Ověření neobsahuje tento úkol.')
    record = task_record(root, number)
    require(record['delivery'] == 'svn', 'Úkol není určen k předání do SVN.')
    base = read_json(Path(root) / '.local' / 'svn' / f'{number}-zaklad.json')
    require(base['url'] == options['url'] and base['settings'] == digest(json.dumps(options, sort_keys=True).encode()),
            'Nastavení SVN se od zachycení základu změnilo.')
    require(proof['base'] == base['git_commit'], 'Ověřený rozsah nezačíná zaznamenaným SVN/Git základem; použij celé SHA.')
    edits = [(status, path) for status, path in changed(root, proof['base']) if allowed(path, options)]
    require(edits, 'Není co předat z explicitně povolených cest.')
    current = remote_state(options, [p for _, p in edits])
    files = []
    for status, path in edits:
        require(current['files'][path] == base['files'].get(path), 'Kolega změnil soubor ' + path + '; nejprve sloučit a znovu ověřit.')
        target = safe_path(root, path)
        after = None if status == 'D' else digest(target.read_bytes())
        files.append({'path': path, 'action': 'delete' if status == 'D' else 'write',
                      'before': base['files'].get(path), 'after': after})
    directory = Path(root) / '.local' / 'predani' / f'{number}-{proof["commit"]}'
    require(not directory.exists(), 'Balíček již existuje; pro kontrolu použij predani-over.')
    directory.mkdir(parents=True)
    archive = directory / 'zmeny.zip'
    with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as zipped:
        for item in files:
            if item['action'] == 'write':
                zipped.write(safe_path(root, item['path']), item['path'])
    manifest = {'version': 1, 'issue': number, 'mantis': record.get('mantis'), 'commit': proof['commit'],
                'policy': proof['policy'], 'url': options['url'], 'baseline_revision': base['revision'],
                'checked_revision': current['revision'], 'files': files, 'archive_sha256': digest(archive.read_bytes()), 'time': now()}
    write_json(directory / 'manifest.json', manifest)
    return str(directory / 'manifest.json')


def verify_package(root, manifest_path):
    proof = receipt(root)
    options = settings(root)
    manifest = read_json(manifest_path)
    require(manifest['commit'] == proof['commit'] and manifest['policy'] == proof['policy'], 'Balíček patří jiné ověřené verzi.')
    require(manifest['url'] == options['url'], 'Nesedí cílové SVN.')
    require(manifest.get('files'), 'Prázdný balíček.')
    validate_files(root, manifest, proof, options)
    archive = Path(manifest_path).parent / 'zmeny.zip'
    require(digest(archive.read_bytes()) == manifest['archive_sha256'], 'Balíček byl změněn.')
    expected = {item['path'] for item in manifest['files'] if item['action'] == 'write'}
    with zipfile.ZipFile(archive) as zipped:
        require(set(zipped.namelist()) == expected and len(zipped.namelist()) == len(expected), 'Obsah archivu neodpovídá manifestu.')
        for item in manifest['files']:
            safe_path(root, item['path'])
            require(allowed(item['path'], options), 'Balíček obsahuje nepovolený soubor.')
            if item['action'] == 'write':
                data = zipped.read(item['path'])
                require(digest(data) == item['after'] == digest(safe_path(root, item['path']).read_bytes()), 'Obsah neodpovídá ověřenému commitu.')
            else:
                require(item['action'] == 'delete' and not safe_path(root, item['path']).exists(), 'Neplatné odstranění souboru.')
    current = remote_state(options, [item['path'] for item in manifest['files']])
    require(all(current['files'][i['path']] == i['before'] for i in manifest['files']),
            'SVN se od přípravy balíčku změnilo. Předání je zastaveno.')
    return manifest


def validate_files(root, manifest, proof, options):
    expected = {path: status for status, path in changed(root, proof['base']) if allowed(path, options)}
    entries = manifest.get('files', [])
    require(len(entries) == len(expected) and {i['path'] for i in entries} == set(expected),
            'Seznam balíčku neodpovídá skutečnému Git rozdílu.')
    for item in entries:
        path = safe_path(root, item['path'])
        action = 'delete' if expected[item['path']] == 'D' else 'write'
        require(item['action'] == action, 'Operace balíčku neodpovídá Git rozdílu.')
        after = None if action == 'delete' else digest(path.read_bytes())
        require(item['after'] == after, 'Otisk balíčku neodpovídá ověřenému Git obsahu.')


def record_delivery(root, manifest_path, revision):
    proof = receipt(root)
    manifest = read_json(manifest_path)
    require(proof['commit'] == manifest['commit'] and manifest['policy'] == proof['policy'], 'Předání patří jinému commitu.')
    require(isinstance(revision, int) and revision > manifest['baseline_revision'], 'Neplatná výsledná revize.')
    options = settings(root)
    require(options['url'] == manifest['url'], 'Nesedí cílové SVN.')
    validate_files(root, manifest, proof, options)
    actual = remote_state(options, [i['path'] for i in manifest['files']], revision)
    require(all(actual['files'][i['path']] == i['after'] for i in manifest['files']), 'Výsledná SVN revize neobsahuje ověřené změny.')
    value = {'issue': manifest['issue'], 'mantis': manifest['mantis'], 'commit': proof['commit'],
             'svn_url': options['url'], 'svn_revision': revision, 'time': now(), 'files': manifest['files']}
    write_json(Path(root) / '.local' / 'svn' / f'{manifest["issue"]}-predano.json', value)
    return value
