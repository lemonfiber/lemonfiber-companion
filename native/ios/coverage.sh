#!/usr/bin/env bash
#
# The Swift half's coverage floor, held at the same 100% the PHP is.
#
# `swift test --enable-code-coverage` writes a profile beside the build, and
# `llvm-cov` reads it. Only the shipped source counts: the test target's own
# lines are not the thing being measured, and counting them is how a suite
# reaches 100% by testing itself.
#
# A floor rather than a report. The rule this covers is six lines long and
# decides whether a credential can be photographed — a line of it nothing runs is
# a line nobody has thought about.
set -euo pipefail

cd "$(dirname "$0")/.."

swift test --enable-code-coverage

profile=$(swift test --enable-code-coverage --show-codecov-path)
# Not `-type f`: on macOS an .xctest is a bundle directory, and the
# executable lives inside it. On Linux it is a plain file.
binary=$(find .build -name '*.xctest' -print -quit)

if [ -z "$binary" ]; then
    echo "::error::No test bundle found under .build; nothing to measure."
    exit 1
fi

# The bundle on macOS is a directory; the executable is inside it.
if [ -d "$binary" ]; then
    binary="${binary}/Contents/MacOS/$(basename "$binary" .xctest)"
fi

covered=$(xcrun llvm-cov export \
    --instr-profile "$(dirname "$profile")/default.profdata" \
    --summary-only \
    --ignore-filename-regex='(Tests/|\.build/)' \
    "$binary" \
    | python3 -c 'import json,sys; print(json.load(sys.stdin)["data"][0]["totals"]["lines"]["percent"])')

printf 'Swift line coverage: %s%%\n' "$covered"

python3 - "$covered" <<'PY'
import sys

covered = float(sys.argv[1])

if covered < 100.0:
    print(f"::error::Swift coverage is {covered}%, and the floor is 100%.")
    print("The rule this covers decides whether a credential can be photographed.")
    print("A line of it nothing runs is a line nobody has thought about.")
    sys.exit(1)

print("At the floor.")
PY
