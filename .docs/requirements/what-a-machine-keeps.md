# What a machine keeps, and the copies it holds

What the stack keeps on its machine, where each thing is and why, the copies of
itself the machine holds, taking a copy and putting one back. The code is the
`N6` half of `app-modules/kernel` and `app-modules/sdk`, drawn by
`WhatThisMachineKeepsHere`, `TakingACopyHere` and `PuttingACopyBack`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N6-R1` | A rehearsed run is labelled as a rehearsal wherever its result is shown, and never described in the past tense | Putting a copy back always rehearses first: `PuttingACopyBack` heads the listing *a rehearsal: nothing has been put back*, and every sentence under it says what *would* happen. The listing and the report of a restore are drawn from different values, `WhatPuttingItBackWouldShow` and `HowPuttingItBackWent`, so one cannot be drawn as the other. A copy's report carries `WhetherItWasRehearsed`, and `HowACopyReads` words a rehearsed one in the conditional in every sentence — what it would copy, remove, come to and hold — under its own heading. `TakingACopyTest` and `PuttingACopyBackTest` assert both |
| `N6-R2` | The scope of a copy or a restore is stated before it starts and again on its result: the whole stack, one service, or an existing project | `ScopeOfACopy` has the three arms and no default, and `HowAScopeReads` is the one place each is worded. Taking a copy asks first, naming the scope `ACopyAsked` would send; the copy while it runs and its report both name it again. A restore's listing names the scope before the yes, and its report after. An existing setup is named with its project and the host paths it read |
| `N6-R3` | Copies removed by taking a new one are reported with that copy | `ACopyTaken` cannot be built without `TheCopies` it pruned, and `TheCopyTaken` refuses a report whose list is missing or holds something that is not a name. The report says how many it removed and names each; one that removed none says every other copy is still kept |
| `N6-R4` | A running copy shows how far it has got, and a paced one is told apart from a stalled one | Half kept. The contract carries how far a copy got only once it has finished: the `job` envelope a running copy answers with holds its action and its name and nothing else, so while it runs the screen says it is being taken, that the stack says nothing of its progress until it finishes, and how often it asks. It asks on `HowOften`'s stated cadence while the copy runs. The finished report's `pace` is drawn as the stack gave it: `HowACopyPaced` carries the bytes, the budget and the stack's own verdict, and a copy past the budget is said to be slow because of what is kept rather than a fault. What a running copy has got through waits on the event stream or the contract |
| `N6-R5` | Where a restore put data somewhere other than where it came from, the app says so | `WhereTheDataGoes`, whose two arms are back where it was and elsewhere with both roots. The listing says where the data would go before the yes; the report says where it went. The yes re-points the data only where the listing said it would move, so what is agreed is what was shown |
| `N6-R7` | What the stack is holding is shown with why, and nothing the contract marks secret has its value rendered | `SomethingKept`, which cannot be built without what it is, where, why and `WhetherItHoldsASecret`. The screen says on every row whether it holds a secret. No type between the wire and the glass has a field for a value: `WhatIsStored` reads the four fields it names and nothing else, so a value the stack sent beside a secret is dropped at the reader. `SeeingWhatThisMachineKeepsTest` sends one through the real adapter and asserts it is not drawn |
| `N6-R9` | An empty list of archives is told apart from one that could not be read | `WhatCopiesWereFound`, whose two arms are `TheCopies` and an obstacle. An empty `TheCopies` is drawn as *no copy has been taken*; an obstacle is drawn as its own sentence with what stood in the way and the remedy. `TheArchives` refuses a payload with no list, and a copy with no name, rather than reading either as a shorter list |
| `N6-R10` | Where the stack offers neither a rollback nor a restore, the app offers neither, and never presents one as the other | Putting back is offered on each copy the stack listed and on nothing else: an empty list and one that could not be read offer none. `PuttingACopyBack` offers the yes only where the stack listed the copy; a stack that would not list it is an obstacle with nothing to agree to. The yes is `WhatPuttingItBackWouldDo`, which only a listing produces. The screen calls it putting a copy back throughout, and the update screen's rollback is not offered here |
| `N6-R11` | Nothing here offers first-run setup | The screens offer taking a copy, putting one back and asking again, and nothing else, which `SeeingWhatThisMachineKeepsTest` asserts on what it draws |

What the stack keeps is asked first, and an obstacle there is the screen's
obstacle. The copies are asked only once that has answered, so a stack that
could not be listed still has what it keeps shown.

What is on the machine and is not the stack's is listed beside what it keeps.
Whether the call that answered removed anything is recorded as not read in
`WhatTheContractCarriesThatNothingReadsTest`, with its reason, and so is each
field of a copy and a restore that no screen draws.

## Asked for, and not drawn yet

`N6-R6` and `N6-R8` are about undoing an action and resetting configuration.
Each is an act, and this app offers neither.

The `Partial` state is an undo or a restore that finished with things it could
not do. The `restore` envelope carries no part left undone — a restore either
answers with what it did or is refused — so nothing here draws a restore as
partial.
