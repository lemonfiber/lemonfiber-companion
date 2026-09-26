# Asking for help

A support bundle, chosen, described and only then written, and everything in it
readable before anybody else reads it. The code is the `N22` half of
`app-modules/kernel` and `app-modules/sdk`, drawn by `AskingForHelpHere`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N22-R1` | Producing a bundle is offered, with the log window, whether media filenames are shown and which settings are revealed chosen before it is written | `AskingForHelpHere`, reached from `HowThisStackIs`, with the choices in `ChoosesWhatABundleHolds`: three log windows, filenames replaced unless the operator shows them, and the settings to reveal. `ABundleAsked` carries all three, and `Bundlers` sends each as the `support` action's `logs`, `filenames` and `reveal` |
| `N22-R2` | Before a bundle is written, what it would hold, how large it would be and where it would go are shown as the stack described them, and writing is agreed to separately | The screen opens by describing the careful bundle, and `AskingForHelpHere::describe()` describes the one chosen: both send `ABundleAsked::described()`, which asks for nothing to be written. The description is drawn with its size, where it would go and every file. `AskingForHelpHere::write()` is the only call sending `write`, it does nothing unless a description is what the stack last answered with, and it sends `ABundleAsked::written()`, which keeps every choice that was described |
| `N22-R3` | A setting is revealed only by naming it and agreeing to reveal it on its own, never several or all by one agreement | `ASettingToReveal`, one name, and `SettingsToReveal`, which grows only through `SettingsToReveal::with()`, one setting at a time. The screen asks about one typed name, and `ChoosesWhatABundleHolds::reveal()` adds that one. Nothing reveals several. `confirm` is sent only with settings agreed to |
| `N22-R4` | The settings a bundle reveals are shown with it before it can be handed over | `TheTermsOfABundle::revealed()`, read from the bundle rather than from what was asked, and drawn on every bundle ahead of its files, or said to be none |
| `N22-R5` | Every piece of a bundle is readable before it can be handed over | `APieceOfABundle`, a name and its body as the stack redacted it. `TheBundle` refuses a bundle with a file missing either, and the screen draws every body whole |
| `N22-R6` | What the stack could not collect is shown with the bundle | `ABundle::missing()`. `TheBundle` refuses a bundle without the list, and the screen draws each entry, or says everything was collected |
| `N22-R7` | When a bundle was taken, and the lemonfiber and stack versions it came from, are shown with it | `WhenABundleWasTaken`, drawn on every bundle |
| `N22-R8` | A bundle the stack refused is shown as refused with the source it named, and not as an error to retry | Half kept. `HowTheBundleIsGoing::refused()` is its own arm, and `Bundlers` answers it where the stack refused the bundle in words. The screen heads it as refused, draws the stack's sentence and offers going back to the choices, never asking again. The source is in the refusal's `detail`, and the SDK's `RequestFailed` carries the refusal's sentence alone, so the sentence is what is drawn |
| `N22-R10` | The app adds nothing to a bundle, and puts nothing it holds into one | `Bundlers` sends the choices, `write` and `confirm`, and nothing else. `ABundleAsShown` holds only what the stack answered with, and nothing of this device's is drawn into it |

## Asked for, and not drawn yet

`N22-R9` is handing a bundle over through the device's own sharing. The file is
served at `/api/bundle/{name}` as a file rather than an envelope, and the SDK's
client has no method fetching it, so this app has no way to hold the file
(`N1-R16`, `N1-R17`). A written bundle stays on the stack's machine, and the
screen says where.
