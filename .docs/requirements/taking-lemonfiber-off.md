# Taking lemonfiber off

Taking lemonfiber off a machine: four removals, each read on its own, agreed to
against the reading on the screen, and followed to what it did. The screen opens
on reading the first, stopping everything, which removes nothing; the others are
read once chosen. The code is
`TakingItOffThisMachine`, reached from the stack's own screen, the
`TakingLemonfiberOff` port and its adapter `Dismantlers`, and the `Uninstalls`
reader. Taking somebody out of the household is the other half of `N13`, and
is not kept here.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## What is answered

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N13-R4` | An uninstall states whether its account of what would be removed is complete, and names what could not be read | `Uninstalls` reads `confidence` into `HowMuchWasRead`, whether it is complete as the stack said it and each sentence of what could not be read in the words of whatever refused. The screen says which it is straight after the removal's name, before what it removes, and lists what could not be read beneath it |
| `N13-R5` | An incomplete account is never presented as a complete one | An incomplete reading is drawn with a sentence of its own, emphasised, that says there is more on the machine than is listed. What goes is said as what *could be read* of it, and a line whose size could not be read says so rather than counting as nothing |
| `N13-R6` | What lemonfiber did not put at a managed location is shown separately, with its extent | `foreign` is read into `WhatIsNotLemonfibers`, each place with its files and bytes, and drawn under its own heading, apart from what goes, saying it is not counted as lemonfiber's |
| `N13-R7` | Each operation carries its own agreement naming its scope, never carried forward from another screen or operation | `AnUninstallAgreed` can be made only from a reading, and sends that reading's `agreement` as the action's `offer`, with the tier it names. The screen holds the reading only while it is in front of the operator; choosing another removal, or reading again, lets go of the reading and anything acknowledged with it, and a yes is never sent a second time on its own. The person's half is below |
| `N13-R9` | A refusal is shown with the stack's reason, and not as an error to retry | `Dismantlers` hands on the stack's own sentence where it refuses, as `WhatBecameOfTheUninstall::refused()`. The screen draws it as the stack's answer and offers choosing a removal again, never *ask again* |
| `N13-R10` | A rehearsed removal is labelled as a rehearsal | `state: confirmed` is read as `WhereTakingItOffGot::rehearsed()` and drawn with a sentence saying nothing was removed. Asking for one is below |
| `N13-R11` | An operation that could not be read is told apart from one that has not run | A yes the stack has no outcome for, or one that met something on the way, is drawn as whether it was taken off not having been read, which is not the same as it not having happened. Before a yes, the same states say the reading could not be read. `again()` asks after the same work where there is some, and otherwise reads the removal afresh |
| `N13-R12` | The library and the downloads are removed only on their own, and the agreement states how much data goes | The library is offered under a heading of its own on the chooser and read on its own. Its yes names what goes in bytes, and its reading says it is never taken with another |
| `N13-R13` | A data location on a network share or a drive that unplugs is shown before the yes, and acknowledged apart from it | The stack's `volume` sentence is drawn under its own heading, with an acknowledgement of its own. Until it is given the yes is disabled, the screen sends nothing, and `AnUninstallAgreed` refuses a reading naming a volume nobody acknowledged |
| `N13-R14` | Downloads still coming down are named with how far along, and waiting is offered beside going ahead, which says it interrupts them | Each is drawn with its progress. The yes is then two, waiting and going ahead now, neither chosen for the operator; going ahead is labelled as interrupting them, and the choice is sent as the action's `wait` |
| `N13-R15` | Before configuration goes, what the stack says about the copy it takes first is shown, and no copy is promised where it says none | The stack's `backup` sentence is drawn as it said it. Where it said none, the screen says no copy is taken and offers taking one first |
| `N13-R16` | A line holding a credential is marked, never shown; the credentials destroyed are listed by name after, complete or partial | `secret` is carried as whether the line holds one, and the value never is. `credentials` is read on a complete and a partial removal alike and drawn by name under its own heading |
| `N13-R17` | A line the stack keeps is shown as kept with why, apart from what goes, and not counted in what is freed | A line with `kept` is drawn under its own heading with the reason. What goes counts only `manifest.bytes`, which the stack counts from what goes |
| `N13-R18` | What lemonfiber cannot take is listed with why and how by hand, found told apart from not found; after a partial removal each thing left is named with what the machine said and how to finish it | `outside` is read into `WhatItCannotTake`, each drawn with why, how by hand, and a sentence for whether the survey found it. `left` is read on a partial removal into `WhatWasLeftBehind`, each drawn with the machine's words and how by hand; a partial removal is never drawn as complete |
| `N13-R20` | Before configuration is agreed to, the app says it removes what admits this app; after, that reaching the machine again needs it set up and paired again; never a refused credential; the pairing kept | The configuration's reading says, above its yes, that the session ends and the machine stops offering what this app reaches. Once it finished, or where the stack has no outcome for it, the screen says reaching it again needs lemonfiber set up and the phone paired again, and offers nothing more. An answer that could not be read after that yes is drawn as that, never as a credential refused; the session is let go of and the pairing stays |

Every act answers with a handle, followed at the cadence the screen states
until the removal arrives. `N1-R2` and `N1-R41` hold as they do across the app:
the reading is asked for through the session this device holds, and nothing
agreed to outlives the screen.

## Asked for, and not answerable yet

`WhatTheContractDoesNotCarryTest` holds this against the `uninstall`
envelope's shape, which the answer arriving changes.

`N13-R10` is answered for what arrives. The `uninstall` action takes `dry_run`,
and the screen does not ask for a rehearsal yet; a stack that answers one is
drawn as one.
