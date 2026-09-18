# What every screen owes

The rules that apply to a screen whatever it is about. The code is
`app-modules/operator`, and these are the ones that turn up in nearly every file
in it.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## A screen an operator is not stuck on

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R3` | A control is not hidden because a stack is unreachable — the app offers it and reports the failure | every screen's *ask again*; `tests/Templates/AnObstacleNeverTakesTheActionAwayTest.php` |
| `N1-R27` | A screen an operator cannot ask again is one that relies on leaving and returning | every screen's *ask again*; `tests/Templates/EveryReadingCanBeAskedAgainTest.php` |
| `N1-R44` | An obstacle has a screen of its own rather than an empty frame | the empty keys a template branches on |
| `N1-R46` | A stack that could not be asked is told apart from a credential that was refused | `HowTheSignInWent` and the obstacle folds |
| `N1-R10` | Each obstacle is its own condition with its own remedy | `Obstacle`; `tests/Feature/EveryObstacleSaysSomethingOfItsOwnTest.php` |

## A screen that does not talk to a machine behind your back

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R65` | One reading per frame, and the screen renders what came back | every screen holds what one asking produced; `tests/Arch/EveryCadenceIsStatedTest.php` |
| `N1-R66` | Beyond that, only a stated cadence or an operator's act — never a value read, a key pressed, or a screen rebuilt | the screens that poll say how often, and only while something is settling |
| `N1-R24` | A session lives no longer than the reach it was made for | opening a screen is a reach, and it carries when it was read |

## What a screen may not leak

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R8` | A credential never appears in a URL | routes carry a stored id and never an address or a secret |
| `N1-R15` | An address has one destination and a screen is not it | a row is a name and nothing else; `tests/Templates/NothingSecretReachesAScreenTest.php` |
| `N4-R13` | A diagnostic report is assembled from what the operator chooses to send, not from whatever a screen happened to hold | `Concealed` on every stack-facing screen |
| `N4-R18` | Credentials and pairing material are kept out of a capture, and so is the report | `tests/Arch/NothingIsCapturedFromAGuardedScreenTest.php` |

## What a screen looks like

| Requirement | What it asks | What keeps it |
|---|---|---|
| `DES-R15` | The accent is not set as text — measured at 1.6:1, it fails | `tests/Templates/ThemeColourIsNotSetAsTextTest.php` |
| `DES-R24` | One platform mapping, decided once rather than at each call site | the class lists are literal; `tests/Arch/NoClassDecidedAtRuntimeTest.php` |
| `DES-R25` | Two things are not told apart by colour alone | the same rule, which also keeps `G3-R1` |
| `Q-R64` | A screen publishes at most twenty methods | `tests/Arch/ModuleApiTest.php`; three screens arrived at twenty-one the day this was written |

## What a screen says

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R1` | Parity across surfaces, rather than parity by everybody remembering | the catalogues, and the rules over them |
| `N1-R2` | An operator away from the machine can see whether their house is working | the whole module |
| `N2-R1` | The app opens on the overall verdict | `HowThisStackIs` |
| `N2-R2` | Worst first, ordered here rather than trusted to arrive that way | `HowTheStacksStandingReads` |
| `N2-R3` | A finding carries its code, its meaning and its remedy, in the core's own words | `HowAFindingReads` |
| `G4-R3` | The cause is reported rather than each symptom independently | narrow, sort, then group — grouping last |
| `G4-R4` | Plain explanation leads; technical detail is available and does not lead | it arrives on the row beneath everything above it |
| `N1-R9`, `N2-R13` | An age comes out beside the word or not at all | both are broken by omission rather than by disagreement |
| `N2-R14` | A value the contract did not carry is not substituted | *nought seconds* is the worst answer, and is refused |
