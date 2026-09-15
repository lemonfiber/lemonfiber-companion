# 0005: How a rule is changed, and how one is retired

**Status:** Accepted · **Date:** 2026-09-11

## Context

`ARCHITECTURE.md` lists every rule and, beside it, the mechanism enforcing it.
`tests/Arch/TheRulesAreRealTest.php` reads that column and fails when the two
disagree, in both directions.

That makes the document load-bearing, which is the point — and it means somebody
will eventually be blocked by a rule at an inconvenient moment. Without a stated
route, the cheapest way past is to delete the row in an unrelated pull request,
where nobody is looking for it.

The document already records what happens when nothing watches: `D4` claimed an
enforcement it never had, and `A6` named a Pest expectation that does not exist.
Both survived review, because a rule table is exactly the kind of document
people stop reading once they trust it.

## Decision

**Changing a rule changes the row and the artifact in the same commit.** The
test enforces that much on its own. What it cannot enforce is the rest:

- The pull request body says what the rule was costing. A rule is changed
  because it was wrong or too expensive, and which one it was is the useful
  part.
- **Retiring** a rule needs the reason it no longer applies — not the fact that
  it is failing. "This is now caught by X" and "the thing it prevented cannot
  happen since Y" are reasons. "It was blocking the build" is a symptom.
- A rule that genuinely cannot be mechanised becomes `review`, honestly, rather
  than being deleted. The count is printed by the suite so it stays visible.
- A rule agreed but not yet built is `planned`, and the ratchet in the same test
  means that count may fall and may not rise.

## The planned rules, and what unblocks them

Each is waiting on code that does not exist yet, not on a decision.

> **This table records the state on 2026-09-11, the day this decision was
> accepted, and is left as it stood.** Every rule in it is enforced today —
> `F2`'s first presenter exists — and no row in `ARCHITECTURE.md` says `planned`
> any more, so the ratchet in `TheRulesAreRealTest` counts zero. That document
> is the live one the suite reads; this is the record of what was decided and
> when.

| Rule | Becomes real when |
|------|-------------------|
| `D1`, `D2` | `kernel` has its first `Api` signature to check |
| `F2` | the first presenter exists |
| `F3` | the first Blade template exists |
| `G2`, `H4` | the first port and adapter pair exists |

Lower the ceiling in the same commit that makes one real.

## Cost

Changing a rule is slower than deleting a line, deliberately. The intended
failure mode is a short argument, not a silent removal.
