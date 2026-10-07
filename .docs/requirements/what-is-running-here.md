# What is running here

Which version of lemonfiber a stack runs, how it got onto the machine, whether
a newer one is out, and what moving to it would take, and which versions run
under it. The code is the `N14` half of `app-modules/kernel` and
`app-modules/sdk`, drawn by `WhatIsRunningHere` and, reached from it,
`WhichVersionsRunHere`.

Each row says what the requirement asks and why it is answered the way it is;
whether it is kept, and by what, is its row in `status.toml`.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | Why |
|---|---|---|
| `N14-R1` | How lemonfiber was installed is shown, and where it cannot be told that is said | `HowLemonfiberWasInstalled` has a case for every way the contract names, `Untellable` among them, and the screen draws the case's own sentence. The owning tool is drawn where the stack names one |
| `N14-R2` | Where lemonfiber cannot replace itself, no control is offered that would not work, and the command that would is shown | The screen offers no control that updates anything. `HowItWouldBeUpdated` is the command to run at the machine, the stack's reason there is none, or neither, and one fold draws at most one of them |
| `N14-R3` | A withdrawn release is never offered and never counted as an update | An update is offered off the pins, and `Upkeep` offers nothing onto the pins a withdrawn release carries. The update screen keeps the withdrawn release in the history, marked as withdrawn, rather than dropping it |
| `N14-R4` | The changelog tells user-facing releases from those that are not | Every release in the history the update screen draws says whether the household would notice it, off the release's own `user_facing`, and the versions screen says it of the running release above its notes |
| `N14-R5` | An update says what it carries and what happens after it is applied, before it is agreed to | `WhatAnUpdateWouldBring` requires both sentences, and the screen draws them above the command |
| `N14-R6` | No update is applied that was not asked for, or on a schedule of the app's own | The only call the screen makes is the `self-update` reading. It holds no clock |
| `N14-R7` | A version or changelog that could not be read is told apart from being current | `WhereThisCopyStands::CheckFailed` is its own case, drawn with the stack's reason, and a reading this app cannot read is an obstacle, drawn as one. On the versions screen, `HowTheNotesStand` carries the record's own standing: `Pending` is drawn as notes not written yet and `Stale` as notes out of step, each with what that means, and `HowTheVersionsRead` draws the running release's notes only where the standing is `Current`. The update screen reads the same standing through the same reader: `HowUpkeepReads` draws what the running release changed only where `Upkeep::notes()` is `Current`, and both screens say why notes are withheld through `HowWithheldNotesRead`. `SeeingWhichVersionsRunTest` holds both to never drawing the notes |
| `N14-R8` | This surface is not used to update the services | The screen says so, and points nowhere that updates a service |
| `N1-R12` | Whether the connection is encrypted is stated, and no protection is implied that it does not have | Every connection is pinned to the certificate the pairing named, and `Pairing` refuses an address that is not encrypted. The screen says so under the owner wherever `Address::isEncrypted()` does, and the list of stacks says it once under every stack it lists; `SeeingWhatIsRunningTest`, `ChoosingAnotherStackTest` |

Where a newer version is out, the screen names it and says what updating to it
brings. Release notes are not drawn here: the running release's are drawn,
grouped, on the versions screen.

## Versions

Reached from *What is running* on About, and not from the menu (`N28-R5`).
`WhichVersionsRunHere` reads `/api/version` once for a frame through
`ReadingVersions`, answered by `Chroniclers` and read by `TheVersions`.

| Requirement | What it asks | Why |
|---|---|---|
| `G2-R1` | A domain term carries an inline explanation wherever it appears | Each of lemonfiber, the stack and the container engine is drawn with the sentence under `stacks.versions.*_means` saying what it does for the machine, including where the engine could not be asked |
| `N14-R3` | A withdrawn release is never offered | The versions screen offers nothing. Where the running release was taken back it says so above its notes, off the release's own `withdrawn` |
| `N1-R65` | A screen reads once for a frame | `WhichVersionsRunHere::answer()` asks once and holds the answer until *Ask again*, which `SeeingWhichVersionsRunTest` counts |
| `N28-R5` | A screen detailing one thing another screen shows may be reached from there | `EveryScreenOfAStackIsInTheMenuTest` names `Versions` among the screens reached from their own, with About as where |
