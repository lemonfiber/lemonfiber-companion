# Language, runtime and security

Part of [the rules](../../ARCHITECTURE.md#the-rules) `ARCHITECTURE.md` indexes.

## Language

The application ships **`en` and `nl`**, and no more. Every other rule here is
about code a developer reads; these two are about the only text the operator
ever sees.

| | Rule | Enforced by |
|---|---|---|
| L1 | Text a person reads comes from the translator | phpstan: own rule, over everything on the way to a screen that is not a refusal |
| L2 | Every locale carries the same keys, none empty and none equal to its key | test |
| L7 | Every catalogue key the application names is a key the catalogue holds — the literal ones read out of the sources, the derived ones asked of each enum that builds them — and every line the catalogue holds is one something shows | test: three, one per direction plus one for derived keys |
| L8 | A tab, a menu item and a menu group name what they open in at most three words, and never as a sentence | arch: every line of the navigation catalogue, in every language |

**The line between the two kinds of text.** A refusal on screen, an empty state,
a notification body — a person reads these, so they are keys in
`lang/<locale>/<module>.php` and reach the screen through `__()` with a
replacement array. An exception message, a log line, a PHPStan rule's message —
a developer reads these, so they are `sprintf` and are **never** translated.
Translating a stack trace helps nobody and makes the one audience who needs it
read it in a language they did not choose.

**Why L2 is the guard that matters.** A key missing from `nl` fails nothing.
Laravel looks it up, misses, falls back to `en`, misses again, and renders the
key — so a Dutch device shows `health.unreachable` where a sentence belongs and
ships that way. Nothing else in this repository can see that, and a comparison
of the two catalogues is cheap. The reverse direction is checked too: a key in
`nl` with no `en` counterpart is a typo or text nothing shows any more.

**Why L7 exists beside it.** L2 compares the catalogues against each other, so
it is blind to the case where they agree and are both wrong: a key that is in
neither, because somebody mistyped it at the call site or renamed the line and
not the reader. That renders the key on every device in every locale. L7 reads
the keys out of the source — production PHP and Blade alike, since a template is
not PHP any analyser reads — and asks the catalogue for each one.

The cure is usually not a corrected literal. Where a key belongs to a closed set,
derive it from the case: `Permission::reason()` builds `device.camera_reason`
from `Permission::Camera`, so there is one spelling, and the rule that checks the
catalogue is checking the string the application actually uses. A literal at the
call site is a second spelling, and the second spelling is the one that drifts.

## What the analyser cannot see

Every other rule here depends on the analyser being able to read the code. These
are the constructs that take something out of its view, and each one disables
every rule that would otherwise have applied to whatever it exposes.

| | Rule | Enforced by |
|---|---|---|
| P1 | No `__get`, `__set`, `__call`, `__callStatic` — `__invoke` stays | phpstan: own rule |
| P2 | No variable variables and no dynamic class, method or property name | phpstan: own rule |
| P3 | No `func_get_args()`, no `#[AllowDynamicProperties]` | phpstan `disallowed-calls` |
| P4 | No reflection in production code | phpstan `disallowed-calls`, scoped by path |
| P5 | A field only a trait reads is protected, never private: read alone, the class shows a private field nothing reads | arch: every class's fields read by the traits it uses, against where the class reads them itself |

## The runtime is one long-lived process

`Runtime::boot()` runs once and `Runtime::dispatch()` handles every interaction
after it. Nothing between two screens resets. That is why these are stricter
here than in a web application, where the same calls are undone by the process
ending a few milliseconds later: here a change made on one screen is still in
force on the next one, and on the one after that, until the operator force-quits
an application they have no reason to think is broken.

| | Rule | Enforced by |
|---|---|---|
| Q1 | No runtime configuration mutation — `ini_set`, `setlocale`, `date_default_timezone_set` and their neighbours | phpstan `disallowed-calls` |
| Q2 | No superglobals | phpstan `disallowed-superglobals` |
| Q3 | No `static::` or `new static()` — every class is final | phpstan: own rule |
| Q4 | No `echo`/`print` from a module | phpstan: own rule |

## Text a person reads

The application ships two locales, so every one of these is a bug that exists
today rather than a precaution against one.

| | Rule | Enforced by |
|---|---|---|
| L3 | Multibyte-safe string functions only | phpstan `disallowed-calls` |
| L4 | No date formatted by a literal format string | phpstan `disallowed-calls` |
| L5 | No number formatted with separators written into the call | phpstan `disallowed-calls` |
| L6 | No byte-order sorting of text a person reads | phpstan `disallowed-calls` |

`strlen` counts bytes. A Dutch service name with an accent in it is longer in
bytes than in characters, so truncating one with `substr` splits a character and
the screen renders a replacement glyph. Dutch writes `1.234,5` where English
writes `1,234.5`. Byte-order sorting puts every accented character after `z`.
None of these is theoretical once the second locale exists.

## Security and supply chain

| | Rule | Enforced by |
|---|---|---|
| S1 | The dangerous, execution, insecure and non-timing-safe call bundles are on | phpstan `disallowed-calls`, four shipped bundles |
| S2 | No package with a published advisory resolves | `roave/security-advisories` + `composer audit` |
| S3 | TLS verification is never weakened | phpstan: own rule (array items) + `disallowed-calls` (the call and curl forms) + test over `config/` and every `.env*` (N1-R21 — the flag may not exist) |
| S4 | `Illuminate\Encryption\Encrypter` is named in the `seal` module and nowhere else | arch: `ModuleBoundariesTest`, the class only used in `Modules\Seal` |
| S5 | The `Crypt` facade is named in the `seal` module and nowhere else | arch: `ModuleBoundariesTest`, the facade only used in `Modules\Seal` |
| S6 | `hash_hmac` is called in the `seal` module and nowhere else | arch: `ModuleBoundariesTest`, the function only used in `Modules\Seal` |

**Q3 and Q4 are about shapes that belong to a different codebase.** Late static
binding resolves to the class it is written in, because every class here is
final and there is no subclass for it to find — what it actually does is tell
the next reader that one exists. And there is no output stream: the runtime
publishes a binary element tree, so an `echo` produces a malformed frame rather
than a visible mistake, diagnosed on a device with no console.

**Why S3 is a rule and not a review note.** ADR-0018 pins a stack's certificate
by a fingerprint taken from the pairing material, which is what makes a stack on
a home network safe to talk to without a public certificate authority. Every
spelling of the verify-off switch — `'verify' => false`, `'verify_peer' =>
false`, `'allow_self_signed' => true`, `CURLOPT_SSL_VERIFYPEER => 0` — is one
line in an options array that reads like configuration. It does not relax the
check; it removes the only one there is, and leaves the pin being compared by
code nothing reaches.

`roave/security-advisories` is a conflict-only package: it carries no code and
fails resolution when a dependency matches a published advisory, so the failure
arrives at `composer update` rather than at `composer audit` in CI a week later.

**Why S4 to S6 put sealing in one module.** Everything the phone keeps is
sealed before a store sees it, under a key the platform's secure storage holds,
and `Modules\Seal\Api\EncrypterSeal` is the one class that does it. The other
key within reach is the framework's own, which NativePHP keeps on Android as a
plain file in the application's storage beside the database — so an encrypter
built anywhere else is most likely built with the key that sits next to what it
locks. `hash_hmac` is the same argument for a stack's
identity: a second keyed hash is a second place an identity can be written
down, under whatever key was to hand.
