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

## This directory is not the spec, and answers no question about it

A requirement absent here is not a requirement that does not exist. These pages
cover what somebody has written up, and the spec is far larger than the part of
it this app has had reason to touch.

It matters because the question gets asked in the other direction. A field
arrives on the wire, nothing reads it, and somebody has to decide whether
anything should — `WhatTheContractCarriesThatNothingReadsTest` is where that
decision is written down, and *no requirement asks for this* is one of its two
legitimate answers. Deciding that by searching this directory, or by searching
`N1`–`N4`, has now been wrong twice: `F7-R3` requires a setting's origin shown
beside it wherever it is shown, and `E5-R6` requires the changelog in the
stack-update flow. Both bind a surface this app has, neither is in area `N`,
and neither had a page here until the field that needed it turned up.

So the answer is searched for in the spec, across every area, before it is
written down as absent. Then the page is added here, which is how the next
lookup stops being wrong.

## The pages

| Page | What it covers |
|------|----------------|
| [reaching-a-stack.md](reaching-a-stack.md) | Every call this app makes to a machine, and everything it reads back |
| [pairing-a-machine.md](pairing-a-machine.md) | How this device comes to know a stack, and what it decides on launch |
| [running-with-nothing-else-running.md](running-with-nothing-else-running.md) | The stand-ins: the whole app on a device with no stack near it |
| [what-a-screen-owes.md](what-a-screen-owes.md) | The rules a screen obeys whatever it is about |
| [the-screens-themselves.md](the-screens-themselves.md) | What each screen is for, and the requirement that put it there |
| [the-model-of-a-machine.md](the-model-of-a-machine.md) | The types this app thinks in: stacks, pairing, retained state, actions |
| [what-a-machine-says.md](what-a-machine-says.md) | The values read back: verdicts, services, repairs, releases, requests |
| [what-leaves-a-machine.md](what-leaves-a-machine.md) | What a machine does unwatched: what it sends, how it shares the line, and what it wakes somebody for |
| [how-full-a-machine-is.md](how-full-a-machine-is.md) | How full a machine is, where the room went, and each download with where it stands |
| [where-an-item-got-to.md](where-an-item-got-to.md) | One item followed through the services: how sure, how far, what was tried, and what of a series is here |
| [what-the-words-mean.md](what-the-words-mean.md) | lemonfiber's words, each with its glosses and what else it is called |
| [what-is-running-here.md](what-is-running-here.md) | Which version of lemonfiber runs, how it was installed, and what moving it would take |
| [what-a-machine-keeps.md](what-a-machine-keeps.md) | What the stack keeps on its machine, where and why, and the copies the machine holds |
| [what-was-done-here.md](what-was-done-here.md) | What a machine has changed about itself, how far back that record goes, and where every service it runs comes from |
| [what-this-device-keeps-to-itself.md](what-this-device-keeps-to-itself.md) | Permissions, notifications, the lock, and what never leaves |
| [what-the-rules-keep.md](what-the-rules-keep.md) | The requirements nothing in a module answers, because a rule reading this repository from outside it does |

`tests/Arch/NoRequirementIdInACommentTest.php` is what keeps a number from
coming back: no comment in this repository names a requirement, and the failure
names the file and the line.
