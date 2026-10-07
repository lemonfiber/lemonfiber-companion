# Where things go

Part of [the rules](../../ARCHITECTURE.md#the-rules) `ARCHITECTURE.md` indexes.

```
bootstrap/Composition/    the composition root, and nothing else
app-modules/<name>/
  composer.json           declares the module's kind, which generates its rules
  src/Api/                what other modules may name
  src/Api/Commands/       one public method each, returning Outcome
  src/Api/Queries/        one public method each, never returning Outcome
  src/Internal/           unreachable from anywhere else
  src/Internal/Store/     what the module keeps, reached only through its port (A11)
  src/Internal/Presenters/   pure: data in, view model out
  src/Internal/ViewModels/   what a template reads, deciding nothing
  resources/views/        the screens this module navigates to
  database/migrations/    the tables its store keeps, named for the module (A10)
  tests/                  mirroring src/, one directory level for one
lang/<locale>/<module>.php   every sentence a person reads
bridge/src/               the plugin's own PHP, held to the same bar (R4)
bridge/tests/             its suite, which is a suite like any other (R4)
tests/Arch/               the rules
tests/Templates/          Blade, which no analyser reads
tests/Contract/           one suite per port, run against the adapter and the fake
tests/Feature/            the composition root
tests/Guards/             every rule, shown to refuse a violation
tests/Support/            what the five above share
phpstan/Rules/            the rules that are easier to write than to find
```

| | Rule | Enforced by |
|---|---|---|
| W1 | `bootstrap/` holds no class but the composition root | arch |
| W2 | A module's `src/` declares only its own namespace | arch |
| W3 | Root `tests/` holds only the suites; root `resources/views/` holds no Blade | arch |
| W4 | A module's tests are namespaced for that module | arch |
| W5 | A file with no namespace imports no global name — the warning it raises fails the run silently | arch |
| W6 | A source file declares one class, and it is the one its path names | arch: over the declarations of every file a class-name rule reads |
| W7 | Every reader puts its envelope through the wire gate before reading the payload | arch: over the SDK module's own sources |
| W8 | A reader hands back nothing the payload did not carry, and the gap it found is raised against the contract and kept until it is answered | phpstan for the coalesce that substitutes a default, plus arch over the register of gaps that must still be gaps — two clauses of one requirement, and a row naming one kind would leave the other unwatched |

**Why `W6` is not covered by the rule above it.** `Q-R66` asserts that a rule
found subjects to judge, and that cures the three cases this repository has
actually had — `L1` named two directories that did not exist, `F2` a namespace
with no classes in it, `F5` five tag names no screen used. In each the selected
set was wrong, so counting it catches them.

A second class in a file is a different failure and a floor does not reach it.
Discovery is working: `Module::classNames()` asks `Imports::declaredName()`
which class a path names, gets an honest answer about the first one, and returns
a set that is exactly right. The file's second class was never a candidate to be
counted, so the count is correct and the class is still judged by nothing — not
`every class is final`, not the readonly rule, not the boundary rules. The
question that catches it is not *did this rule select anything* but *is there
anything this rule could not have selected*.

It is also a live fault rather than only a blind spot. PSR-4 maps the second
class to a file that does not exist, so it resolves only because something
already loaded the sibling it shares a file with — and the day a reference names
it first, the autoloader has nowhere to look.

**These are not tidiness.** Every rule on this page is derived from a path or a
namespace: the kind rules read `app-modules/<name>/composer.json`, the published
surface rule reads `Api` against `Internal`, H4 pairs a test with its source by
replacing one path segment. A file in the wrong place is a file the rules
governing its neighbours do not reach — and nothing says so, because a rule that
finds no files reports a green tick.

`bootstrap/Composition/` is the sharpest case. The permission to name both a port
and an adapter is granted by path, along with an exemption from A2, A3 and A4, so
a class put there acquires all of it without anyone deciding it should. `W1` is
what keeps the rest of `bootstrap/` free of classes, and there is no `app/`: the
one class NativePHP names as `App\Providers\NativeServiceProvider` is mapped
into `bootstrap/Composition/NativePHP/Admitting/` by PSR-4, because the vendor
fixes the class name and not the path.
