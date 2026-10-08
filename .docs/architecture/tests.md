# Tests

Part of [the rules](../../ARCHITECTURE.md#the-rules) `ARCHITECTURE.md` indexes.

| | Rule | Enforced by |
|---|---|---|
| G1 | No mocking types you do not own — hand-written fakes for our ports | arch: no Mockery on foreign namespaces |
| G2 | Every port has one contract test, run against the real adapter **and** its fake | test: every interface in a tree the coverage floor measures, its implementing classes and the names its contract file's code reaches for, compared, with a register of those still waiting |
| G3 | No test reaches the network | `Http::preventStrayRequests()` + an empty global `MockClient` + test |
| G4 | No dev dependency reachable from production code | `composer-dependency-analyser` |
| G5 | One assertion idiom: Pest's `expect()`, never PHPUnit's `assert*` | arch |
| G6 | No committed `->only(`, and no `->skip()` whose last argument is not the reason | arch |
| G7 | Every tree the coverage report measures is held to a coverage and a mutation floor, declared in the manifest nearest it, and no manifest is nearest to two | arch |
| G8 | Every port in `Modules\Kernel`, and every port a store answers, is bound, once, in the composition root, and something takes it — a port nothing is handed is a binding that resolves and changes nothing | test: the booted composition root; arch: every bound port read against what is handed one, with a register of those still waiting |
| G9 | No measured tree is below the coverage floor its nearest manifest declared | test: the `Floors` suite, over the clover report |
| G10 | No two test files declare the same helper or file-level constant name | arch: over the text of the test files |
| G11 | A diagnostic fails the run, and no setting exempts one | arch: the settings, read out of `phpunit.xml` |
| G12 | A suite standing a payload in for a stack reads it against the contract | arch: over the suites that write a wire body |
| G13 | No test's title names a requirement: the requirement's row under `status/` names the test file instead | arch: `NoRequirementIdInACommentTest`, over every `it`, `test`, `arch` and `describe` title in the PHP test trees, as it already reads every Kotlin and Swift test |
| G14 | No test's title names a rule: an architecture test carries its rule's identifier in a comment directly above it, which is where `TheRulesAreRealTest` reads it | arch: `NoRuleIdInATestTitleTest`, over every `it`, `test` and `arch` title in the PHP test trees |

**G2 is the most valuable rule on this page.** A fake that has drifted from its
adapter makes the suite green while the application is broken, and nothing else
here catches that. One contract test per port, run twice, is what makes every
fake trustworthy — and therefore what makes G1 safe to adopt.

**A port is any interface in a tree the coverage floor measures**, which is the
sentence above read at its word. The check used to build its list from the
module manifests filtered to the kernel, and two of this repository's trees are
not modules: `bridge/src` is a path package and `bootstrap/Composition` is the
composition root. `Keeps` and `Runloop` live there, each with three
implementations and one of them a fake the suite stands on, and neither was
covered — the `Keeps` fake had already drifted from its adapter in two answers
by the time anything asked. Ports genuinely waiting for a contract are named in
a register with the reason, and the count may fall and may not rise, which is
the arrangement G8 keeps for the same situation.

**An implementation is a class, and being covered means the contract's own code
names it.** An interface extending a port runs nothing against a contract, so
it is judged as the port it is rather than counted as one of the two
implementations the port it extends must have. And a class named in a comment
is not a class a contract runs: the file is parsed rather than searched, by the
whole name, because a search for a short name is answered both by a note
explaining why something is *not* covered and by any longer class name it
happens to sit inside.

**There is a half G2 cannot reach, and `G12` is it.** Running both
implementations against the same assertions proves they agree with each other.
It does not prove either agrees with the stack, because the payload they are
both run against is written by hand — by whoever wrote the reader. When the
reader looks for a field at a path the contract has not got and the payload
obliges, both sides pass and the application is broken against every real
machine.

That is not a hypothetical. A fixture put `state: pending` and `running` at the
top of the `update` payload, where the contract has `running` only under
`changelog` and allows none of `current`, `pending` or `stale` for the
top-level `state`. `Standings` read them there, three rules passed, and against
a real stack the reader would have refused every reading. So the payload is now
read against the generated types instead of against the reader — a key the
contract has not got there, a key it requires that the payload leaves out, and
a word outside a closed set it declares. The third is the one that names a
defect rather than a symptom: a reader in the wrong place often finds a field
that *exists* there under another meaning, so nothing is unknown and nothing is
missing, and only the word is wrong.

`WhatTheContractDeclares` reads the types and `WhatTheContractAccepts` judges a
payload against them. The rule is over the suites rather than inside one: a
check living in whichever suite last remembered would have `G12` claim a
guarantee that one file's assertion was carrying, which is the same defect one
level up.

**A suite is found by the body it builds, read over tokens rather than over
text.** There are two ways to build one and the rule started by seeing one of
them: a body written out whole spells the wire's version field, and a body
built positionally hands three arguments to the envelope type and spells no
field of an envelope anywhere. Ten suites wrote one the second way — every SDK
reader suite, including the one whose payload is the reason this rule exists —
and the register read as complete while half of it had never been looked at.
Tokens rather than text because every comment on this page quotes code: a
search for either mark finds this paragraph before it finds a payload.

The kind a body is built under is resolved to an envelope out of the generated
package, never from a map kept here. Each envelope declares the one kind it
reads and the generated enum holds the word, so a kind neither of them has
**fails by name** — a stand-in built under a word the contract has not got is
judged against nothing and reads as covered. What fails there is an envelope
being used as a carrier for a value a test needs out of a closure, and the fix
is a readonly class rather than an exemption.

Three things are asserted, and none of them is a figure written down. Every
stand-in judges the body it builds. There is at least one stand-in, so a mark
that matched nothing cannot read as compliance. And every envelope some reader
here unwraps has at least one judged stand-in — grepped from the `::in(` call
sites on each run, so a reader written for a new envelope tomorrow fails until
something stands a payload in for it.

Where a body is deliberately not one a stack sends — `Wire`'s version check is
answered before the payload is read, so its fixture is empty on purpose — the
fixture says so, naming the kind and the reason. Per kind and not per file: a
suite that later builds a second kind of body is asked about that one on its
own, because an exemption nobody can read is how a rule stops covering what it
was written for.

```
tests/Contract/ClockContractTest.php
  ✓ SystemClock   (the real adapter, reading the platform's clock)
  ✓ FrozenClock   (in memory, in tests/Support/Fakes)
  — the same assertions, both times
```

The file is named for the port, ends in `Test.php` because that is the suffix
PHPUnit collects, and is found by the rule from either end: a port with no
contract fails, and so does an implementation the contract does not name. Fakes
live in `tests/Support/Fakes` rather than in a module, so that nothing
reachable from production is a fake (`G4`).
