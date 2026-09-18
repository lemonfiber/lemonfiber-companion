# What this app is required to do, and where each requirement is kept

Every requirement lemonfiber's companion answers is written down in the
[spec](https://github.com/lemonfiber/spec). This directory is the layer between
that and the code: a page per subject, naming the requirements it covers, what
each one asks, and the file or rule in this repository that keeps it.

## Why it exists rather than a comment beside the code

`GOV-R6` — a citation may not appear in a code comment. The requirement IDs used
to sit in the docblocks here, a few thousand of them, and every other repository
in the org had none: the Rust stack keeps a middle layer for exactly this reason
(`Q-R5`), and `sdk-php`, `sdk-ts` and `lemonfiber-web` carry no identifier in
source at all.

The argument in the spec's [code-comments](https://github.com/lemonfiber/spec/blob/main/40-quality/code-comments.md)
is that provenance is not documentation. An identifier in a comment gestures at
a page rather than saying anything, and it rots the moment that page is
superseded — silently, because nothing reads a comment. The prose that explains
*why* a thing is the way it is stays exactly where it was. What moves is the
number, to a page that can be revised, that a link can reach, and that a gate
can check.

## What a page here owes

Both halves, or the move costs something real:

- **what the requirement asks**, in one sentence, so a reader need not open the
  spec to know whether it is the one they want; and
- **what keeps it here** — the class, the rule, the test — so the requirement is
  findable from the code and the code from the requirement.

A page naming only the first is a second copy of the spec that will drift from
it. A page naming only the second is an index, and an index is what the reader
already has.

## The pages

| Page | What it covers |
|------|----------------|
| [reaching-a-stack.md](reaching-a-stack.md) | Every call this app makes to a machine, and everything it reads back |

More follow as each module is converted; `tests/Arch/NoRequirementIdInACommentTest.php`
is what stops the count going back up while that happens.
