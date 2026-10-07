# Who gets in

The credentials a stack holds to let its services in, which app the household
should watch on, where the household comes in, and what an invitation grants
before it is sent. The code is the `N9` and `G9` half of `app-modules/kernel` and
`app-modules/sdk`, drawn by `WhatItHoldsToLetThemIn`, `WhichAppToWatchOn` and
`WhereTheHouseholdComesIn`, three screens each asked once when its frame is
built, and by `AskingSomebodyIn`, which [asking somebody in](asking-somebody-in.md)
covers.

Each row says what the requirement asks and why it is answered the way it is;
whether it is kept, and by what, is its row in `status.toml`.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | Why |
|---|---|---|
| `N9-R1` | A credential is shown with the state the contract gives, and stale, invalid and rotating are never one warning | `WhereACredentialStands` has a case for each of the six states, and each is drawn in a sentence of its own. A state this app has no case for is refused, and the reading is an obstacle |
| `N9-R2` | A credential names the services that consume it | Every consumer is drawn under the credential by name, and a credential nothing uses says so |
| `N9-R3` | A credential's origin is shown beside it | `WhoMadeACredential` is drawn on every credential: the operator, the service, or lemonfiber |
| `N9-R4` | The app never offers to set or change a credential's value, and never renders one | `ACredentialHeld` has no field a value could be carried in, and `CredentialsKept` does not read `revealed`, the one place the envelope can carry one. The screen offers nothing but asking again, and says a credential is set or replaced at the machine |
| `N9-R8` | A client recommendation is for a named device, carries its rating, and where the rating is poor carries what to use instead | `ADeviceToWatchOn` requires the device and the app, and `HowWellADeviceIsServed` has a case for each rating, *fallback* among them, each drawn in its own words. What to use instead is drawn wherever the stack sends it |
| `N9-R9` | Where something only works on the household network, the app says so | The clients screen draws the stack's `only_at_home` sentence once, above every device. On the front door, each address is drawn with the stack's own caution about it, and a door that answers where no other device can find it is its own standing, `stranded` |
| `N9-R10` | The front door shows what each address faces and why, and whether it was chosen or derived | The door and every service beside it are drawn with what they face; each service beside it says why it is not the door. `HowTheDoorWasChosen` says whether the door was worked out, chosen, or chosen and refused, with what was named and why. Every address is the stack's text: `AnAddressToHand` is never built here, and an address the stack did not send is drawn as not said |
| `N9-R5` | An invitation states what it grants — libraries, filtering, unrated material, and whether requests may be made — before it is sent | `AskingSomebodyIn` asks the stack to rehearse the invitation before offering to send it, and draws what `WhatWasGranted` carries back: each library, or every one where none was named; the limit, or none; the stack's sentence on what a limit is; what becomes of unrated material; and whether the request service was held to it. An invitation that writes nothing about access says so rather than drawing every library. The yes sends the request the rehearsal was asked with, through `AnInvitationAgreed` |
| `N9-R6` | An invitation states when it lapses | The `hours` the stack sends are drawn on the rehearsal and on the invitation sent, with what lapsing does: the invitation is withdrawn, and the account with it. They travel in the sentence the invitation is passed on under |
| `N9-R11` | Credentials or a door that could not be read are told apart from there being none | A reading that could not be read is an obstacle on each of the three screens. A stack holding no credentials, advising no devices, or publishing nothing to the household has a sentence of its own for each |

The credentials screen also draws what keeping them in files protects against
and what it does not, as the stack words both lists.

## Connecting a device

`ConnectingADeviceForThem` is opened from each member's card under who is in,
by the name their account is held under. It asks the stack nothing until the
operator taps *Show the code*, because the first asking writes down when the
code was given and which devices were signed in then. `Handoffs` reads the
`handoff` envelope and `Connectors` asks and follows it.

| Requirement | What it asks | Why |
|---|---|---|
| `G9-R2` | A per-person scannable code of the server address, and a deep link where the client takes one | The address is drawn as a code by `x-design::scannable` and written out beside it. Each app whose code is a link is drawn with *A link that opens :client at this server* and the link, as the stack wrote it |
| `G9-R3` | Code sign-in is guided and never approved on the person's behalf | The steps are the stack's, drawn in its words; this app offers no approval of any kind |
| `G9-R4` | A proprietary client is flagged as not open source and is never the default | `AClientToHandOver` and `ADeviceToWatchOn` both carry `open_source`, and both screens mark an app that is not with *Not open source*. The order is the stack's, which puts open-source apps first |
| `G9-R5` | Whether a device registered is asked of the media server, not assumed | *Signed in* is drawn only where the stack says `connected`, and the devices signed in are listed as it read them, each with when it was last seen |
| `G9-R6` | An incomplete step on the device is pending, not failed | `WhereTheHandoffStands` has a case for each state, and *Waiting for them* and *Did not work* are drawn apart |
| `G9-R7` | A client with no deep link falls back to the address | Such an app is listed with no link; the address and its code above are its code |
| `G9-R9` | The code is no bearer credential | The code is the address and nothing more, so the screen is not concealed; the steps say they still sign in as themselves |
| `G9-R10` | A device that cannot reach the machine is said to be that, not the app's fault | The stack's reason is drawn in its words wherever it gives one |
| `G9-R11` | A signed-out device does not go on reading as connected | Nothing is kept: whether a device is signed in is read when the operator taps *Check again*, and what was drawn is let go of on each asking |
| `G9-R13` | What to do next is a remedy each surface offers its own way | `WhatTheHandoffNeedsNext` has a case for each remedy and one for none: *Check again* asks again here, and is offered wherever nothing else is named, *Invite them* opens asking somebody in with the name typed, and starting the media server and recording the address open *Services* and *Front door* |

## Asked for, and not answerable yet

`N9-R7` asks that an invitation that lapsed unaccepted is told apart from one
the invitee declined. The contract does not carry that distinction: an
invitation's `standing` is `made`, `waiting`, `joined` or `reset`, and each is
drawn as the stack gives it; `waiting` past its hours is not read as lapsed.
`WhatTheContractDoesNotCarryTest` holds it against the invitation's shape,
which either word arriving changes.
