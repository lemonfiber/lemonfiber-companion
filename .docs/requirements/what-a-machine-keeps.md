# What a machine keeps, and the copies it holds

What the stack keeps on its machine, where each thing is and why, and the copies
of itself the machine holds. The code is the `N6` half of `app-modules/kernel`
and `app-modules/sdk`, drawn by `WhatThisMachineKeepsHere`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N6-R7` | What the stack is holding is shown with why, and nothing the contract marks secret has its value rendered | `SomethingKept`, which cannot be built without what it is, where, why and `WhetherItHoldsASecret`. The screen says on every row whether it holds a secret. No type between the wire and the glass has a field for a value: `WhatIsStored` reads the four fields it names and nothing else, so a value the stack sent beside a secret is dropped at the reader. `SeeingWhatThisMachineKeepsTest` sends one through the real adapter and asserts it is not drawn |
| `N6-R9` | An empty list of archives is told apart from one that could not be read | `WhatCopiesWereFound`, whose two arms are `TheCopies` and an obstacle. An empty `TheCopies` is drawn as *no copy has been taken*; an obstacle is drawn as its own sentence with what stood in the way and the remedy. `TheArchives` refuses a payload with no list, and a copy with no name, rather than reading either as a shorter list |
| `N6-R11` | Nothing here offers first-run setup | The screen offers asking again and nothing else, which `SeeingWhatThisMachineKeepsTest` asserts on what it draws |

What the stack keeps is asked first, and an obstacle there is the screen's
obstacle. The copies are asked only once that has answered, so a stack that
could not be listed still has what it keeps shown.

What is on the machine and is not the stack's is listed beside what it keeps.
Whether the call that answered removed anything is recorded as not read in
`WhatTheContractCarriesThatNothingReadsTest`, with its reason.

## Asked for, and not drawn yet

`N6-R1` to `N6-R6`, `N6-R8` and `N6-R10` are about taking a copy, putting one
back, undoing an action and resetting configuration. Each is an act, and this
app offers none of them.
