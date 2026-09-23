import contextlib
import io
import unittest
from unittest.mock import patch

from nastroje_prace import gate
from nastroje_prace.common import Failure, git, read_json, write_json
from support import MESSAGE, commit, fixture


class GateTests(unittest.TestCase):
    def setUp(self):
        self.temp, self.root = fixture()
        self.base = git(self.root, 'rev-parse', 'HEAD')
    def tearDown(self): self.temp.cleanup()

    def change(self, document=True):
        (self.root / 'src/main.txt').write_text('nový obsah\n', encoding='utf-8')
        if document:
            with (self.root / 'docs/ukoly/1.md').open('a', encoding='utf-8') as out:
                out.write('\nDoplněno ověření konkrétní provedené změny.\n')
        return commit(self.root)

    def check(self, base=None):
        with contextlib.redirect_stdout(io.StringIO()):
            return gate.verify(self.root, base or self.base)

    def test_valid_change_receives_exact_commit_receipt(self):
        sha = self.change(); value = self.check()
        self.assertEqual(value['commit'], sha)
        self.assertEqual(gate.receipt(self.root)['commit'], sha)

    def test_code_only_and_code_with_unrelated_image_fail(self):
        self.change(document=False)
        with self.assertRaises(Failure): self.check()
        path = self.root / 'docs/snimky/unrelated.png'; path.parent.mkdir(parents=True); path.write_bytes(b'fixture')
        commit(self.root)
        with self.assertRaises(Failure): self.check()

    def test_unknown_base_and_wrong_root_never_skip(self):
        self.change()
        for base in ('1' * 40, 'ROOT'):
            with self.subTest(base=base), self.assertRaises(Failure): self.check(base)

    def test_dirty_tree_and_new_commit_invalidate_receipt(self):
        self.change(); self.check()
        (self.root / 'src/main.txt').write_text('další změna')
        with self.assertRaises(Failure): gate.receipt(self.root)
        commit(self.root)
        with self.assertRaises(Failure): gate.receipt(self.root)

    def test_failing_test_has_no_success_receipt(self):
        settings = read_json(self.root / '.prace.json')
        settings['tests'] = [['{python}', '-c', 'raise SystemExit(7)']]
        write_json(self.root / '.prace.json', settings)
        sha = self.change()
        with self.assertRaises(Failure): self.check()
        self.assertFalse(gate.receipt_path(self.root, sha).exists())

    def test_failed_recheck_removes_old_success(self):
        sha = self.change(); self.check()
        with patch('nastroje_prace.gate.repository_content', side_effect=Failure('Povinná kontrola selhala')):
            with self.assertRaises(Failure): self.check()
        self.assertFalse(gate.receipt_path(self.root, sha).exists())

    def test_modified_upstream_is_rejected(self):
        from nastroje_prace import upstream
        original = upstream.cache
        with patch('nastroje_prace.upstream.cache', return_value=self.root):
            with self.assertRaises(Failure): upstream.verify()

    def test_each_issue_needs_its_own_changed_record(self):
        record = read_json(self.root / '.tasks/1.json'); record['issue'] = 2
        write_json(self.root / '.tasks/2.json', record)
        (self.root / 'docs/ukoly/2.md').write_text((self.root / 'docs/ukoly/1.md').read_text(encoding='utf-8'), encoding='utf-8')
        self.base = commit(self.root)
        (self.root / 'src/main.txt').write_text('Jiný výsledek')
        with (self.root / 'docs/ukoly/1.md').open('a', encoding='utf-8') as out: out.write('\nZměna patří k jinému úkolu.\n')
        commit(self.root, MESSAGE.replace('(#1)', '(#2)'))
        with self.assertRaisesRegex(Failure, 'Každý úkol'): self.check()


if __name__ == '__main__': unittest.main()
