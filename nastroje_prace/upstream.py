import os
from pathlib import Path
import shutil
import urllib.request
import zipfile

from .common import TOOL_ROOT, Failure, digest, git, read_json, require, run


def cache():
    return TOOL_ROOT / '.cache' / 'nastroje'


def php():
    candidates = [os.environ.get('NASTROJE_PHP'), str(TOOL_ROOT / '.cache' / 'php' / 'php.exe'),
                  shutil.which('php')]
    for candidate in candidates:
        if candidate and Path(candidate).is_file():
            return candidate
    raise Failure('Chybí PHP 8.3+ s mbstring. Nastav NASTROJE_PHP nebo spusť bootstrap --php-windows.')


def verify():
    lock = read_json(TOOL_ROOT / 'upstream.lock.json')
    require(cache().is_dir(), 'Chybí připnuté nástroje. Spusť bootstrap.')
    require(git(cache(), 'rev-parse', 'HEAD') == lock['commit'], 'Nesedí verze společných nástrojů.')
    require(not git(cache(), 'status', '--porcelain'), 'Společné nástroje byly lokálně změněny.')
    run([php(), '-r', 'exit(PHP_VERSION_ID >= 80300 && extension_loaded("mbstring") ? 0 : 1);'])
    return lock['commit']


def bootstrap(install_php=False):
    lock = read_json(TOOL_ROOT / 'upstream.lock.json')
    if not cache().exists():
        cache().parent.mkdir(parents=True, exist_ok=True)
        run(['git', 'clone', '--no-checkout', lock['repository'], cache()])
        run(['git', '-C', cache(), 'checkout', '--detach', lock['commit']])
    if install_php:
        require(os.name == 'nt', 'Stažení PHP je určeno pouze pro Windows.')
        folder = TOOL_ROOT / '.cache' / 'php'
        if not (folder / 'php.exe').exists():
            with urllib.request.urlopen(lock['php_windows_url'], timeout=120) as response:
                data = response.read()
            require(digest(data) == lock['php_windows_sha256'], 'Stažené PHP nemá očekávaný SHA-256.')
            import io
            folder.mkdir(parents=True, exist_ok=True)
            with zipfile.ZipFile(io.BytesIO(data)) as archive:
                for member in archive.infolist():
                    target = (folder / member.filename).resolve()
                    require(target.is_relative_to(folder.resolve()), 'Archiv PHP obsahuje cestu mimo cílovou složku.')
                archive.extractall(folder)
            (folder / 'php.ini').write_text('extension_dir="ext"\nextension=mbstring\n', encoding='utf-8')
    return verify()


def check(script, args=(), input=None):
    verify()
    result = run([php(), cache() / script, *args], input=input, check=False)
    if result.returncode:
        raise Failure((result.stdout + result.stderr).decode('utf-8', errors='replace').strip())
    return result.stdout.decode('utf-8', errors='replace').strip()
