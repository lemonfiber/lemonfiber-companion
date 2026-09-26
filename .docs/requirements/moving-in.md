# Moving in

What is already on a stack's machine before lemonfiber moves in beside it or
takes it over, and what may be done about it. The code is the `N7` half of
`app-modules/kernel` and `app-modules/sdk`, drawn by
`WhatIsAlreadyOnThisMachine`: one screen, reading the survey once when its
frame is built.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N7-R11` | The survey is offered, every project and service it found is shown with whether each runs and could be taken over before any mode, and a survey that could not look is told apart from one that found nothing | `HowThisStackIs` offers the survey. `AProjectStanding` carries each project and `AServiceStanding` each service, with its ports, whether it runs and whether it could be adopted. The screen draws them all before the modes. `TheSurvey` carries whether the engine looked, and the screen says it could not look rather than that the machine is empty |
| `N7-R12` | The modes are offered in the stack's order, each with what it comes to and whether it disturbs what is running, and nothing is preselected that the stack did not preselect, replacement least of all | `TheModes` keeps the stack's order and `AMode` carries each mode's word, what it comes to and whether it disturbs. `AMode::isPreselected()` is true only where the stack preselected a mode and it disturbs nothing, so a mode that stops what is running is never drawn as chosen |
| `N7-R5` | A conflict names what already holds the port | `APortHeld` cannot be built without the project holding the port and the service wanting it, and the screen draws both in one sentence |
| `N7-R6` | A port a service was moved to stays reachable | `APortMoved` carries the port each service would take instead of its own, and the screen draws every one on each reading |
| `N7-R13` | A layout that cannot hold a hardlink says why, what it costs and the remedy, and the remedy is offered only as an act of its own | `WhatLinkingCosts` carries why, what it costs, the remedy and the filesystems, or nothing where the layout links. The screen draws the remedy as words, says it is the operator's to do on their own disks, and offers nothing but asking again |
| `N7-R14` | A service the survey found and cannot take over is named as unsupported, with the reason | The survey's `unsupported` list is read into `Unsupported`, which cannot be built without what and why, and each is drawn. A row that cannot be read is an obstacle rather than a shorter list |

A survey that could not be read at all is an obstacle, drawn as one, and never
an empty machine.

## What the contract does not carry

`N7-R11` also asks for each service's configuration source and library
location. A service in the survey carries its name, its ports, whether it runs
and whether it could be adopted, and nothing about where it keeps its settings
or its library. Nothing is inferred in their place.
`WhatTheContractDoesNotCarryTest` holds the requirement against the survey's
shape.

`N7-R13` asks that the remedy be offered as an act of its own. No action the
stack offers carries it out, so there is no act to offer: the remedy is drawn
as words and nothing more.

## Asked for, and not drawn yet

`N7-R1` to `N7-R4` are about carrying out a mode: what an import could not
carry, the stance of a move, a refusal, and a copy wanted before something is
taken over destructively. The survey already reads what adopting each service
would come to and what no mode carries, and draws neither until a mode can be
chosen here.

`N7-R7` to `N7-R10` and `N7-R15` to `N7-R17` are about wiring the services
together, which is the `seed` action, and it is not offered here.
