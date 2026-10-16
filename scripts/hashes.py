#!/usr/bin/env python3
"""Integrity of repository source, excluding ignored runtime/build state."""
from pathlib import Path
import hashlib
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
TARGET = ROOT / 'SHA256SUMS.txt'


def selected():
    names = subprocess.check_output(
        ['git', 'ls-files', '-z', '--cached', '--others', '--exclude-standard'],
        cwd=ROOT,
    ).decode().split('\0')
    return sorted(
        ROOT / name for name in set(names) - {''}
        if ROOT / name != TARGET and (ROOT / name).is_file()
    )


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


if len(sys.argv) > 1 and sys.argv[1] == '--write':
    TARGET.write_text(''.join(digest(path) + '  ' + path.relative_to(ROOT).as_posix() + '\n' for path in selected()))
    print('Wrote SHA256SUMS.txt')
    sys.exit(0)

errors = []
recorded = set()
for line in TARGET.read_text().splitlines():
    expected, name = line.split('  ', 1)
    path = ROOT / name
    if Path(name).is_absolute() or '..' in Path(name).parts:
        errors.append('Unsafe hash path')
        continue
    recorded.add(name)
    if not path.is_file() or digest(path) != expected:
        errors.append('Mismatch: ' + name)
actual = {path.relative_to(ROOT).as_posix() for path in selected()}
if recorded != actual:
    errors.append('Manifest file set differs from actual source file set')
print('Hash integrity passed' if not errors else '\n'.join(errors))
sys.exit(1 if errors else 0)
