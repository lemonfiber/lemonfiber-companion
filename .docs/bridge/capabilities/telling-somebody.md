# Telling somebody

Local notifications: showing one now, scheduling one, cancelling, and reading
whether the device would show one at all.

Serves `N4-R1`, `N4-R2`, `N4-R4`, `N4-R10`, `N4-R20`.

## Reading the standing answer is a separate call from asking

`N4-R4` turns on the difference between *nobody has been asked* and *the
operator said no*. An app that cannot read the standing answer without raising a
prompt has no way to obey it: reading becomes asking, and a device where
somebody already refused gets asked again.

So `Standing` reads and `Ask` prompts, and they are two functions.

## What it answers

`Lemonfiber.Telling.Standing` — read, never ask:

| outcome | means |
|---|---|
| `granted` | a notification posted now would appear |
| `denied` | it would not, and nothing may ask again |
| `not_determined` | nobody has been asked; the point of first use is ahead |

`Lemonfiber.Telling.Ask` — raise the prompt, and **record that it was raised**.
The recording is the part nothing else can do: Android answers *never asked* and
*refused permanently* identically, so the difference has to be remembered by
whoever raised the dialog.

The rule takes four facts and is the same on both platforms:

- `wouldAppear` — would a notification posted now be seen (covers the runtime
  permission, the channel switch and the app switch at once)
- `permissionIsAsked` — does this platform have a runtime permission to ask for
  (false below Android 33; always true on iOS)
- `wouldExplain` — does the platform say an explanation would help (true only
  after a refusal, which is what makes it evidence of one; always false on iOS)
- `everAsked` — has this application raised the prompt before

```
wouldAppear            → granted
!permissionIsAsked     → denied          (settings, not a question)
wouldExplain           → denied          (refused once)
everAsked              → denied          (refused for good)
otherwise              → not_determined
```

## The rest

`Show`, `Schedule`, `ScheduleRecurring`, `Cancel`, `CancelAll`, `Pending`,
`ClearBadge` keep the vendor's behaviour and gain the outcome envelope: a
`Show` that was withheld says *why* — not permitted, no such channel, the device
refused — rather than returning false and leaving the caller to guess. The app
already has a `WhyNothingIsShown` for exactly this and currently fills it in
from a boolean.

## Nothing secret

A notification carries a `Code` and nothing else sayable — the words come from
the catalogue, keyed by type. The shim logs the code and never the rendered
text, because the rendered text is the only place a stack's name could reach a
log line.
