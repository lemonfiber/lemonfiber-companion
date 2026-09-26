# Choosing how good

How good a machine's media should be: the presets in force, choosing one for
everything or for one kind of media, a held choice confirmed apart, and
upgrading what is already in the library as its own act. The code is the
choosing half of `N24` in `app-modules/kernel` and `app-modules/sdk`, drawn by
`ChoosingHowGood` and reached from the home screen of a stack.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N24-R1` | Choosing a preset is offered for everything and for one kind of media, in the stack's plain terms | `ChoosingQuality::choose()` takes an `APresetToChoose`: a preset and a kind as the operator named them, no kind being everything. `Graders` sends `quality-set` with the preset, the kind only where one was named, and no yes. Every preset in force is drawn in the stack's words, the overall one first, with what an hour of it costs |
| `N24-R2` | Media with no resolution is offered in the stack's terms for it, and never as a resolution | Music is `AFormatInForce`, its own type with a format, what it means, what it aims for and what an hour costs, and no resolution. It is drawn under its own heading, never among the presets; a choice for music is answered with the `music` envelope, read by `WhatMusicCameTo` with what its service made of it |
| `N24-R3` | A held choice is shown with its reason, and confirming it is apart from viewing it | `WhatBecameOfTheChoice::Held` has its own sentence, and the reason is what the stack said playing each preset costs where it would transcode here. `ChoosingQuality::confirm()` takes an `AHeldChoice`, which only a held answer to the operator's own choice builds, and the screen offers it as a tap of its own |
| `N24-R4` | Upgrading the library is its own act, described kind by kind before it is confirmed | `UpgradingTheLibrary` is a port apart from choosing. Describing sends `quality-upgrade` with no yes and draws each kind at its own preset and cost an hour; `upgrade()` takes an `AnUpgradeDescribed`, which only a description builds, and sends the yes. What each service was asked is drawn per kind |
| `N24-R5` | A hand-edited configuration is shown as edited and respected, and re-asserting the preset is not offered | `TheQualityChosen::customised()` is drawn as a sentence saying the edit is left as it is and putting the preset back is not offered here. `WhatToDoAboutQuality` has no case for `quality-reapply`, and `TheAppOpensOnlyTheseDoorsTest` holds every verb this app can ask for to its list |
| `N8-R1` | A quality choice is shown with what an hour of it costs, never by name and resolution alone | Every preset and the music format carry the stack's size an hour, drawn on every row |
| `N8-R2` | A choice that needs transcoding here is shown so, and not as a property of the preset | `APresetInForce::transcodesHere()` is the stack's `needs_transcoding_here`, drawn as a sentence about this machine on the row it is true of, beside what playing the preset costs |
| `N8-R3` | A disposition is rendered as given, and *rehearsed*, *held* and *recorded* are never flattened | `WhatBecameOfTheChoice` has a case for each of the six, each drawn in a sentence of its own. A word this app has no case for is refused, and the reading is an obstacle |
| `N24-R10` | A rehearsed choice is labelled as a rehearsal and never presented as recorded | `WhatBecameOfTheChoice::Rehearsed` has its own sentence, *a rehearsal: nothing has been recorded*, on a preset and on a format for music alike, and so does a rehearsed re-assert. Neither shares a sentence with `Recorded` |

A choice or an upgrade the stack could not be asked about is what stood in the
way, drawn as such, and never a choice that was not made.

## Asked for, and not answered

`N24-R1` is answered in part. The contract carries the choices in force and
not the presets a choice may be made from, the formats music may take, or the
kinds of media a preset may be set apart for, so the operator names them in the
stack's words and the stack takes the name or refuses it.

`N24-R4` is answered in part. The contract carries a cost an hour for each kind
and no total, which `D2-R7` asks for, and no statement that choosing affects
what is fetched next only, which `D2-R6` asks for. Neither is worked out or
written here.

All three are blocked on the contract, and `WhatTheContractDoesNotCarryTest`
holds each.

`N24-R6` to `N24-R9` are the walkthrough.
