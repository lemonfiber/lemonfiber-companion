# Handing something over

The platform's own share sheet, given a diagnostic report, an invitation or a
support bundle to put in front of somebody.

Serves `N4-R13` and `N22-R9`.

## What this is really about

The requirements have two halves and the second is structural: the app
*assembles* a report, or fetches a bundle, for the operator to send, and **must
not transmit it**. Where it goes is a choice a person makes in an app this one
does not know about — which is the whole difference between this and a crash
reporter, and the reason `N4-R12` forbids one.

Both halves are kept by construction: `Diagnostics` holds nothing that could
send, an `ABundleFile` has no way to send itself, `Sharing` takes nowhere to
send to, and the shim asks for the sheet and stops. There is no code path in
any of them that could upload.

**What the operator chose is never asked for.** Android's `createChooser` will
report the app that was picked through an `IntentSender`, and iOS reports it
through `completionWithItemsHandler`. Neither is used. Reporting which app
received a diagnostic report or a bundle would be this application learning
something about the operator it has no reason to know, and *must not be
transmitted by the app* is not honoured by an app that watches where it went.

## Three endings, of which two are the same

A handover ends with the operator choosing an app, with them dismissing the
sheet, or with the platform never presenting it.

The first two are one answer here, deliberately: where it went is none of this
application's business. The third is its own, because a sheet that never
appeared leaves somebody looking at a screen that did nothing, and the screen
has to say so.

## What it answers

`Lemonfiber.Handover.Offer`, taking a title and a report's text, and
`Lemonfiber.Handover.OfferFile`, taking a title, a file's name and its bytes in
base64:

| outcome | `because` | means |
|---|---|---|
| `offered` | — | the sheet was put in front of them; what they did with it is theirs |
| `refused` | `nothing_to_hand_over` | there was nothing to offer: no text, or a file that could not be written |
| `refused` | `the_platform_would_not` | the sheet could not be presented |

**Two functions, one for text and one for a file.** A report and an invitation
are text and go straight into whatever the operator picks. A support bundle is
an archive, and an archive only travels as a file.

**Deliberately no outcome for what they chose.** Reporting which app received
it would be this application learning something about the operator it has no
reason to know.

## Text writes nothing; a file is written once, and swept

`Offer` hands the platform text — `EXTRA_TEXT` on Android, a `String` activity
item on iOS — and writes nothing.

`OfferFile` writes the bytes into one directory, `lemonfiber-handover`, inside
the app's own cache, used for nothing else. On Android the chooser is handed a
`content://` URI from the host app's `FileProvider` with
`FLAG_GRANT_READ_URI_PERMISSION`, so the chosen app may read that one file; on
iOS `UIActivityViewController` is handed the file's URL. A grant is only ever
given for the file the directory holds.

Nothing on this side learns when the chosen app is done reading, so the file is
bounded rather than tracked: `ShareCache` empties the directory before every new
handover, and the plugin's init function empties it on the next launch. **At
most one file is ever in it**, and only until one of those comes round. A name
that is nothing or a path is refused as nothing to hand over, so nothing is ever
written outside that directory.

What is in a report is what makes it safe to hand anywhere: no credential, no
address and no reading from a stack, said in its own first lines. A bundle is
the stack's own file, already redacted, and the app adds nothing to it. Both are
properties of what is handed over rather than of this capability, and each has
its own test.

The shim logs the outcome word. It does not log the title, the file's name, or
a byte of the text or the file.

## Watched

**Nothing yet, on either platform.** The capability is built — rule, share
cache, both shims, the PHP facade, and the contract suite against adapter and
stand-in — and no part of it has been in front of a person.

What wants watching first is the sheet actually opening, because that is the one
thing no suite can assert: `HandoverTest` drives the envelope through the real
`nativephp_call()`, `HandoverRule` decides the two refusals and `ShareCache`
decides where a file goes and when it is swept, but whether
`Intent.createChooser` and `UIActivityViewController` present at all is a fact
about a device. After that, a bundle arriving in the chosen app as an
attachment it can open, which is what the read grant is for.

The section is here rather than absent on purpose. A page with no **Watched**
section reads as a capability somebody watched and forgot to write up.
