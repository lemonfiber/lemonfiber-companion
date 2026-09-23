# What leaves a machine

Everything a machine says to the world while nobody is watching: every request
lemonfiber makes on its own account, and apart from those, what each of the
stack's services reaches. The code is the `N10` half of `app-modules/kernel` and
`app-modules/sdk`, drawn by `WhatLeavesHere`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## Connections

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N10-R1` | Connections lemonfiber makes are shown apart from connections the installed services make, and the two are never merged into one list | `WhatLeavesThisMachine`, which holds `OurRequests` and `TheirRequests` as two types with no accessor returning both, so nothing downstream can be handed one as the other. The screen draws them under two headings |
| `N10-R2` | Each connection lemonfiber makes is shown with its purpose, its destination and the switch that turns it off | `ARequestOfOurs`, which cannot be built with its purpose, what it sends or its switch blank — `RequestSaysNothing` names the one that was. Where it goes is `WhereItGoes`, and *nowhere configured* is drawn as that, separately from `WhetherItIsAllowed`: a request can be allowed with nowhere to go |
| `N10-R3` | Where turning a connection off has a cost, that cost is shown with it | The cost is a required word of `ARequestOfOurs` and drawn on every row, because turning something off is the decision the list exists to inform |
| `N10-R12` | Connections that could not be read are told apart from there being none | `WhatWasFoundLeaving`, whose arms are the two lists or an obstacle. `WhatLeaves` refuses a row it cannot read rather than dropping it — a connection dropped is one the screen says this machine does not make |

A service the stack has no record of is its own arm of `ARequestOfTheirs`,
carrying no destination at all. The stack fills such a row's destination and
purpose with placeholder words, and `recorded` is what says so; read as a
destination, *nobody knows* would come out as *reaches nothing*. The screen
says it in its own words instead.

## Asked for, and not drawn yet

`N10-R4` to `N10-R7` are the line — its capacity, the tunnel, what a cap does
and a cap with no figure — and read the `bandwidth` envelope. `N10-R8` and
`N10-R9` are alert presets and rehearsals, from the `alerts` envelope.
`N10-R10` is what keeps running unattended, which the `hosting` envelope carries and a screen of its own reads.
`N10-R11` asks that nothing here raise an alert of its own, and nothing here
raises anything.
