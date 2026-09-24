# What leaves a machine, how it shares the line, and what it wakes somebody for

Everything a machine does while nobody is watching: every request lemonfiber
makes on its own account and, apart from those, what each of the stack's
services reaches; how it shares the line with the household; and what it will
tell its operator about. The code is the `N10` half of `app-modules/kernel` and
`app-modules/sdk`, drawn by `WhatLeavesHere`, `HowTheLineIsSharedHere` and
`WhatYouAreToldAbout`.

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

## The line

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N10-R4` | The line's capacity says whether it was declared or observed | `WhatTheLineCarries`, which cannot be built without `HowTheLineWasMeasured`; the screen draws it under the figures every time, because a cap decided on a declared figure is decided on a guess. The figures are said in bits a second through `HowFast`, which is what a line is sold in and what the stack's own sentences beside them say |
| `N10-R5` | Whether traffic passes through the tunnel is shown where the contract carries it | `WhetherItGoesThroughTheTunnel`, carried on the same measurement and drawn beside it |
| `N10-R6` | Reaching a cap says which of pause, throttle or continue the stack does | `AMonthlyCap`, which cannot be built without `WhatACapDoes`, drawn on the same line as the allowance — *you have reached your cap* without which one is not an answer. Where the month stands is added only where the stack counted it |
| `N10-R7` | A cap with no figure set is told apart from a cap of zero | A stack with no cap declared has no `AMonthlyCap` at all, and `HowTheLineIsShared::cap()` is a fold with an arm for that; a cap of nought is an `AMonthlyCap` and drawn as one. `HowTheLineIs` reads `null` as the first and `0` as the second, and refuses a month's standing with no cap to stand against |

A line nobody measured is its own arm too, drawn as a sentence rather than as
zeros. What a spent cap is doing, and what holding the upload back costs, are
drawn where the stack says them and not otherwise. Each client's holding, the
month's metering, the override and the household's hours are recorded as not
read yet in `WhatTheContractCarriesThatNothingReadsTest`, each with its reason;
the structured limits are too, because the stack's one sentence per direction
already carries them with the figure a share is a share of.

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

`N10-R9` is a rehearsed alert, which only a call that changes the setting
produces, and this app makes none.
`N10-R10` is what keeps running unattended, which the `hosting` envelope carries and a screen of its own reads.
