import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
from nastroje_prace.common import Failure, config, git, require
from nastroje_prace.gate import verify
from nastroje_prace.github import GitHub

root = Path.cwd()
try:
    for line in sys.stdin:
        local_ref, local_sha, remote_ref, remote_sha = line.split()
        if local_sha == '0' * 40:
            continue
        require(local_sha == git(root, 'rev-parse', 'HEAD'), 'Push musí obsahovat právě ověřovaný HEAD.')
        base = remote_sha
        if base == '0' * 40:
            if int(git(root, 'rev-list', '--count', 'HEAD')) == 1:
                base = 'ROOT'
            else:
                base = git(root, 'merge-base', 'HEAD', 'origin/main')
        verify(root, base, online=True, client=GitHub(config(root)['repository']))
except Failure as exc:
    print(str(exc), file=sys.stderr)
    raise SystemExit(1)
