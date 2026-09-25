# Running part of it

A stack running some of its forms on purpose, and what starting a form would
come to before anybody starts it. The code is the `N18` half of
`app-modules/kernel` and `app-modules/sdk`, drawn by `WhatThisStackRuns` and
`WhatToDoWithThis`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N18-R2` | A stack running part of itself is not drawn as degraded, incomplete or a proportion of a whole | No screen draws the stack's overall condition, a count of its services, or a share of them. `WhatThisStackRuns` draws each service in its own state and each form by name |
| `N18-R4` | Before a form is started, what it would start and what it would leave out are shown, each with its reason, as a rehearsal | `Rehearsers` asks the forms read naming the form, and `WhatToDoWithThis` draws the answer above the form's verbs under a heading saying nothing has started. A profile left out says what it would need |
| `N18-R7` | The app holds no copy of the forms or what they contain | The forms are the ones the `forms` reading lists, and what a form would start is the stack's rehearsal of it. Nothing in this repository names a form |
| `N18-R8` | The app never starts or stops a form of its own accord | A form starts or stops only on a verb the operator taps on `WhatToDoWithThis`. No screen holds a clock or a threshold that sends one |
| `N18-R9` | Forms that could not be read are told apart from there being none | A forms reading that could not be read is an obstacle, drawn as one; a stack that declares no forms has its own sentence |

## Asked for, and not drawn yet

`N18-R1`, `N18-R3`, `N18-R5` and `N18-R6` need what the `status` and `preview`
envelopes do not carry: every form that brought a running service in, the
services filtered out of what is running and why, and the footprint a form's
start is estimated at. Each is raised against the contract, which is where
`N2-R14` sends a requirement the contract does not answer.
