# The rules about the rules

Part of [the rules](../../ARCHITECTURE.md#the-rules) `ARCHITECTURE.md` indexes.

| | Rule | Enforced by |
|---|---|---|
| R1 | Every documented rule has an artifact carrying its identifier, and every identifier an artifact carries is documented | test |
| R2 | Every rule that claims to be enforced refuses a planted violation | test: the `Guards` suite, run on its own |
| R3 | An architecture expectation names one symbol per rule, and every namespace it names resolves | arch |
| R4 | Every tree `phpunit.xml` measures or runs is read by the analyser, by the refactorer and by the architecture rules — and where one of those places is exempted, all of them are | arch |

**Why R4 exists.** A rule is only as wide as the list of where it looks, and
there are five such lists: the analyser's `paths`, the refactorer's
`withPaths`, the namespaces an architecture expectation resolves, the `allowIn`
entries that let a test do what production code may not, and the file list each
text-scanning rule builds for itself. Every one of them is a copy of a single
fact — what is ours — and a copy that loses a tree loses it in the one way
nothing reports: the rules resting on it keep passing, about the trees they
still read.

`bridge/src` is the case that made this a rule. It is production code, it ships
inside the application, and `phpunit.xml` holds it to the same 100% coverage
floor as everything else — *being a package is not a reason to be held to a
lower bar than the code that calls it*, as the comment beside it says. It was in
the analyser's paths, the refactorer's paths and the architecture namespaces in
none of them. A `time()`, an `Illuminate\Support\Facades\Cache::get()` and an
`echo` planted in `bridge/src/Screen.php` made `composer analyse` report *No
errors*; a non-final class named `WindowManager` holding a mutable public static
passed all 189 tests in the Arch suite. `bootstrap/Composition` was missing from
the architecture namespaces for a different reason and cost the same thing, and
`App` was in them, resolving to the formatter's own source under `vendor`.

`phpunit.xml` is what the other lists are compared against, because it is the
only one that cannot be narrowed quietly: a tree dropped from it stops being
covered, and the coverage gate is loud.

**Why R2 exists.** R1 asks whether an artifact exists. It cannot ask whether the
artifact works, and the two are indistinguishable from the outside: a rule can
be documented, tagged, registered and run on every commit while permitting
exactly what it names. Three ways for that to happen are known and all three
report a green tick — an expectation naming a namespace no autoloader
registers, a list on the left of `toBeUsedIn` that is read as *uses all of
these*, and a list holding both a function name and a namespace, which cancel
out. R3 refuses those three shapes by name. R2 is the general answer: plant the
smallest violation of every rule, run the machine that enforces it, and require
it to report.

**A fixture must break the rule's sentence, not its mechanism.** This is the
failure R2 is most likely to miss, because a fixture written from the code that
enforces a rule passes by construction.

`D3` says "no `mixed` in public signatures". Its fixture planted a parameter
with *no type at all* and asserted the analyser reported `missingType.parameter`
— which it does, and which proves something true about missing types and nothing
at all about `mixed`. Type coverage counts whether a type is declared, and
`mixed` is a declared type; `mixed $said): mixed` is 100% covered by that
measure and legal at level max. The rule read as enforced, R2 read as satisfied,
and a published signature saying `mixed` would have passed every gate.

Write the fixture from the sentence. If the sentence cannot be broken in a way
the mechanism sees, that is the finding: the mechanism is narrower than the rule
and one of the two has to move.

**Every fixture is planted in a throwaway copy of the checkout, never in the
checkout itself.** The copy is the working tree as git sees it — every tracked
file as it is on disk, uncommitted changes included, and every untracked file
git does not ignore — with `vendor` copied in beside it, under the system's
temporary directory. The analyser and the suite run inside it, and it is removed
when the run ends. The checkout is only read, so a run killed at any point leaves
it exactly as it was, and anything else can read it or run in it meanwhile. A
copy whose run was killed is swept at the start of the next run: each copy is
locked by the run using it, and the kernel releases that lock with the process,
however the process ended.

**Some violations are not a file.** A second accessor on a type that already
exists, a listener registered against another module's event, a `@param` that
stops handing on what it read — each is a change to a file this repository owns,
and for a long time each was recorded as *nothing can break this* with a
paragraph explaining that the harness could only write whole files. That was an
honest answer to the wrong question: what could not be done was the editing, not
the breaking.

`Fixture::edit` does it. It finds one piece of text in a real file and puts
something else in its place, and it refuses a piece of text it cannot find
exactly once — a fixture that matched nothing would leave the rule passing on an
unedited tree, which is the vacuous green this whole harness exists to make
impossible. The edit is made to the copy, like every other fixture, so the file
the checkout holds is never written to.

**Some violations are the run itself.** R2's own violation is a documented rule
with nothing planted under it — and planting that would mean leaving a rule
uncovered in this repository, where the run that reads it is this run. There is
no file, and there was no edit either, so R2 was recorded as *nothing can break
this* for the same reason the paragraph above was wrong: what could not be done
was the planting, not the breaking.

`Fixture::direct` is the answer. Split the judgement from the reading around it
— `rulesWithNoFixture` takes the claims and the coverage as arguments rather
than going and finding them — and it can be handed the violation on every run,
which is the whole of what a planted file buys. The test that does so asserts
the empty answer *and* a non-empty one, because everything a check says about
the clean case is equally true of a function that returns nothing whatever it is
asked.

`Q-R66 (discovery)` is the same shape and the worse failure. Every path here is
built from `Tree::root()`, which is `dirname(__DIR__, 2)` — correct for exactly
as long as that file stays two directories down. A root that has drifted cannot
be planted against, because the fixture would have to be written to a tree the
harness could no longer find; and it does not announce itself either, since
`filesUnder` answers `[]` for a directory that is not there and every rule built
on it then reads no files and reports nothing wrong. `Tree::isTheRepository`
is the judgement taken out of `root()`, so it can be asked about the parent
directory — which is precisely what a file moved one level down would produce —
and watched refusing it on every run.

`G11` is the third, and it shows the shape is not rare. The rule reads
`phpunit.xml` for the attributes that make a diagnostic fail the run, and the
settings it reads belong to the run doing the reading — so taking one out to
plant a violation changes *that* run rather than a fixture. `notTurnedOn` was
already a pure function taking the declared settings and the wanted list; it had
simply never been asked about a file where something was missing. Now it is,
including the two shapes PHPUnit treats alike and a reader does not: an attribute
deleted, and an attribute switched off on purpose.

`N1-R13` is the fourth, and it is the one where the split found something. The
rule compares five enums against the unions the generated envelope declares, and
the envelope is somebody else's file in `vendor/` — restored by composer rather
than by this harness, so a fixture that failed to clean up would leave the
installed SDK wrong. `unionIn` and `outcomesIn` now take the text rather than
going and finding it, and what that exposed was a `[]` nobody had watched them
return: a field declared twice with unions that disagree is a question the reader
cannot answer, and it answers by finding nothing rather than by taking whichever
came first. That path is the difference between comparing an enum against half a
contract and refusing to compare at all, and until now it had only ever been
described in a comment.

**The `Guards` suite runs on its own command.** `composer test` is
`pest --parallel` with `Guards` excluded, and `composer test:guards` runs it:
it boots the analyser and the whole suite again as subprocesses, which takes
minutes. It plants into a copy of the tree rather than the tree, so it can run
beside anything else — another suite, the analyser, an editor. Everything else
is deterministic in parallel and is checked that way.

A rule with no fixture fails R2. That is the part that matters — it makes *I did
not check this one* impossible to leave implicit, which is the condition the
three above needed in order to survive.

## Comments

| | Rule | Enforced by |
|---|---|---|
| K1 | A comment states the situation and why, never the history of how it came to be | review, plus an arch check for the obvious markers |
| K2 | A docblock only where a native type cannot speak | arch |
| K3 | A docblock says a thing once; a paragraph repeating another is a copy that goes stale | arch |
| K4 | A docblock describes a symbol, never another docblock | arch |

A comment is read by someone who was not there. They cannot tell a fact from a
recollection, and the recollection is the half that goes stale — so `glob()` has
no globstar is worth writing down forever, and *we used to use glob* stops being
checkable the moment its author leaves. Rationale is welcome: why a thing is the
way it is, what it costs, what would make it wrong.

The exception is a commit message and a decision record. History is the point
there, and neither is read as a description of the current code.

**A requirement identifier is not a comment's to carry.** `GOV-R6` says a
citation may not appear in one, and this repository was the org's only exception
— `sdk-php`, `sdk-ts` and `lemonfiber-web` have none in source at all, and the
Rust stack keeps a middle layer precisely so that its code needs none. The
argument is the same one K1 makes about recollections: an identifier gestures at
a page rather than saying anything, and it rots the moment that page is
superseded, silently, because nothing reads a comment.

So the sentence stays and the number moves, to the requirement's row under
[`status/`](../../status/), which names the code and the test in this
repository that hold it. A citation belongs in a
commit trailer and a pull request body, which is where the gate reads it.

This repository holds none. `NoCitationInACommentTest` is what keeps it
that way, and it is a flat refusal rather than a ratchet: a floor that has
reached the ground is a rule rather than a promise.

The rule reaches a test's own title too, in PHP as in the Kotlin and Swift under
`bridge/`: a test's name is a sentence a reader reads, and the row that cites the
requirement names the test file, so a number in the title would be doing nothing
but gesturing (G13). A native source may not name a requirement anywhere.
