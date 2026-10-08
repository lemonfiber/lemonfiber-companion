# Naming, size and lifecycle

Part of [the rules](../../ARCHITECTURE.md#the-rules) `ARCHITECTURE.md` indexes.

## Naming and size

| | Rule | Enforced by |
|---|---|---|
| H1 | No `Manager`, `Helper`, `Util`, `Service`, `Data`, `Info` suffixes | arch |
| H2 | No `Interface`/`Abstract` affixes on type names | arch |
| H3 | Caps: methods per class (20), cognitive complexity | phpstan: own rule + `cognitive_complexity` |
| H4 | A test file mirrors its source file's location | arch: an orphan test fails, a class without one does not |
| H5 | A string with a value in it is built with `sprintf` — never `.`, never interpolation — and a message is one literal, never two joined by a dot | phpstan: own rule, one per node type |
| H6 | An exception is named for what happened, not for being an exception | arch |
| H7 | A test is named and described for the behaviour it pins | arch |
| H8 | A method returns from at most three places | phpstan: own rule |

**What `H3` does not cap, and why.** This row read *methods per class, lines
per method, constructor parameters, cognitive complexity* for a long time, and
only the last of the four had anything counting it — so a class could pass here
and be refused by SonarCloud under `Q-R64`, which is how `WhatWouldBePutRight`
reached twenty-one methods before anybody heard about it. The method count now
has a rule of its own, at SonarCloud's own number so the two cannot disagree.

The other two were dropped from the sentence rather than given mechanisms,
because both would refuse code that is right as it is. A cap on method length
would name `CompositionRoot::register` and `OperatorServiceProvider::boot`,
which are lists of bindings with a paragraph each on why — length is what a
reader wants there, and what makes a long method hard to follow is already
capped as cognitive complexity. A cap on constructor parameters would name the
row carriers: `WhatOneServiceSays` takes ten because a service has ten facts a
template renders, and `D1` refuses the array that would hide them behind one.
Splitting a row in half to satisfy a count makes two halves a template has to
join back up.

A rule whose sentence is wider than its mechanism is worse than a narrow rule
honestly described: the table reports green and a reader stops checking, which
is strictly worse than an unchecked area, because an unchecked area gets
reviewed by a person.

**Why the floors are per tree.** One percentage across every tree is an
average, and an average is true about what it covered and silent about what it
covered over: a capability at 100% carries an adapter at 40% and the gate
reports a pass. The clover report already holds the per-file numbers, so
splitting it by directory costs nothing at the point of measurement and turns
one number into one per tree.

**A tree's floors are declared in the nearest manifest above it.** That is one
principle applied three times rather than three cases:
`app-modules/kernel/src` is held by `app-modules/kernel/composer.json`,
`bridge/src` by `bridge/composer.json`, and `bootstrap/Composition` by the root
manifest. Derived rather than listed, which is what makes *every tree
`phpunit.xml` measures is held to a bar* checkable instead of a list somebody
maintains: `Tests\Support\MeasuredTree` reads the trees out of `phpunit.xml`
and the floors out of whichever manifest is nearest each, and G7, G9 and
`scripts/mutation.php` all read it.

The rule it replaces was *in the module's own manifest*, and two trees could
not obey it. `bridge/src` is a path package: its namespace is
`Lemonfiber\Native\` rather than `Modules\<Name>` and its manifest declares no
kind, so nothing could read it as a module. `bootstrap/Composition` has no
manifest at all. Between them that was 2,900 lines — 7% of the measured source
— inside the global coverage floor and outside every per-directory bar, with
nowhere to declare one.

**No manifest may be nearest to two measured trees.** One manifest declares one
pair of numbers, so two trees reaching the same one share a bar between them —
the averaging above, except quieter, because nothing in either tree says it is
being judged alongside the other. The root's is where it would happen: it is
what every tree falls back to.

**There is deliberately no default floor.** A tree whose manifest declares none
fails G7 by name. A default would put the number back where nobody chose it,
and a tree added tomorrow would inherit a bar somebody picked for a different
tree — which is the silent exemption the change exists to remove. Where the
manifest declares a kind, that kind's convention is named in the failure
message instead, so declaring it is a ten-second job rather than a guess.

**The ratchet is on the declared floors, not the measured ones.** The obvious
rule — a floor tracks actual coverage and may only rise — is a gate that blocks
its own cure: a tree gaining a well-tested class raises its real coverage
without anyone deciding to, and the build turns red for an improvement. Declared
floors move only when somebody edits a manifest, so the total can never be
tripped by code getting better. The slack between a floor and the real number is
printed every run and argued down in review.

**Mutation floors are declared in the same place and cannot be read the same
way.** There is no machine-readable mutation report — Pest offers `--min`, which
fails a run, and nothing that emits a score. So the floors are enforced by
invocation: trees sharing a floor share one run, because a floor of 100 admits
no offsetting between them, and a tree whose floor differs gets its own. The
path list comes from the same derivation the coverage floors do rather than
being written out, which is what stops it going stale the day a tree is added.
The invocation is `composer test:mutation`, run locally: mutation testing is not
run in CI.

**Why G6 is worth a rule of its own.** A committed `->only()` makes Pest run
that one test and report green. Every other rule on this page stops holding, the
run says nothing is wrong, and the change that did it is one word long. It is the
single most expensive thing that can be committed here.

**G11 is G6's other half, and it is two settings rather than one.** `->only()`
stops the suite reporting; a diagnostic nothing acts on lets it report and be
ignored. `failOnWarning` and its neighbours are what act — and on their own they
are narrower than they read, because PHPUnit's issue filter drops a warning,
notice or deprecation raised inside `@` before the result is assembled. The run
prints the diagnostic, counts it in the summary, and exits zero: the number a
reader sees and the number the gate reads are not the same number. Most of what
a framework raises at boot is raised under `@`, so that is not the rare case, it
is the usual one. `ignoreSuppressionOf*` on `<source>` is what puts the
suppressed ones back in front of the `failOn*` attributes, and G11 requires both
halves because either alone reads like a gate and is not one.

The settings only make PHPUnit *report* what PHP raised anyway. Nothing about
them changes what the application does, and `@` suppresses in production exactly
as it did before.

**Why D6 permits 0, 1 and 2.** Their names would be the number. Everything else
— a thirty-second timeout, a three-attempt budget, a staleness threshold in
seconds — is a decision an operator can feel, and a decision that lives as a
literal cannot be found by searching for what it means. Only method bodies are
read, so moving the number to a class constant or an enum case is both the cure
and the exemption.

H1 is not pedantry. `BackupManager` is a name that permits anything, which is how
a class acquires twenty methods; a class you cannot name precisely is usually
more than one class.

## Runtime lifecycle

| | Rule | Enforced by |
|---|---|---|
| I1 | The runtime is **persistent**: no request-scoped assumptions, no state surviving a dispatch | arch: A6, plus review |

NativePHP runs the application as a long-lived process, not a request. A static
cache that would be harmlessly rebuilt per request on a web server here survives
between screens and becomes a stale answer on someone's phone. This is the
reason A6 is absolute rather than a preference.

It is also the reason A6 is two readings rather than one. A static property is a
fact about a class and reflection answers it; a `static` inside a method body has
no property, no name the class knows and no entry in any API, and it outlives a
dispatch in exactly the same way. Memoising inside a method is the natural way to
write a cache, so the declaration reflection cannot see is the one somebody
reaches for first.

`news` keeps what each stack's stream last named past a dispatch, on purpose,
in `WhatEachStackLastNamed`: a container singleton named in the composition root
and in `MUTABLE_BY_DESIGN` rather than a static, so a screen that opens draws
the marks another screen heard. Every write to what is kept of
a stack's news recounts it, and it lives no longer than the process.
