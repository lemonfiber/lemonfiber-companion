# What leaves a machine, and what it wakes somebody for

Everything a machine says to the world while nobody is watching: every request
lemonfiber makes on its own account, and apart from those, what each of the
stack's services reaches — and what it will tell its operator about. The code is
the `N10` half of `app-modules/kernel` and `app-modules/sdk`, drawn by
`WhatLeavesHere` and `WhatYouAreToldAbout`.

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
| `F7-R9` | A plugin's declared hosts appear in the account of what leaves this machine, attributed to the plugin | `ARequestOfTheirs` carries who put the service there on both arms — the unrecorded one is where a plugin's service lands — and `WhatLeaves` reads it through `Attributions`. The screen marks every service that is not the stack's own and says once under the list what an unmarked row is |

A service the stack has no record of is its own arm of `ARequestOfTheirs`,
carrying no destination at all. The stack fills such a row's destination and
purpose with placeholder words, and `recorded` is what says so; read as a
destination, *nobody knows* would come out as *reaches nothing*. The screen
says it in its own words instead.

## What the operator is told about

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N10-R8` | An alert preset is shown with what it means and with the exceptions the operator has made | `WhatTheOperatorIsTold`, which cannot be built without the preset's name or what it means — `AlertSaysNothing` names the one missing. Each exception is `AnEventSetApart`, heard or kept quiet by `WhetherItIsHeard`, and `SetApart` refuses one event set apart twice, because whichever answer a screen drew, the other is the one the machine acts on |
| `N10-R11` | The app raises no alerts of its own here; what is configured is the core's | `Telling` reads and has nothing that writes, and `WhatYouAreToldAbout` says once that the setting is changed at the machine. Nothing on the screen sends a notification |

The stack's answer also says whether the call that produced it changed the
setting and whether it only rehearsed. This app makes no call that changes it,
so both are recorded as unread in `WhatTheContractCarriesThatNothingReadsTest`
rather than drawn: a rehearsal label on a plain reading would describe
something that never happened.

## Asked for, and not drawn yet

`N10-R4` to `N10-R7` are the line — its capacity, the tunnel, what a cap does
and a cap with no figure — and read the `bandwidth` envelope. `N10-R9` is a rehearsed alert, which only
a call that changes the setting produces, and this app makes none.
`N10-R10` is what keeps running unattended, which the `hosting` envelope carries and a screen of its own reads.
