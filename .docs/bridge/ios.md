# iOS

## Where things are

```
bridge/resources/ios/              what actually ships, and what SwiftPM compiles
  <Capability>Rule.swift           the decision, no UIKit in it
  <Capability>Functions.swift      the shim: gather, ask the rule, apply
bridge/Package.swift               the package: one library target, one test target per rule
bridge/ios/Tests/<Rule>Tests/      one test file per rule
```

`Package.swift` excludes every file touching a window or a notification centre
from the library target, for the reason the Kotlin harness excludes its
equivalents. The test targets depend on the library, so they see the rules and
nothing else.

## The gates

`swift build`, `swift test`, and `swift-format` in lint mode, with the same
coverage expectation the Kotlin side holds.

## What iOS makes easy, and what that costs

**iOS answers the notification question directly.** `UNAuthorizationStatus` has
a `.notDetermined` of its own, so the state Android has to reconstruct arrives
here already separated.

The rule is written anyway, takes the same four inputs, and ignores the ones it
does not need. That looks like waste and is the opposite: the failure these
paired files exist to catch is the two platforms quietly disagreeing about
whether somebody has already been asked, and a rule that exists on one side only
cannot disagree visibly. The cost is a few unused inputs; the thing bought is
that a disagreement becomes a failing test rather than a support conversation.

**Keychain accessibility is a choice iOS makes you make.** When an item may be
decrypted — while unlocked, after first unlock, only on this device — is a
decision with security consequences and no safe default, and Android has no
equivalent knob. It belongs in the capability's contract rather than in the
shim, so that both platforms are answering the same question even where one of
them answers it trivially.

## Untested until a phone is attached

Everything here compiles and the rules are unit-tested, and until the work is
run on a handset that is all that can honestly be claimed. Each capability page
records what was watched on which device; a page with nothing under iOS means
nobody has looked.
