import json
import os
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
from nastroje_prace.common import Failure, config, git, require
from nastroje_prace.gate import verify
from nastroje_prace.github import GitHub

root = Path.cwd()
try:
    event = json.loads(Path(os.environ['GITHUB_EVENT_PATH']).read_text(encoding='utf-8'))
    if 'pull_request' in event:
        base = event['pull_request']['base']['sha']
    else:
        base = event.get('before')
    if not base or base == '0' * 40:
        if int(git(root, 'rev-list', '--count', 'HEAD')) == 1:
            base = 'ROOT'
        elif os.environ.get('GITHUB_REF') == 'refs/heads/main':
            base = git(root, 'rev-parse', 'HEAD^')
        else:
            base = git(root, 'merge-base', 'HEAD', 'origin/main')
    verify(root, base, online=True, client=GitHub(config(root)['repository']))
except Failure as exc:
    print(str(exc), file=sys.stderr)
    raise SystemExit(1)
