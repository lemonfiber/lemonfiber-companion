# Taking somebody out

Taking one member out of the household: what it would cost, the yes, and how
far it reached. The code is `TakingSomebodyOut`, reached from each member on
`AskingSomebodyIn`, the `RemovingSomebody` port and its adapter `Removers`, and
the `Removals` reader. Taking lemonfiber off a machine is the other half of
`N13`, and is not kept here.

Each row says what the requirement asks and why it is answered the way it is;
whether it is kept, and by what, is its row in `status.toml`.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## What is answered

| Requirement | What it asks | Why |
|---|---|---|
| `N13-R1` | How far a revocation reached is shown as *everywhere*, *media-server-only* or *nothing*, never flattened into *removed* | `Removals` reads `revoked` into `HowFarTheRemovalReached`, a case for each of the three, and a word it has no case for is refused rather than read as the nearest one. Each is drawn in a sentence of its own, on the reading and on the removal alike |
| `N13-R2` | A revocation that reached only the media server is never rendered as complete | `HowFarTheRemovalReached::isDone()` is true for *everywhere* alone, and only that is drawn as done. *Media-server-only* says they can neither watch nor ask, that the request service still holds an account for them, and that it is not finished; the screen offers reading the cost again, since the next removal takes that account |
| `N13-R3` | Before a person is removed, what they have outstanding and whether they ask through the request service is shown | Opening the screen asks the stack what taking them out would cost, taking nobody out, and draws `requests` as a count and `asks-through-the-request-service` as a sentence of its own for each answer. The yes is offered only beneath that reading |
| `N13-R9` | A refusal is shown with the stack's reason, and not as an error to retry | `Removers` hands on the stack's own sentence where it refuses the asking, as `WhatBecameOfTheRemoval::refused()`. The screen draws it under the name that was asked about and offers the way back to who is in, never *ask again*. A refused session and a stack that did not answer stay the obstacles they are |
| `N13-R11` | An operation that could not be read is told apart from one that has not run | A yes the stack has no outcome for, or one that met something on the way, is drawn as whether they were taken out not having been read, which is not the same as it not having happened. Before a yes, the same states say the cost could not be read. `again()` asks after the same work where there is some, and otherwise reads the cost afresh: a yes is never sent a second time on its own |
| `N13-R19` | Before a person is removed, what it does to their requests is stated as the stack states it, and every finding is shown in the stack's words before and after | `requests` is drawn as a count of requests that are destroyed rather than handed to anybody, which is what the contract says the field counts, on the reading and on the removal. Every finding is drawn as the stack wrote it, beneath both. The watch-history half is below |

The yes is agreed against the reading on the screen. `ARemovalAgreed` can only
be made from a reading nobody agreed to, and names the person as the media
server spells them; the screen holds that reading only while it is in front of
the operator, carries nothing in from another screen, and a reading asked for
again is agreed to again. Every act answers with a handle, followed at the
cadence the screen states until the removal arrives.

## Asked for, and not answerable yet

`WhatTheContractDoesNotCarryTest` holds each of these against the `removal`
envelope's shape, which the answer arriving changes.

`N13-R7` is answered for this screen and not on the wire. The `remove` action
takes a name and a bare `confirm`, and the envelope names nothing a yes could
quote, so nothing sent can say which reading was agreed to.

`N13-R10` has no person's rehearsal to label yet. The `remove` action takes
`dry_run` and the `removal` envelope says `rehearsed`, but the screen does not ask
for a rehearsal; it draws `confirmed: false` as what taking them out would cost,
and never as a rehearsal.

`N13-R19` is answered for the requests and the findings. Nothing on the wire
says what taking somebody out does to their watch history, and the screen says
nothing about it rather than saying it for the stack.
