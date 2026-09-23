from pathlib import Path

from .common import config, meaningful, read_json, require
from .gate import receipt
from .policy import names, task_record, validate_comment, validate_issue


def create(root, draft, client):
    validate_issue(root, draft, closed=False)
    title = draft['title'].strip()
    for existing in client.pages('/issues?state=open'):
        require(existing['title'].strip().casefold() != title.casefold(), 'Stejně nazvané otevřené issue již existuje.')
    payload = {k: draft[k] for k in ('title', 'body', 'labels', 'assignees')}
    return client.request('POST', '/issues', payload)


def update(root, number, draft, client):
    old = client.issue(number)
    validate_issue(root, {**old, **draft, 'number': number})
    return client.request('PATCH', f'/issues/{number}', {k: draft[k] for k in ('title', 'body', 'labels', 'assignees')})


def close(root, number, summary, client):
    proof = receipt(root)
    require(proof['online'] is True and number in proof['issues'], 'Chybí online ověření příslušného úkolu.')
    record = task_record(root, number)
    item = client.issue(number)
    require(item['state'] == 'open', 'Issue již není otevřené.')
    validate_issue(root, item, closed=True)
    require(('rozhrani' in names(item['labels'])) == record['visual'], 'Nesouhlasí klasifikace změny rozhraní.')
    # Fresh GitHub CI must be green for exactly the locally verified commit.
    checks = client.request('GET', '/commits/' + proof['commit'] + '/check-runs?per_page=100')
    required = 'Povinne kontroly'
    matching = [c for c in checks.get('check_runs', []) if c['name'] == required and c.get('app', {}).get('slug') == 'github-actions']
    require(matching and matching[0].get('conclusion') == 'success', 'Chybí úspěšné GitHub CI ověřeného commitu.')
    main = client.request('GET', '/commits/main')
    require(main['sha'] == proof['commit'], 'Ověřená změna ještě není aktuálním main.')
    meaningful(summary, 'Výsledek úkolu')
    lines = [summary, f'Ověření: https://github.com/{config(root)["repository"]}/commit/{proof["commit"]}']
    if record['delivery'] == 'svn':
        delivery = read_json(Path(root) / '.local' / 'svn' / f'{number}-predano.json')
        require(delivery['commit'] == proof['commit'], 'Chybí doložené SVN předání této verze.')
        lines.append(f'Předáno do SVN v revizi {delivery["svn_revision"]}.')
    comment = '\n'.join(lines)
    validate_comment(comment)
    client.request('POST', f'/issues/{number}/comments', {'body': comment})
    return client.request('PATCH', f'/issues/{number}', {'state': 'closed', 'state_reason': 'completed'})


def audit(root, client, number=None):
    items = [client.issue(number)] if number else client.pages('/issues?state=all')
    count = 0
    for item in items:
        if 'pull_request' in item:
            continue
        validate_issue(root, item)
        for comment in client.pages(f'/issues/{item["number"]}/comments'):
            validate_comment(comment['body'])
        count += 1
    return count
