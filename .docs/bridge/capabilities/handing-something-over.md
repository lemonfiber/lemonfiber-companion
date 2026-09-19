# Handing something over

The platform's own share sheet, given a diagnostic report to put in front of
somebody who can help.

Serves `N4-R13`.

## What this is really about

The requirement has two halves and the second is structural: the app *assembles*
a report for the operator to send, and **must not transmit it**. Where it goes
is a choice a person makes in an app this one does not know about — which is the
whole difference between this and a crash reporter, and the reason `N4-R12`
forbids one.

Both halves are kept by construction: `Diagnostics` holds nothing that could
send, `Sharing` takes nowhere to send to, and the shim asks for the sheet and
stops. There is no code path in any of the three that could upload.

**What the operator chose is never asked for.** Android's `createChooser` will
report the app that was picked through an `IntentSender`, and iOS reports it
through `completionWithItemsHandler`. Neither is used. Reporting which app
received a diagnostic report would be this application learning something about
the operator it has no reason to know, and *must not be transmitted by the app*
is not honoured by an app that watches where it went.

## Three endings, of which two are the same

A handover ends with the operator choosing an app, with them dismissing the
sheet, or with the platform never presenting it.

The first two are one answer here, deliberately: where the report went is none
of this application's business. The third is its own, because a sheet that never
appeared leaves somebody looking at a screen that did nothing, and the screen
has to say so.

## What it answers

`Lemonfiber.Handover.Offer`, taking a title and the report's text:

| outcome | `because` | means |
|---|---|---|
| `offered` | — | the sheet was put in front of them; what they did with it is theirs |
| `refused` | `nothing_to_hand_over` | there was no report to offer |
| `refused` | `the_platform_would_not` | the sheet could not be presented |

**One function, not two.** A URL handover has no requirement behind it: `N4-R13`
asks for a report assembled for the operator to send, and a link is a report in
somebody's browser history rather than a report. A function nothing calls is one
nobody maintains.

**Deliberately no outcome for what they chose.** Reporting which app received a
diagnostic report would be this application learning something about the
operator it has no reason to know, and the sentence *must not be transmitted by
the app* is not honoured by an app that watches where it went.

## Nothing is written to disk

The report travels as text and no file is made. Both platforms' sheets will take
a path, and taking one would mean writing a diagnostic report into a cache
directory, standing up a `FileProvider` on Android to grant read access to it,
and then leaving it there — because nothing on this side ever learns when the
chosen app is done with it. A file deleted at the right moment is a file the
chosen app cannot read; one deleted at no moment is residue.

`EXTRA_TEXT` and a `String` activity item need none of that. **Nothing in this
bridge writes to a cache**, and this was the one capability that would have.

What is in the report is what makes it safe to hand anywhere: no credential, no
address and no reading from a stack, said in its own first lines. A test asserts
that, and it belongs to the report rather than to this capability.

The shim logs the outcome word. It does not log the title, and it does not log a
byte of the text.

## Watched

**Nothing yet, on either platform.** The capability is built — rule, both shims,
the PHP facade, the contract suite against adapter and stand-in, and
`nativephp/mobile-share` removed — and no part of it has been in front of a
person.

What wants watching first is the sheet actually opening, because that is the one
thing no suite can assert: `HandoverTest` drives the envelope through the real
`nativephp_call()`, and `HandoverRule` decides the two refusals, but whether
`Intent.createChooser` and `UIActivityViewController` present at all is a fact
about a device. After that, the report arriving in the chosen app as text rather
than as an attachment, which is the change this capability made.

The section is here rather than absent on purpose. A page with no **Watched**
section reads as a capability somebody watched and forgot to write up.
