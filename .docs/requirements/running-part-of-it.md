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
| `N18-R1` | What is running is shown as the forms asked for and the services they expand to, and no service without what brought it | `WhatThisStackRuns` draws the forms running, off `active_forms`, above the services, and each service with every form it runs for, off its `forms`. A service no running form asked for says so |
| `N18-R3` | A service filtered out of a form is shown as filtered with its reason, never omitted or drawn as failed | The services in `filtered` are drawn under their own heading, each with what it would need and the forms that asked for it, and are left out of the service rows, where the stack also lists them as absent |
| `N18-R4` | Before a form is started, what it would start and what it would leave out are shown, each with its reason, as a rehearsal | `Rehearsers` asks the forms read naming the form, and `WhatToDoWithThis` draws the answer above the form's verbs under a heading saying nothing has started. Each service left out says what it would need |
| `N18-R5` | A footprint is the estimate the stack declares, never a measurement | The rehearsal draws the stack's `estimated_mib` in a sentence saying it is an estimate and not a measurement, and names the services with no estimate of their own, so a short sum reads as short |
| `N18-R6` | A service several running forms include is shown once, naming every form that asked for it | Each service is one row, and its line names every form in its `forms` |
| `N18-R7` | The app holds no copy of the forms or what they contain | The forms, what brought each service, what was left out and the rehearsal are all the stack's answers. Nothing in this repository names a form |
| `N18-R8` | The app never starts or stops a form of its own accord | A form starts or stops only on a verb the operator taps on `WhatToDoWithThis`. No screen holds a clock or a threshold that sends one |
| `N18-R9` | Forms that could not be read are told apart from there being none | A forms reading that could not be read is an obstacle, drawn as one; a stack that declares no forms has its own sentence |

## Asked for, and not drawn yet

`N18-R2` asks that a stack running part of itself is not drawn as degraded,
incomplete or a share of a whole. The listing draws no count or share, and
draws a filtered service as left out rather than absent. It also draws the
stack's own `condition`, which the core works out over every service it lists,
the filtered ones among them as absent, so a stack running part of itself on
purpose can read as `partial`. `N2-R14` keeps this app from putting a condition
of its own in its place; the calculation is the core's to change.
