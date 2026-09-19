# The shape of a capability

Every capability in this bridge is built the same way, and the shape is the
point rather than a convention. A decision that lives inside the platform shim —
in the Kotlin that touches a `NotificationManager`, in the Swift that touches a
`UNUserNotificationCenter` — cannot be reached without a device, and a decision
that needs a handset to exercise is a decision nobody exercises.

## Four parts, and none of them optional

**1 — A rule, with no framework in it.**

A `data class` in Kotlin and a `struct` in Swift, taking the facts the platform
reports as plain values and answering what they mean. No `android.*`, no
`UIKit`, no `Foundation` beyond what a string needs. It runs on a JVM in two
seconds and in SwiftPM in one.

`CaptureRule` and `LockRule` are the model. The rule is where *never asked* is
told from *refused*, where a grace period is arithmetic, where the awkward case
is named. It is the part a reviewer reads to find out what the app believes.

**2 — The same rule again, on the other platform.**

Line for line, case for case, in the same order. Two platforms quietly
disagreeing is what this catches, and a disagreement is only visible if both
sides answer the same questions. Where one platform answers directly what the
other has to reconstruct, the input is carried anyway and the page says why — a
rule that exists on one side only cannot disagree visibly.

**3 — A shim that does nothing but ask and apply.**

The Kotlin or Swift that touches the framework: gather the facts, hand them to
the rule, act on the answer. It holds no branching of its own worth testing, and
it is excluded from both test harnesses rather than stubbed, because a stub of a
window is a test of the stub.

**4 — A PHP facade and a contract test over it.**

One class in `bridge/src/`, and one suite in `tests/Contract/` run against both
that class and the stand-in every other test uses. The two can then never drift:
a promise the adapter keeps and the stand-in does not is a promise every screen
test is lying about.

## What "done" means

A capability is finished when all of these are true:

- the rule passes its own tests on both platforms, at the coverage floor the
  harnesses hold (100%);
- the contract suite passes against the adapter and against the stand-in;
- **the build carries a handler for every function the manifest declares**, on
  both platforms, checked rather than assumed;
- the Android half has been watched working on a handset and the iOS half on a
  phone, with what was seen written on the capability's page;
- nothing it does writes key material to a log line, a breadcrumb or a cache
  file, and a test says so rather than a reviewer.

## Two rules about what it may not do

**Nothing secret is written where it can be read.** Not in a log at any level,
not in a crash breadcrumb, not in a cache file, not in an exception message
carrying the value it failed on. The shim logs *that* a write failed and the
reason the platform gave; never the key, the value, or an identifier that
resolves to a stack. There is a test per capability for this, and it reads the
source rather than trusting the author.

**No requirement identifier in a comment**, on either platform. The sentence
explaining why the code is the way it is stays in the code; the number lives on
the capability's page under this directory, which is what these pages are for.

## Where the states come from

The application reasons in types — `WhyNothingWasScanned`, `WhySessionCannotBeKept`,
`WhyNothingIsShown`, `HowThePairingWent`. A capability answers with enough detail
that those can be constructed honestly, rather than with a boolean the adapter
has to guess behind. Each capability page lists the states it keeps apart, and
each of those is a distinction a screen says a different sentence about.
