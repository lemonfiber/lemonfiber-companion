#!/usr/bin/env python3
"""Whether a change touches code, so the jobs that judge only code can skip one that does not.

A pull request that changes only documentation holds runners for the PHP
toolchain, the planted-rule suite, the test suite, the bridge's Kotlin and
Swift gates and the CodeQL analysis, and none of them can answer differently
than it did on the base. So each of those jobs asks this first, through a `what
changed` job, and skips where the answer is no. `ci.yml` and `codeql.yml` each
run that job, and both run this script, so the two cannot disagree about a
path.

It answers once per gate, as a line written to `$GITHUB_OUTPUT`:

  code       the PHP gates. Documentation is not code: Markdown, `.docs/` and
             `LICENSE`, but for the Markdown a PHP gate reads. `ARCHITECTURE.md`
             is read by the planted-rule suite, which plants a violation of
             every rule it documents, and `.docs/requirements/` by the test
             suite and by the Arch suite the planted-rule suite runs. Nor is
             anything under `.github/` code, but `ci.yml`, which runs these
             gates, and `sdk-bump.yml`, which the planted-rule suite reads.
             `ci.yml` is not code either where every line it changes is a pin
             on a shared workflow from `lemonfiber/spec`, since those jobs run
             none of these gates.
  typecheck  every Swift file the bridge ships, compiled for iOS against the
             locked `nativephp/mobile` as `scripts/patch_nativephp.php` patches
             it: the iOS sources, the two scripts, and the lock where that
             package's entry moved.
  swift      the Swift package, linted, built and tested.
  kotlin     the Gradle project, whose ktlint reads the root `.editorconfig`
             as well as the bridge's own.
  agreement  the two test trees, held to each other.
  analyze    CodeQL's analysis of the workflows, which reads `.github/`. Only
             documentation is not code for it.

Each bridge gate is started by the paths it reads, Markdown among them. A
change to `ci.yml` beyond its pins starts every gate, and so does a change to
this script. Anything else that is not documentation is code, so a file of a
kind nobody has thought about runs the PHP gates. A rename is read as the
deletion and the addition it is, so moving a source file into `.docs/` reaches
code through the deletion.

Asked about nothing it can compare (no base, a base that is not a commit here,
a push that opened a branch) it answers yes for every gate. So does a run that
fails: the jobs asking run whenever the `what changed` job did not succeed.

Usage:
  the_code_a_change_touches.py <base>    decide for HEAD against <base>
  the_code_a_change_touches.py --self-test
Exit 0 = decided, 1 = the self-test found a claim broken.
"""

from __future__ import annotations

import json
import os
import pathlib
import re
import subprocess
import sys
import tempfile
from fnmatch import fnmatchcase

# The gates this answers for, in the order their lines are written.
GATES = ("code", "typecheck", "swift", "kotlin", "agreement", "analyze")

# The workflow that runs the gates.
CI = ".github/workflows/ci.yml"

# This script, whose answer decides every gate.
ITSELF = "scripts/the_code_a_change_touches.py"

# The paths each bridge gate reads, as `case` patterns: `*` crosses a `/`.
BRIDGE = {
    "typecheck": ("bridge/resources/ios/*", "scripts/typecheck_native.sh", "scripts/patch_nativephp.php"),
    "swift": ("bridge/resources/ios/*", "bridge/ios/*", "bridge/Package.swift", "bridge/.swift-format"),
    "kotlin": ("bridge/resources/android/*", "bridge/android/*", "bridge/.editorconfig", ".editorconfig"),
    "agreement": ("bridge/android/src/test/kotlin/*", "bridge/ios/Tests/*"),
}

# The Markdown a PHP gate reads, which is code however it is spelled.
READ = ("ARCHITECTURE.md", ".docs/requirements/*")

# The files under `.github/` the planted-rule suite reads besides `ci.yml`.
READ_UNDER_GITHUB = frozenset({".github/workflows/sdk-bump.yml"})

# The lock, and the package in it whose sources `typecheck` compiles against.
LOCK = "composer.lock"
NATIVEPHP = "nativephp/mobile"

# A changed line that moves a pin on a shared workflow and nothing else.
PIN = re.compile(r"[-+]\s*uses: lemonfiber/spec/\.github/workflows/[^@]+@[0-9a-f]{40}( # .*)?")


def git(cwd: pathlib.Path | None, *args: str) -> str:
    """Git's answer, failing on anything but success."""
    return subprocess.run(["git", *args], cwd=cwd, capture_output=True, check=True, text=True).stdout


def matches(path: str, patterns: tuple[str, ...]) -> bool:
    """Whether a path matches any pattern."""
    return any(fnmatchcase(path, pattern) for pattern in patterns)


def documentation(path: str) -> bool:
    """Whether no PHP gate reads this path."""
    if matches(path, READ):
        return False
    return path.startswith(".docs/") or path.endswith(".md") or path == "LICENSE"


def only_pins(cwd: pathlib.Path | None, base: str) -> bool:
    """Whether every line the change makes to `ci.yml` is a pin on a shared workflow."""
    diff = git(cwd, "diff", "-U0", base, "HEAD", "--", CI)
    changed = [
        line for line in diff.splitlines() if line[:1] in "+-" and not line.startswith(("+++ ", "--- "))
    ]
    return all(PIN.fullmatch(line) for line in changed)


def nativephp(cwd: pathlib.Path | None, commit: str) -> object:
    """The lock's entry for `nativephp/mobile` at a commit, or None where it has none."""
    try:
        lock = json.loads(git(cwd, "show", f"{commit}:{LOCK}"))
    except (subprocess.CalledProcessError, json.JSONDecodeError):
        return None
    packages = lock.get("packages") if isinstance(lock, dict) else None
    return next(
        (entry for entry in packages or [] if isinstance(entry, dict) and entry.get("name") == NATIVEPHP),
        None,
    )


def reaches(cwd: pathlib.Path | None, base: str, path: str) -> set[str]:
    """The gates one changed path reaches."""
    gates = {gate for gate, patterns in BRIDGE.items() if matches(path, patterns)}
    if path == LOCK and nativephp(cwd, base) != nativephp(cwd, "HEAD"):
        gates.add("typecheck")
    if documentation(path):
        return gates
    gates.add("analyze")
    if path == ITSELF or (path == CI and not only_pins(cwd, base)):
        return set(GATES)
    if path.startswith(".github/") and path not in READ_UNDER_GITHUB:
        return gates
    return gates | {"code"}


def changed(cwd: pathlib.Path | None, base: str) -> list[str] | None:
    """Each path HEAD changes against `base`; None where `base` is not a commit."""
    if not base:
        return None
    known = subprocess.run(
        ["git", "cat-file", "-e", f"{base}^{{commit}}"], cwd=cwd, capture_output=True, check=False
    )
    if known.returncode != 0:
        return None
    return git(cwd, "diff", "--no-renames", "--name-only", "-z", base, "HEAD").split("\0")[:-1]


def decide(base: str, cwd: pathlib.Path | None = None) -> tuple[dict[str, bool], list[str]]:
    """Which gates the change reaches, and the lines that say why."""
    paths = changed(cwd, base)
    if paths is None:
        return dict.fromkeys(GATES, True), [f"No commit `{base}` to compare against, so every gate runs."]
    answer = dict.fromkeys(GATES, False)
    why = []
    for path in paths:
        gates = [gate for gate in GATES if gate in reaches(cwd, base, path)]
        answer.update(dict.fromkeys(gates, True))
        why.append(f"{path}: {', '.join(gates) or 'no gate'}")
    return answer, why


def report(answer: dict[str, bool], why: list[str]) -> None:
    """The answer where the workflow reads it, and the reason where a person does."""
    lines = [f"{gate}={'true' if answer[gate] else 'false'}" for gate in GATES]
    print("\n".join([*lines, *why]))
    if output := os.environ.get("GITHUB_OUTPUT"):
        with pathlib.Path(output).open("a", encoding="utf-8") as out:
            out.write("".join(f"{line}\n" for line in lines))
    if summary := os.environ.get("GITHUB_STEP_SUMMARY"):
        with pathlib.Path(summary).open("a", encoding="utf-8") as out:
            out.write("### What changed\n\n" + "".join(f"- `{line}`\n" for line in [*lines, *why]))


def self_test() -> int:
    """Each claim in the docstring, against a repository made to break it."""
    failures = []
    with tempfile.TemporaryDirectory() as made:
        root = pathlib.Path(made)

        def commit(files: dict[str, str | None]) -> None:
            for name, text in files.items():
                path = root / name
                if text is None:
                    path.unlink()
                else:
                    path.parent.mkdir(parents=True, exist_ok=True)
                    path.write_text(text, encoding="utf-8")
            who = ("-c", "user.name=t", "-c", "user.email=t@t", "-c", "commit.gpgsign=false")
            git(root, "add", "-A")
            git(root, *who, "commit", "-q", "--allow-empty", "-m", "x")

        def lock(version: str) -> str:
            return json.dumps({"packages": [{"name": "laravel/framework"}, {"name": NATIVEPHP, "version": version}]})

        pin = "      uses: lemonfiber/spec/.github/workflows/dco.yml@{} # v1.0.{}\n"
        workflow = "jobs:\n  dco:\n" + pin.format("a" * 40, 1) + "  checks:\n    runs-on: ubuntu-latest\n"
        moved = workflow.replace(pin.format("a" * 40, 1), pin.format("b" * 40, 2))

        git(root, "init", "-q")
        commit(
            {
                "README.md": "a",
                "ARCHITECTURE.md": "a",
                ".docs/decisions/a.md": "a",
                ".docs/requirements/a.md": "a",
                "LICENSE": "a",
                "app-modules/kernel/src/Thing.php": "a",
                "app-modules/kernel/src/README.md": "a",
                "bridge/ios/Sources/A.swift": "a",
                LOCK: lock("1.0.0"),
                CI: workflow,
                ".github/workflows/sdk-bump.yml": "a",
                ".github/workflows/codeql.yml": "a",
            }
        )
        base = git(root, "rev-parse", "HEAD").strip()
        nothing = dict.fromkeys(GATES, False)
        everything = dict.fromkeys(GATES, True)

        def asks(name: str, files: dict[str, str | None], **expected: bool) -> None:
            git(root, "reset", "-q", "--hard", base)
            commit(files)
            got, why = decide(base, root)
            if got != {**nothing, **expected}:
                failures.append(f"{name}: {got}, expected {expected} ({why})")

        php = {"code": True, "analyze": True}
        asks("nothing at all", {})
        asks("the readme, a decision and the license", {"README.md": "b", ".docs/decisions/a.md": "b", "LICENSE": "b"})
        asks("a module's readme", {"app-modules/kernel/src/README.md": "b"})
        asks("a decision deleted", {".docs/decisions/a.md": None})
        asks("the rules the planted suite reads", {"ARCHITECTURE.md": "b"}, **php)
        asks("a requirement the tests read", {".docs/requirements/a.md": "b"}, **php)
        asks("a requirement added", {".docs/requirements/b.md": "b"}, **php)
        asks("a source file", {"app-modules/kernel/src/Thing.php": "b"}, **php)
        asks("a source file moved into the docs", {"app-modules/kernel/src/Thing.php": None, ".docs/thing.md": "a"}, **php)
        asks("a file of a kind nobody listed", {"notes.txt": "b"}, **php)
        asks("the workflow the planted suite reads", {".github/workflows/sdk-bump.yml": "b"}, **php)
        asks("another workflow", {".github/workflows/codeql.yml": "b"}, analyze=True)
        asks("a pin moved in ci.yml", {CI: moved}, analyze=True)
        asks("a job changed in ci.yml", {CI: workflow.replace("ubuntu-latest", "ubuntu-24.04")}, **everything)
        asks("ci.yml deleted", {CI: None}, **everything)
        asks("this script", {ITSELF: "b"}, **everything)
        asks("a Swift source", {"bridge/ios/Sources/A.swift": "b"}, swift=True, **php)
        asks("Markdown in the Swift package", {"bridge/ios/README.md": "b"}, swift=True)
        asks("an iOS resource", {"bridge/resources/ios/A.swift": "b"}, typecheck=True, swift=True, **php)
        asks("a Kotlin test", {"bridge/android/src/test/kotlin/A.kt": "b"}, kotlin=True, agreement=True, **php)
        asks("the root editor settings", {".editorconfig": "b"}, kotlin=True, **php)
        asks("the typecheck script", {"scripts/typecheck_native.sh": "b"}, typecheck=True, **php)
        asks("NativePHP moved in the lock", {LOCK: lock("1.1.0")}, typecheck=True, **php)
        asks("another package moved in the lock", {LOCK: lock("1.0.0").replace("framework", "framework-x")}, **php)

        for missing in ("", "0" * 40, "f" * 40):
            got, _ = decide(missing, root)
            if got != everything:
                failures.append(f"a base of {missing!r} decided some gate skips: {got}")

    for failure in failures:
        print(f"FAIL: {failure}", file=sys.stderr)
    if not failures:
        print("every claim refused its break")
    return 1 if failures else 0


def main(argv: list[str]) -> int:
    if argv == ["--self-test"]:
        return self_test()
    if len(argv) != 1:
        print(__doc__, file=sys.stderr)
        return 2
    report(*decide(argv[0]))
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
