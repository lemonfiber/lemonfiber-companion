"""Whether the Kotlin and Swift halves of the bridge ask the same questions.

  python3 scripts/both_halves_agree.py [--root DIR]
  python3 scripts/both_halves_agree.py --self-test
"""

import argparse
import re
import sys
import tempfile
from pathlib import Path

KOTLIN = Path("bridge/android/src/test/kotlin")
SWIFT = Path("bridge/ios/Tests")
PLATFORM_ONLY = Path("bridge/platform-only.txt")
ANDROID = "android"
IOS = "ios"
PLATFORMS = (ANDROID, IOS)
DECLARED = re.compile(r"^(?P<name>[A-Z][A-Za-z0-9]*)\s+(?P<platform>\S+)\s+(?P<why>\S.*)$")


def kotlin_path(name: str) -> Path:
    """The Kotlin suite of a rule."""
    return KOTLIN / f"{name}Test.kt"


def swift_path(name: str) -> Path:
    """The Swift suite of a rule."""
    return SWIFT / f"{name}Tests" / f"{name}Tests.swift"


def suites(root: Path) -> dict[str, set[str]]:
    """Every rule with a suite, by platform."""
    return {
        ANDROID: {p.stem.removesuffix("Test") for p in (root / KOTLIN).glob("*Test.kt")},
        IOS: {p.parent.name.removesuffix("Tests") for p in (root / SWIFT).glob("*Tests/*Tests.swift")},
    }


def declared(root: Path) -> tuple[dict[str, str], list[str]]:
    """The rules declared to one platform alone, and each line that declares nothing readable."""
    listed: dict[str, str] = {}
    wrong: list[str] = []
    path = root / PLATFORM_ONLY
    if not path.exists():
        return listed, wrong
    for number, line in enumerate(path.read_text().splitlines(), start=1):
        if line.strip() == "" or line.startswith("#"):
            continue
        found = DECLARED.match(line)
        if found is None or found["platform"] not in PLATFORMS:
            wrong.append(
                f"{PLATFORM_ONLY}:{number} is not `<Rule> <{'|'.join(PLATFORMS)}> <why the other platform needs none>`."
            )
            continue
        if found["name"] in listed:
            wrong.append(f"{PLATFORM_ONLY}:{number} declares {found['name']} a second time.")
            continue
        listed[found["name"]] = found["platform"]
    return listed, wrong


def asked(root: Path, name: str) -> dict[str, set[str]]:
    """The questions each half of a rule asks."""
    return {
        ANDROID: set(re.findall(r"fun `([^`]+)`", (root / kotlin_path(name)).read_text())),
        IOS: set(re.findall(r'@Test\("([^"]+)"\)', (root / swift_path(name)).read_text())),
    }


def held_to(listed: dict[str, str], found: dict[str, set[str]]) -> tuple[list[str], list[str]]:
    """Whether each rule declared to one platform is still that platform's alone."""
    wrong: list[str] = []
    agreed: list[str] = []
    for name, platform in sorted(listed.items()):
        other = PLATFORMS[1 - PLATFORMS.index(platform)]
        if name not in found[platform]:
            wrong.append(
                f"{name} is declared {platform}-only in {PLATFORM_ONLY}, and {platform} has no such suite. "
                "Remove the line."
            )
        elif name in found[other]:
            wrong.append(
                f"{name} is declared {platform}-only in {PLATFORM_ONLY}, and {other} now tests it too. "
                "Remove the line so the two halves are compared."
            )
        else:
            agreed.append(f"{name}: {platform} alone, as {PLATFORM_ONLY} declares.")
    return wrong, agreed


def undeclared(listed: dict[str, str], found: dict[str, set[str]]) -> list[str]:
    """Each rule tested on one platform alone that no declaration names."""
    return [
        "A rule is tested on one platform and not the other: no such file: "
        f"{swift_path(name) if name in found[ANDROID] else kotlin_path(name)}. "
        f"Add the missing suite, or declare the rule in {PLATFORM_ONLY} with why the other platform needs none."
        for name in sorted(found[ANDROID] ^ found[IOS])
        if name not in listed
    ]


def compared(root: Path, name: str) -> tuple[bool, str]:
    """Whether the two halves of a rule ask the same questions, and what is said of it."""
    questions = asked(root, name)
    if questions[ANDROID] == questions[IOS]:
        return True, f"{name}: both halves answer the same {len(questions[ANDROID])} questions."
    lines = [f"{name}Test and {name}Tests no longer ask the same questions."]
    lines += [
        f"  only on {side}: {question}"
        for side, other in ((ANDROID, IOS), (IOS, ANDROID))
        for question in sorted(questions[side] - questions[other])
    ]
    lines.append("  Add the missing case rather than deleting the one that has no pair.")
    return False, "\n".join(lines)


def judge(root: Path) -> tuple[list[str], list[str]]:
    """What disagrees between the halves under that root, and what agrees."""
    found = suites(root)
    listed, wrong = declared(root)
    refused, agreed = held_to(listed, found)
    wrong += refused + undeclared(listed, found)
    for name in sorted(found[ANDROID] & found[IOS]):
        same, said = compared(root, name)
        (agreed if same else wrong).append(said)
    return wrong, agreed


def _tree(root: Path, kotlin: dict[str, list[str]], swift: dict[str, list[str]], platform_only: str | None) -> Path:
    """A bridge test tree holding those suites and that declaration."""
    (root / KOTLIN).mkdir(parents=True)
    (root / SWIFT).mkdir(parents=True)
    for name, questions in kotlin.items():
        (root / kotlin_path(name)).write_text("".join(f"    fun `{q}`() {{}}\n" for q in questions))
    for name, questions in swift.items():
        (root / swift_path(name)).parent.mkdir(parents=True)
        (root / swift_path(name)).write_text("".join(f'@Test("{q}")\nfunc a() {{}}\n' for q in questions))
    if platform_only is not None:
        (root / PLATFORM_ONLY).write_text(platform_only)
    return root


def self_test() -> list[str]:
    """Each way the halves can disagree, put in front of the judgement; what it failed to refuse or to pass."""
    paired = {"LockRule": ["a lock opens"]}
    relay = {"RelayRule": ["an address is handed at loopback"]}
    why = "RelayRule ios Android fetches every segment through DoorDataSource.\n"
    cases = [
        ("agreeing halves", paired, paired, None, None),
        ("a declared platform-only rule", paired, {**paired, **relay}, why, None),
        ("an undeclared rule on one platform", paired, {**paired, **relay}, None, "no such file"),
        ("a declared rule that gained its counterpart", {**paired, **relay}, {**paired, **relay}, why, "now tests it too"),
        ("a declared rule that no longer exists", paired, paired, why, "has no such suite"),
        ("a declared rule on the other platform", {**paired, **relay}, paired, why, "has no such suite"),
        ("a declaration with no reason", paired, {**paired, **relay}, "RelayRule ios\n", "is not"),
        ("a declaration naming no platform", paired, {**paired, **relay}, "RelayRule desktop why\n", "is not"),
        ("a rule declared twice", paired, {**paired, **relay}, why + why, "a second time"),
        ("halves asking different questions", paired, {"LockRule": ["a lock stays shut"]}, None, "only on ios"),
    ]
    failures = []
    for title, kotlin, swift, platform_only, refused in cases:
        with tempfile.TemporaryDirectory() as scratch:
            wrong, _ = judge(_tree(Path(scratch), kotlin, swift, platform_only))
        said = "\n".join(wrong)
        if refused is None and wrong:
            failures.append(f"{title} was refused: {said}")
        if refused is not None and refused not in said:
            failures.append(f"{title} was not refused for `{refused}`: {said or 'nothing was said'}")
    return failures


def main() -> int:
    """Judge the tree, or the judgement itself."""
    parser = argparse.ArgumentParser()
    parser.add_argument("--root", type=Path, default=Path("."))
    parser.add_argument("--self-test", action="store_true")
    arguments = parser.parse_args()

    if arguments.self_test:
        failures = self_test()
        for failure in failures:
            print(f"::error::{failure}")
        if not failures:
            print("The judgement refuses every disagreement put in front of it, and passes agreement.")
        return 1 if failures else 0

    wrong, agreed = judge(arguments.root)
    for line in agreed:
        print(line)
    for line in wrong:
        print(f"::error::{line}")
    return 1 if wrong else 0


if __name__ == "__main__":
    sys.exit(main())
