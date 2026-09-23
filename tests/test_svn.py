import contextlib
import io
import os
from pathlib import Path
import shutil
import tempfile
import unittest

from nastroje_prace import gate, svn
from nastroje_prace.common import Failure, git, read_json, run, write_json
from support import commit, fixture

HAS_SVN = bool(shutil.which(os.environ.get('NASTROJE_SVN', 'svn')) and shutil.which('svnadmin'))


class RequiredSvnTests(unittest.TestCase):
    def test_ci_has_real_svn(self):
        if os.environ.get('NASTROJE_REQUIRE_SVN') == '1':
            self.assertTrue(HAS_SVN, 'CI musí skutečně spustit SVN integrační scénáře.')


@unittest.skipUnless(HAS_SVN, 'Místní SVN CLI není dostupné; povinné reálné testy běží v Linux CI.')
class SvnIntegrationTests(unittest.TestCase):
    def setUp(self):
        self.temp, self.root = fixture()
        self.server = tempfile.TemporaryDirectory(prefix='nastroje-prace-svn-', ignore_cleanup_errors=True)
        self.server_root = Path(self.server.name)
        repo = self.server_root / 'repo'
        run(['svnadmin', 'create', repo])
        self.url = repo.as_uri()
        imported = self.server_root / 'import'
        (imported / 'src').mkdir(parents=True)
        (imported / 'src/main.txt').write_text('původní obsah\n', encoding='utf-8')
        run(['svn', 'import', imported, self.url, '-m', 'Testovaci zaklad', '--non-interactive'])
        self.wc = self.server_root / 'wc'
        run(['svn', 'checkout', self.url, self.wc, '--non-interactive'])
        settings = read_json(self.root / '.prace.json')
        settings['svn'] = {'enabled': True, 'url': self.url, 'allow': ['src/**']}
        write_json(self.root / '.prace.json', settings)
        record = read_json(self.root / '.tasks/1.json'); record['delivery'] = 'svn'
        write_json(self.root / '.tasks/1.json', record)
        self.base = commit(self.root)
        svn.baseline(self.root, 1)

    def tearDown(self):
        self.temp.cleanup(); self.server.cleanup()

    def change(self):
        (self.root / 'src/main.txt').write_text('nový obsah\n', encoding='utf-8')
        with (self.root / 'docs/ukoly/1.md').open('a', encoding='utf-8') as out: out.write('\nOvěřeno předání do izolovaného SVN.\n')
        commit(self.root)
        with contextlib.redirect_stdout(io.StringIO()): gate.verify(self.root, self.base)

    def colleague_change(self):
        (self.wc / 'src/main.txt').write_text('změna kolegy\n', encoding='utf-8')
        run(['svn', 'commit', self.wc, '-m', 'Souběžná testovací změna', '--non-interactive'])

    def test_prepare_verify_and_record_real_revision(self):
        self.change()
        manifest = svn.prepare(self.root, 1)
        value = svn.verify_package(self.root, manifest)
        self.assertEqual([i['path'] for i in value['files']], ['src/main.txt'])
        self.assertEqual((self.wc / 'src/main.txt').read_text(encoding='utf-8'), 'původní obsah\n')
        shutil.copyfile(self.root / 'src/main.txt', self.wc / 'src/main.txt')
        run(['svn', 'commit', self.wc, '-m', 'Predani testovaneho obsahu', '--non-interactive'])
        result = svn.record_delivery(self.root, manifest, 2)
        self.assertEqual(result['svn_revision'], 2)

    def test_colleague_edit_blocks_prepare(self):
        self.change(); self.colleague_change()
        with self.assertRaises(Failure): svn.prepare(self.root, 1)

    def test_colleague_edit_after_prepare_blocks_verify(self):
        self.change(); manifest = svn.prepare(self.root, 1); self.colleague_change()
        with self.assertRaises(Failure): svn.verify_package(self.root, manifest)

    def test_changed_zip_and_wrong_revision_fail(self):
        self.change(); manifest = svn.prepare(self.root, 1)
        with self.assertRaises(Failure): svn.record_delivery(self.root, manifest, 1)
        with (Path(manifest).parent / 'zmeny.zip').open('ab') as out: out.write(b'corruption')
        with self.assertRaises(Failure): svn.verify_package(self.root, manifest)

    def test_changed_manifest_cannot_claim_unverified_content(self):
        self.change(); manifest = svn.prepare(self.root, 1)
        value = read_json(manifest); value['files'][0]['after'] = 'a' * 64
        write_json(manifest, value)
        with self.assertRaisesRegex(Failure, 'Git obsahu'): svn.record_delivery(self.root, manifest, 2)

    def test_initial_git_copy_must_match_svn(self):
        (self.root / 'src/main.txt').write_text('zastaralý výchozí obsah', encoding='utf-8')
        commit(self.root)
        with self.assertRaisesRegex(Failure, 'neodpovídá aktuálnímu SVN'): svn.baseline(self.root, 1)


if __name__ == '__main__': unittest.main()
