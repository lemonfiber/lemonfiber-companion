# What is running here

Which version of lemonfiber a stack runs, how it got onto the machine, whether
a newer one is out, and what moving to it would take. The code is the `N14`
self-update half of `app-modules/kernel` and `app-modules/sdk`, drawn by
`WhatIsRunningHere`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N14-R1` | How lemonfiber was installed is shown, and where it cannot be told that is said | `HowLemonfiberWasInstalled` has a case for every way the contract names, `Untellable` among them, and the screen draws the case's own sentence. The owning tool is drawn where the stack names one |
| `N14-R2` | Where lemonfiber cannot replace itself, no control is offered that would not work, and the command that would is shown | The screen offers no control that updates anything. `HowItWouldBeUpdated` is the command to run at the machine, the stack's reason there is none, or neither, and one fold draws at most one of them |
| `N14-R5` | An update says what it carries and what happens after it is applied, before it is agreed to | `WhatAnUpdateWouldBring` requires both sentences, and the screen draws them above the command |
| `N14-R6` | No update is applied that was not asked for, or on a schedule of the app's own | The only call the screen makes is the `self-update` reading. It holds no clock |
| `N14-R7` | A version that could not be read is told apart from being current | `WhereThisCopyStands::CheckFailed` is its own case, drawn with the stack's reason, and a reading this app cannot read is an obstacle, drawn as one |
| `N14-R8` | This surface is not used to update the services | The screen says so, and points nowhere that updates a service |

Where a newer version is out, the screen draws it with what its release notes
say it changed.

## Asked for, and not drawn yet

`N14-R3` and `N14-R4` are about the changelog of the stack's releases, which
the `update` envelope carries and the update screen reads.
