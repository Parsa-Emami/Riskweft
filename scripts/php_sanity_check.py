#!/usr/bin/env python3
r"""
Best-effort static sanity check for the hand-authored PHP tree, run because
no PHP/Composer runtime is available in this sandbox (no network egress to
Packagist/apt, no php binary installed). This is NOT a substitute for
`php -l` / `composer install` / `phpunit` - it is a deliberately narrow,
single-pass character scanner (properly quote/comment/heredoc aware, unlike
a naive two-pass regex) that catches:

  1. Unbalanced braces/parens/brackets per file outside of strings/comments
     (a strong signal of a truncated or malformed class body).
  2. Every `use Some\Namespaced\Thing;` import in App\/Tests\/Database\
     resolves to a class/interface/enum/trait declared somewhere in the
     tree (third-party vendor imports can't be checked without a real
     Composer install, which needs network access this sandbox lacks).
  3. Every file's `namespace` line matches its path per PSR-4.
  4. Duplicate class/interface/enum/trait declarations across files.
  5. Every opened heredoc/nowdoc (<<<'EOF' / <<<EOF) has a matching
     terminator.

Exit code 0 = no issues found. Non-zero = see printed report.
"""
import re
import sys
from pathlib import Path

ROOT = Path(sys.argv[1] if len(sys.argv) > 1 else ".")

PSR4 = [
    ("app/", "App\\"),
    ("tests/", "Tests\\"),
    ("database/factories/", "Database\\Factories\\"),
]

OPEN_TO_CLOSE = {"(": ")", "[": "]", "{": "}"}
CLOSERS = {")": "(", "]": "[", "}": "{"}


def scan(text: str, filename: str):
    """Single linear pass. Returns (stack_at_eof, issues)."""
    i = 0
    n = len(text)
    stack = []
    found_issues = []
    line = 1

    while i < n:
        c = text[i]

        if c == "\n":
            line += 1
            i += 1
            continue

        if text[i:i + 2] == "//":
            j = text.find("\n", i)
            i = n if j == -1 else j
            continue

        if c == "#" and text[i:i + 2] != "#[":
            j = text.find("\n", i)
            i = n if j == -1 else j
            continue

        if text[i:i + 2] == "/*":
            j = text.find("*/", i + 2)
            if j == -1:
                i = n
            else:
                line += text.count("\n", i, j)
                i = j + 2
            continue

        if c == "'":
            j = i + 1
            while j < n and text[j] != "'":
                if text[j] == "\\" and j + 1 < n:
                    j += 2
                else:
                    j += 1
            line += text.count("\n", i, j)
            i = j + 1
            continue

        if c == '"':
            j = i + 1
            while j < n and text[j] != '"':
                if text[j] == "\\" and j + 1 < n:
                    j += 2
                else:
                    j += 1
            line += text.count("\n", i, j)
            i = j + 1
            continue

        if text[i:i + 3] == "<<<":
            m = re.match(r"<<<\s*(['\"]?)([A-Za-z_][A-Za-z0-9_]*)\1[ \t]*\r?\n", text[i:])
            if m:
                label = m.group(2)
                start_body = i + m.end()
                term_re = re.compile(r"(?m)^[ \t]*" + re.escape(label) + r"(?![A-Za-z0-9_])")
                m2 = term_re.search(text, start_body)
                if not m2:
                    found_issues.append(f"{filename}:{line}: heredoc/nowdoc <<<{label} has no matching terminator")
                    i = n
                else:
                    line += text.count("\n", i, m2.end())
                    i = m2.end()
                continue

        if c in OPEN_TO_CLOSE:
            stack.append((c, line))
            i += 1
            continue

        if c in CLOSERS:
            if not stack or stack[-1][0] != CLOSERS[c]:
                got = stack[-1][0] if stack else "<empty>"
                found_issues.append(f"{filename}:{line}: mismatched '{c}' (stack top was '{got}')")
            elif stack:
                stack.pop()
            i += 1
            continue

        i += 1

    return stack, found_issues


issues = []
declared = {}
uses_by_file = {}
namespace_by_file = {}

php_files = sorted(ROOT.rglob("*.php"))
print(f"Scanning {len(php_files)} PHP files under {ROOT}...")

for f in php_files:
    text = f.read_text(encoding="utf-8")
    rel = str(f.relative_to(ROOT))

    stack, heredoc_issues = scan(text, rel)
    issues.extend(heredoc_issues)
    if stack:
        opens = ", ".join(f"'{c}' opened at line {ln}" for c, ln in stack)
        issues.append(f"{rel}: {len(stack)} unclosed opener(s) at EOF: {opens}")

    ns_match = re.search(r"^namespace\s+([^;]+);", text, re.M)
    namespace_by_file[rel] = ns_match.group(1).strip() if ns_match else None

    if ns_match:
        for path_prefix, ns_prefix in PSR4:
            if rel.startswith(path_prefix):
                sub = rel[len(path_prefix):]
                dir_part = str(Path(sub).parent)
                expected_ns = ns_prefix.rstrip("\\")
                if dir_part not in (".", ""):
                    expected_ns += "\\" + dir_part.replace("/", "\\")
                if namespace_by_file[rel] != expected_ns:
                    issues.append(f"{rel}: namespace '{namespace_by_file[rel]}' != expected '{expected_ns}' from path")
                break

    for m in re.finditer(r"^\s*(?:abstract\s+|final\s+|readonly\s+)*(class|interface|enum|trait)\s+([A-Za-z0-9_]+)", text, re.M):
        kind, name = m.groups()
        fqcn = (namespace_by_file[rel] + "\\" + name) if namespace_by_file[rel] else name
        if fqcn in declared:
            issues.append(f"DUPLICATE declaration of {fqcn} in {rel} (already in {declared[fqcn]})")
        declared[fqcn] = rel

    file_uses = []
    for m in re.finditer(r"^use\s+([A-Za-z0-9_\\]+)(?:\s+as\s+[A-Za-z0-9_]+)?;", text, re.M):
        file_uses.append(m.group(1))
    uses_by_file[rel] = file_uses

print(f"Declared types found: {len(declared)}")

OURS = ("App\\", "Tests\\", "Database\\")
for rel, uses in uses_by_file.items():
    for u in uses:
        if u.startswith(OURS) and u not in declared:
            issues.append(f"{rel}: `use {u};` does not resolve to any declared type in this tree")

if issues:
    print(f"\n{len(issues)} POTENTIAL ISSUE(S):")
    for i in issues:
        print(" -", i)
    sys.exit(1)

print("\nNo brace/heredoc/namespace/duplicate/unresolved-own-import issues found.")
sys.exit(0)
