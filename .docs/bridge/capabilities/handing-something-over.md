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

## Three endings, of which two are the same

A handover ends with the operator choosing an app, with them dismissing the
sheet, or with the platform never presenting it.

The first two are one answer here, deliberately: where the report went is none
of this application's business. The third is its own, because a sheet that never
appeared leaves somebody looking at a screen that did nothing, and the screen
has to say so.

## What it answers

`Lemonfiber.Handover.File` and `Lemonfiber.Handover.Url`:

| outcome | `because` | means |
|---|---|---|
| `offered` | — | the sheet was put in front of them; what they did with it is theirs |
| `refused` | `nothing_to_hand_over` | the file was not there to offer |
| `refused` | `the_platform_would_not` | the sheet could not be presented |

**Deliberately no outcome for what they chose.** Reporting which app received a
diagnostic report would be this application learning something about the
operator it has no reason to know, and the sentence *must not be transmitted by
the app* is not honoured by an app that watches where it went.

## Nothing secret

The report is written to a cache file for the platform to read, because
`Share.file()` takes a path — that is the one thing in this bridge that writes
to a cache on purpose. What makes it safe is what is in it: the report carries
no credential, no address and no reading from a stack, and says so in its own
first lines. A test asserts that, and it belongs to the report rather than to
this capability.

The shim logs the outcome word. It does not log the path, and it does not log a
byte of the contents.

## Watched

**Nothing, because there is nothing to watch yet.** The capability is not built:
`HandoverRule` exists in Kotlin and Swift and passes its tests on both, and
there is no shim, no PHP facade and no wire call. `PlatformShare` still reaches
`nativephp/mobile-share`.

The section is here rather than absent on purpose. A page with no **Watched**
section reads as a capability somebody watched and forgot to write up, and this
one has not been near a device.
