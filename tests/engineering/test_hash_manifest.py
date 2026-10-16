"""A clean Git clone must verify after dependency caches were built locally."""
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

SOURCE = Path(__file__).resolve().parents[2] / 'scripts/hashes.py'


class SourceManifestTest(unittest.TestCase):
    def test_ignored_runtime_cache_is_not_shipped_and_source_corruption_is_rejected(self):
        with tempfile.TemporaryDirectory(prefix='riskweft-integrity-') as work:
            root = Path(work) / 'source'
            root.mkdir()
            subprocess.run(['git', 'init', '--quiet', str(root)], check=True)
            (root / 'scripts').mkdir()
            shutil.copyfile(SOURCE, root / 'scripts/hashes.py')
            (root / '.gitignore').write_text('bootstrap/cache/*.php\n.phpunit.cache/\n')
            (root / 'README.md').write_text('Synthetic source fixture.\n')
            (root / 'bootstrap/cache').mkdir(parents=True)
            (root / 'bootstrap/cache/packages.php').write_text('<?php return [];\n')
            subprocess.run(['python3', 'scripts/hashes.py', '--write'], cwd=root, check=True, capture_output=True)
            self.assertNotIn('bootstrap/cache/packages.php', (root / 'SHA256SUMS.txt').read_text())
            subprocess.run(['git', 'add', '--all'], cwd=root, check=True)
            subprocess.run(['git', '-c', 'user.name=Source fixture', '-c', 'user.email=fixture@example.invalid', 'commit', '--quiet', '-m', 'Synthetic fixture'], cwd=root, check=True)
            clone = Path(work) / 'clone'
            subprocess.run(['git', 'clone', '--quiet', '--no-hardlinks', str(root), str(clone)], check=True)
            subprocess.run(['python3', 'scripts/hashes.py'], cwd=clone, check=True, capture_output=True)
            (clone / 'bootstrap/cache').mkdir(parents=True)
            (clone / 'bootstrap/cache/packages.php').write_text('<?php return ["generated"];\n')
            subprocess.run(['python3', 'scripts/hashes.py'], cwd=clone, check=True, capture_output=True)
            (clone / 'README.md').write_text('Corrupted source bytes.\n')
            result = subprocess.run(['python3', 'scripts/hashes.py'], cwd=clone, text=True, capture_output=True)
            self.assertNotEqual(0, result.returncode)
            self.assertIn('Mismatch: README.md', result.stdout)


if __name__ == '__main__':
    unittest.main()
