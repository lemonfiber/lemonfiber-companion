# Moving in

What is already on a stack's machine before lemonfiber moves in beside it or
takes it over, what may be done about it, and moving in. The code is the `N7`
half of `app-modules/kernel` and `app-modules/sdk`, drawn by two screens.
`WhatIsAlreadyOnThisMachine` reads the survey once when its frame is built, and
asks about a mode, and agrees to it, from the modes list.
`HowTheServicesAreWired` opens on the services the stack runs, which are what a
run wires to each other, and starts a run and follows it to how each
connection turned out.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N7-R11` | The survey is offered, every project and service it found is shown with whether each runs and could be taken over before any mode, and a survey that could not look is told apart from one that found nothing | `HowThisStackIs` offers the survey. `AProjectStanding` carries each project and `AServiceStanding` each service, with its ports, whether it runs and whether it could be adopted. The screen draws them all before the modes. `TheSurvey` carries whether the engine looked, and the screen says it could not look rather than that the machine is empty |
| `N7-R12` | The modes are offered in the stack's order, each with what it comes to and whether it disturbs what is running, and nothing is preselected that the stack did not preselect, replacement least of all | `TheModes` keeps the stack's order and `AMode` carries each mode's word, what it comes to and whether it disturbs. `AMode::isPreselected()` is true only where the stack preselected a mode and it disturbs nothing, so a mode that stops what is running is never drawn as chosen |
| `N7-R5` | A conflict names what already holds the port | `APortHeld` cannot be built without the project holding the port and the service wanting it, and the screen draws both in one sentence |
| `N7-R6` | A port a service was moved to stays reachable | `APortMoved` carries the port each service would take instead of its own, and the screen draws every one on each reading |
| `N7-R13` | A layout that cannot hold a hardlink says why, what it costs and the remedy, and the remedy is offered only as an act of its own | `WhatLinkingCosts` carries why, what it costs, the remedy and the filesystems, or nothing where the layout links. The screen draws the remedy as words, says it is the operator's to do on their own disks, and offers nothing to press beside it |
| `N7-R14` | A service the survey found and cannot take over is named as unsupported, with the reason | The survey's `unsupported` list is read into `Unsupported`, which cannot be built without what and why, and each is drawn. A row that cannot be read is an obstacle rather than a shorter list |
| `N7-R1` | What an import could not carry is shown with the reason for each, and never subordinated to a success message | `WhatMovingInCameTo` reads the `import` envelope's `not_carried` into `TheImport::notCarried()`, each an `Unsupported` that cannot be built without what and why. `HowAMoveReads` gives it a heading and a list of its own, and the screen draws both before the stance and before what was carried |
| `N7-R2` | A move's stance is rendered as given, and *unchanged*, *pending* or *blocked* is never presented as *applied* | `WhatAMoveCarries::came()` reads each of the four envelopes' `stance` into `Stance` and refuses a word outside the four. `AMove` carries it as given, and `HowAMoveReads` draws each with a sentence of its own, only *applied* saying anything was done. The yes is offered only beneath *pending* |
| `N7-R3` | A refusal is shown with the stack's reason, and never as a generic failure or a retry prompt | A move turned away arrives as `AMove::blocked()`, which cannot be built without the stack's reason; a request the stack turned down arrives as `WhatBecameOfTheMove::refused()` with its words, which `Scouts` reads off the answer. The screen draws either with the reason and offers leaving it, and nothing to try again |
| `N7-R4` | Where the stack would take something over destructively and wants a copy first, that is said before the decision is agreed to | `WhatMovingInCameTo` reads each service adopting would open with a newer version into `AServiceAdopted`, with both versions and whether it wants a copy first, and the paths copied first into `TheAdoption::backUp()`. `HowAMoveReads` draws them under their own heading above the yes, and only on a pending answer; `AMoveAgreed` can be built only from one |
| `N7-R18` | A replacement is not presented as done while anything it replaces is still running, whatever its stance says | `TheReplacement::leftSomethingRunning()` decides it; `HowAMoveReads` draws an applied replacement that left something running under its own stance, *Not finished: something it replaces is still running*, and draws what would not stop ahead of what it stopped |
| `N7-R18` | A replacement's agreement names what it would stop, and is not carried forward | `WhatMovingInCameTo` reads the replacement's `agreement` into `TheReplacement`, `AMoveAgreed` carries it from the answer the operator was shown, and `Scouts::moveIn()` sends it as `offer` rather than a bare `confirm`; a stack whose offer has moved refuses the yes in its words, which the screen draws |
| `N7-R9` | An import that carried nothing is told apart from one that has not run | `HowAMoveReads` gives the three quiet answers of an import three sentences: a pending one has not run and draws what it would carry, an unchanged one ran and found nothing to carry, and an applied one that carried nothing says it ran and carried nothing, with what was left behind leading |
| `N7-R15` | A wiring run is offered, each connection is shown in the state the stack gave it, *skipped* is never drawn as failed, and a connection kept because the operator changed it is never drawn as wired or as drift to repair | `HowThisStackIs` offers the run and `HowTheServicesAreWired` starts it. `WhatTheWiringCameTo` reads each of the `seed` envelope's thirteen states into `WhereAConnectionStands` and refuses a word outside them. `HowTheWiringReads` gives each state a sentence of its own: *skipped* says a later run finishes it, and *drifted* says it was kept as the operator changed it |
| `N7-R16` | A connection kept because the operator changed it is said to be kept; where both values moved, what the service holds is shown beside what lemonfiber would write; nothing offers to overwrite the operator's value | `HowAConnectionEnded::conflicted()` cannot be built without lemonfiber's value, and carries the service's where the stack sent it. The screen draws both, each named for whose it is. The only thing the screen offers is another run, which changes nothing already right and keeps what the operator changed |
| `N7-R17` | A write a service rejected is shown in the service's own words, never paraphrased | `HowAConnectionEnded::failed()` cannot be built without the stack's `detail`, which `AConnectionAsShown` carries as it came and the screen draws beneath the state's sentence |
| `N7-R7` | A wiring the stack could not assess is its own answer, never drawn as sound | `WhatTheWiringCameTo` reads the run's `assessment` into `HowDriftWasJudged`. The screen says whether drift could be judged before any connection, and an unassessable run says the record could not be read rather than that nothing drifted |
| `N7-R8` | Where a wiring carries a severity, what would break and what would put it right are both shown | `WhatItWouldBreak::warning()` cannot be built without both, and `WhatTheWiringCameTo` refuses a warning that leaves either out. The screen draws the two together under the connection |
| `N7-R10` | A rehearsed wiring run is labelled as one, on the same terms as `N6-R1` | `TheWiring::rehearsed()` carries a run that only said what it would do. The screen labels it before anything else it draws about the run, and a run that wrote carries no such label |

A survey that could not be read at all is an obstacle, drawn as one, and never
an empty machine.

Each mode is asked about first, without the yes: `MovingIn::wouldMoveIn()`
asks the stack what it would come to and does nothing. The yes is
`MovingIn::moveIn()`, which takes an `AMoveAgreed`. The stack answers both
with work to follow, and `MovingIn::whatBecameOf()` follows it to where the
move stands. A mode the survey offers under a word none of the four acts
carries offers nothing to press.

A wiring run is `WiringTheServices::wire()`, sent with nothing but an
idempotency key. The stack answers with work to follow, and
`WiringTheServices::whatBecameOf()` follows it to `TheWiring`: every connection
the run attempted, in the stack's order, with what the run could not wire and
why.

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

`N7-R7` is written about a wiring. The `seed` envelope carries `assessment`
once for the whole run, not per connection, so whether drift could be judged
is said once, above every connection it is about.

## Asked for, and not drawn yet

The survey reads what adopting each service would come to and what no mode
carries, and draws neither: asking about a mode draws what that mode would
come to.
