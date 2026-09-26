# Asking somebody in

Who is in already, inviting a person, handing the invitation over, and letting
somebody choose a new password. The code is `AskingSomebodyIn`, the `Inviting`
port and its adapter `Ushers`, the `Invitations` reader and
`Households::whoIsIn()`, and the `codes` module, whose `QrCodes` draws an
address as a code. The rows for what an invitation states
before it is sent (`N9-R5`, `N9-R6`) are kept on [who gets in](who-gets-in.md).

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## What is answered

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N21-R1` | Inviting is offered, with libraries, an age limit and what happens to unrated material chosen in the stack's terms, and nothing in the media server's own terms | The screen asks for a name, libraries by the names the stack uses, an age in years, and whether unrated material is held back, let through or left to the stack. `WhatTheInvitationIsAskedWith` makes those into an `AnInvitationAskedFor`, and `WhatAnInvitationAsksWith` sends them as the `invite` action's `name`, `libraries`, `age_limit` and `unrated`, an age or a word about unrated material only where one was said. What the stack wrote comes back as `applied` and is drawn as it wrote it |
| `N21-R2` | The invitation is handed over as the address the stack gave, as text and as a code another device can scan, and no address is built or altered here | `AnInvitationToHand` holds the stack's `address` in an `AnAddressToHand`, which is never built here. The screen draws that text, and `QrCodes` draws the same text as squares of `bg-theme-on-accent` on `bg-theme-accent`, the two tokens that hold in light and dark alike. `QrCodesTest` holds the code to exactly the encoder's squares of the address, and to none of its caution |
| `N21-R3` | Handing over is the operator's act through the device's own sharing, and the app sends nothing to anybody | `Sharing::passOn()` takes an `AnInvitationToPassOn` and puts it in front of the operator through the platform's sheet, which is where it stops. The port takes no address, endpoint or client. A sheet that would not open is said to have sent nothing, and the address stays on the screen |
| `N21-R4` | A caution about the address is shown with it and travels with it | The caution is drawn beneath the address, and `AnInvitationToPassOn::text()` reads the address and its caution off the invitation itself, so what is passed on carries both. The code is the address alone, so the phone scanning it opens the address; the caution is drawn beside the code on the screen it is scanned from |
| `N21-R5` | What the stack found is shown as the standing it gave, and *joined* and *reset* are never presented as a new invitation | `WhereTheInvitationStands` has a case for each of `made`, `waiting`, `joined` and `reset`, each drawn in a sentence of its own. *Joined* leaves nothing to hand over: no address, no code, no sharing, and a rehearsal finding them joined offers no yes. *Reset* is drawn as their password having been taken off |
| `N21-R6` | Where the account was made and the request service has not been told, the app says they can watch and cannot yet ask, and not that it is complete or failed | `WhetherTheyCanAsk` has a case for each of `made`, `not-yet` and `not-tried`, and *not yet* is drawn as a person who can watch and cannot yet ask, which the next run puts right |
| `N21-R7` | Taking a member's password off is offered, and no password is shown, set, accepted or transmitted | The screen opens on who is in, read off the household by `Inviting::whoIsIn()`: each member by name, joined or with an invitation still out. Each row offers letting them choose a new password, asks once more, and `Inviting::takeThePasswordOff()` sends the `reissue` action with the name and nothing else. A name the reading did not list is refused by the screen. `SomebodyInTheHousehold` has no field a password could be carried in, and the answer is an invitation, standing `reset`, handed over like any other |
| `N21-R8` | A refusal about a person is shown with the stack's reason and the name asked for, and not as an error to retry | `Ushers` hands on the stack's own sentence where it refuses the asking or the naming, as `WhatBecameOfTheInvitation::refused()`. The screen draws it under the name that was asked for and offers starting again, never *ask again*. The account the media server signs in with stays on the list of who is in, and taking its password off is refused with the stack's reason like any other. A refused session and a stack that did not answer stay the obstacles they are |
| `N21-R9` | A rehearsed invitation is labelled as a rehearsal and never presented as an account that exists | `Invitations` reads `rehearsed` into `AnInvitation::rehearsed()` or `AnInvitation::carriedOut()`, and a rehearsal is drawn under a line saying nothing has been made yet, with no address and no code |
| `N21-R10` | Invitations the stack withdrew on the way past are shown with the answer they arrived on | `WhoWasTakenBack` carries `withdrawn` on every answer, and the screen lists them beneath it: as what would be taken back on a rehearsal, and as what was taken back on an answer carried out |

The yes is the rehearsal's own request again. The first tap asks the stack what
inviting them would come to, making nothing; `AnInvitationAgreed` can only be
made against that rehearsal, and sending it repeats the arguments the rehearsal
was asked with rather than whatever is in the fields afterwards. The stack
answers each act with a handle, and the screen follows it at the cadence it
states until the invitation arrives.

## Asked for, and not answerable yet

`N21-R1` is answered with the libraries named as text. Picking them from a list
is not answerable: the contract carries no list of the libraries a new member
could be given. `household` names each member's libraries, not the set there is
to choose from.

`N21-R5` is answered for the four standings the contract carries. An invitation
the invitee declined is not one of them (`D6-R16`), and telling it apart from
one that lapsed is `N9-R7`, which [who gets in](who-gets-in.md) keeps with the
register row that waits on it.

The address handed over is the one the invitation carries. `D6-R15` puts a
second, decline address on every invitation, and the invitation has no field
for it.
