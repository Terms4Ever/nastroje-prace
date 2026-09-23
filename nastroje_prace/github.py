import json
import os
from pathlib import Path
import urllib.error
import urllib.parse
import urllib.request

from .common import Failure, require, run


class GitHub:
    """Jeden klient svázaný s jedním repozitářem. Přihlášení se neukládá."""

    def __init__(self, repository):
        self.repository = repository
        self._token = None

    def token(self):
        if self._token is None:
            self._token = os.environ.get('GH_TOKEN') or os.environ.get('GITHUB_TOKEN')
            if not self._token:
                result = run(['git', '-c', 'credential.interactive=false', 'credential', 'fill'],
                             input=b'protocol=https\nhost=github.com\n\n')
                fields = dict(line.split('=', 1) for line in result.stdout.decode().splitlines() if '=' in line)
                self._token = fields.get('password')
            require(self._token, 'Chybí přihlášení na GitHub.')
        return self._token

    def request(self, method, suffix, data=None):
        require(suffix.startswith('/') and not suffix.startswith('//') and '..' not in suffix,
                'Neplatná cesta GitHub API.')
        url = 'https://api.github.com/repos/' + self.repository + suffix
        request = urllib.request.Request(url, method=method, data=json.dumps(data).encode() if data is not None else None,
            headers={'Authorization': 'Bearer ' + self.token(), 'Accept': 'application/vnd.github+json',
                     'X-GitHub-Api-Version': '2022-11-28', 'Content-Type': 'application/json'})
        try:
            with urllib.request.urlopen(request, timeout=60) as response:
                return json.load(response) if response.status != 204 else None
        except urllib.error.HTTPError as exc:
            raise Failure(f'GitHub API {method} {suffix.split("?")[0]}: HTTP {exc.code}.') from None
        except (OSError, ValueError):
            raise Failure('GitHub API není dostupné nebo vrátilo neplatnou odpověď.') from None

    def pages(self, suffix):
        rows = []
        for page in range(1, 101):
            batch = self.request('GET', suffix + ('&' if '?' in suffix else '?') + f'per_page=100&page={page}')
            require(isinstance(batch, list), 'GitHub nevrátil seznam.')
            rows.extend(batch)
            if len(batch) < 100:
                return rows
        raise Failure('Překročen limit stránek; kontrola nesmí tiše vynechat další záznamy.')

    def issue(self, number):
        require(isinstance(number, int) and number > 0, 'Neplatné číslo issue.')
        item = self.request('GET', f'/issues/{number}')
        require('pull_request' not in item, 'Očekáváno issue, nikoli pull request.')
        return item
