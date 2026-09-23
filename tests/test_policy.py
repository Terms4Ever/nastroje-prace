import copy
from pathlib import Path
import tempfile
import unittest
from unittest.mock import Mock, patch

from nastroje_prace import issues, upstream
from nastroje_prace.common import Failure, git, safe_path
from nastroje_prace.policy import pictures, repository_content, validate_commit, validate_comment, validate_issue
from support import BODY, MESSAGE, commit, draft, fixture


class IssueTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        upstream.verify()

    def test_valid_agent_issue_uses_real_upstream_validator(self):
        self.assertTrue(validate_issue(Path.cwd(), draft()))

    def test_unstructured_agent_idea_is_rejected(self):
        item = draft(); item['body'] = 'Prosím něco upravit v aplikaci.'
        with self.assertRaises(Failure): validate_issue(Path.cwd(), item)

    def test_missing_assignee_and_type_are_rejected(self):
        for key in ('labels', 'assignees'):
            with self.subTest(key=key):
                item = draft(); item[key] = []
                with self.assertRaises(Failure): validate_issue(Path.cwd(), item)

    def test_dash_in_title_and_body_is_rejected(self):
        for key in ('title', 'body'):
            item = draft(); item[key] += '\u2014 nový obsah'
            with self.subTest(key=key), self.assertRaises(Failure): validate_issue(Path.cwd(), item)

    def test_extra_section_and_empty_task_are_rejected(self):
        for change in ('\n## Něco navíc\nDalší obsah.', '\n- [ ] TODO'):
            item = draft(); item['body'] += change
            with self.subTest(change=change), self.assertRaises(Failure): validate_issue(Path.cwd(), item)

    def test_long_body_rejected(self):
        item = draft(); item['body'] += '\n' + '\n'.join('Další konkrétní řádek.' for _ in range(45))
        with self.assertRaises(Failure): validate_issue(Path.cwd(), item)

    def test_closing_incomplete_checklist_rejected(self):
        item = draft(); item['number'] = 1
        with self.assertRaises(Failure): validate_issue(Path.cwd(), item, closed=True)

    def test_short_comment_passes_but_long_comment_fails(self):
        validate_comment('Ověření potvrdilo očekávaný výsledek.')
        with self.assertRaises(Failure): validate_comment('\n'.join(['Ověření potvrdilo výsledek.'] * 6))

    def test_invalid_draft_never_calls_github(self):
        client = Mock(); item = draft(); item['body'] = ''
        with self.assertRaises(Failure): issues.create(Path.cwd(), item, client)
        client.request.assert_not_called(); client.pages.assert_not_called()

    def test_duplicate_issue_does_not_create_second_one(self):
        client = Mock(); client.pages.return_value = [{'title': draft()['title']}]
        with self.assertRaises(Failure): issues.create(Path.cwd(), draft(), client)
        client.request.assert_not_called()

    def test_github_error_cannot_be_success(self):
        client = Mock(); client.pages.side_effect = Failure('Nedostupné API')
        with self.assertRaises(Failure): issues.audit(Path.cwd(), client)

    def test_commit_requires_issue_reason_and_evidence(self):
        self.assertEqual(validate_commit(MESSAGE), 1)
        for bad in ('Oprava', MESSAGE.replace(' (#1)', ''), MESSAGE.replace('Ověření:', 'Poznámka:')):
            with self.subTest(bad=bad), self.assertRaises(Failure): validate_commit(bad)


class RepositoryTests(unittest.TestCase):
    def setUp(self):
        self.temp, self.root = fixture()
    def tearDown(self):
        self.temp.cleanup()

    def test_missing_docs_is_failure(self):
        import shutil
        shutil.rmtree(self.root / 'docs')
        with self.assertRaisesRegex(Failure, 'docs'): repository_content(self.root)

    def test_private_token_is_rejected_without_echo(self):
        token = 'gh' + 'p_' + 'a' * 40
        (self.root / 'credentials.txt').write_text(token)
        git(self.root, 'add', '.')
        with self.assertRaises(Failure) as error: repository_content(self.root)
        self.assertNotIn(token, str(error.exception))

    def test_browser_profile_is_rejected(self):
        file = self.root / 'tests/asc-profile/History'; file.parent.mkdir(parents=True); file.write_bytes(b'example')
        git(self.root, 'add', '.')
        with self.assertRaises(Failure): repository_content(self.root)

    def test_traversal_and_metadata_paths_rejected(self):
        for path in ('../secret', '/outside', 'C:/outside', '.git/config', '.svn/wc.db', 'src/../../file'):
            with self.subTest(path=path), self.assertRaises(Failure): safe_path(self.root, path)

    def test_visual_issue_requires_real_paired_files(self):
        folder = self.root / 'docs/snimky/1-zkouska'; folder.mkdir(parents=True)
        # Synthetic signatures are exclusively test fixtures, never presented as application evidence.
        (folder / 'pred-formular.png').write_bytes(b'\x89PNG\r\n\x1a\nfixture-before')
        (folder / 'po-formular.png').write_bytes(b'\x89PNG\r\n\x1a\nfixture-after')
        sha = commit(self.root)
        prefix = f'https://github.com/Terms4Ever/nastroje-prace/blob/{sha}/docs/snimky/1-zkouska/'
        before = f'![Před]({prefix}pred-formular.png?raw=true)'
        after = f'![Po]({prefix}po-formular.png?raw=true)'
        pictures(self.root, 1, before + '\n' + after, True, 'Terms4Ever/nastroje-prace')
        for body in (before, after, '', (before + after).replace('/1-zkouska/', '/2-zkouska/')):
            with self.subTest(body=body), self.assertRaises(Failure): pictures(self.root, 1, body, True, 'Terms4Ever/nastroje-prace')
        (folder / 'po-formular.png').write_bytes(b'changed')
        with self.assertRaises(Failure): pictures(self.root, 1, before + after, True, 'Terms4Ever/nastroje-prace')

    def test_text_link_does_not_count_as_screenshot(self):
        with self.assertRaises(Failure): pictures(self.root, 1, '[Po](https://example.invalid/po.png)', True, 'Terms4Ever/nastroje-prace')

    def test_non_visual_change_does_not_require_screenshots(self):
        pictures(self.root, 1, BODY, False, 'Terms4Ever/nastroje-prace')


if __name__ == '__main__': unittest.main()
