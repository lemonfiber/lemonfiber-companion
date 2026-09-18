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

`Ask` waits for the operator rather than returning while the dialog is on
screen. It has to: both halves write the record down *before* raising the
prompt, because that is the only moment either can, and reading the record back
before the operator has answered says *refused* about somebody who is still
looking at the question. On Android the wait ends when the activity is resumed
— which is what happens when the platform's own dialog goes away — and gives up
after two minutes; on iOS it is the authorisation callback.

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

**The reading lives in `WhatTheOperatorSaid`, not in `NotificationRule`.** The
camera asks the identical question — [reading a code](reading-a-code.md) says
so — and the same three states with the same four inputs. Two copies of that
decision would be two capabilities quietly disagreeing about what `denied`
means, which is precisely what the paired Kotlin and Swift exist to catch. The
capability's own rule keeps what is its own: which facts are gathered, and what
the answer is called once the subject is a notification.

There is a second reason and it is the load-bearing one. `outcome` and
`because` are closed sets that never carry a caller's value, and a closed set
kept in one place is a promise something can be made to check; one copied into
each capability is a promise nothing can stand over. `Envelope` is the other
half of that, and is likewise a file of its own on both platforms.

## The rest

`Show`, `Schedule`, `ScheduleRecurring`, `Cancel`, `CancelAll`, `Pending`,
`ClearBadge` keep the vendor's behaviour and gain the outcome envelope: a
`Show` that was withheld says *why* — not permitted, no such channel, the device
refused — rather than returning false and leaving the caller to guess. The app
already has a `WhyNothingIsShown` for exactly this, and it grew a case for the
distinction a boolean could not carry: *the device would not*, which is not a
permission and is not answered by asking anybody anything.

| outcome | `because` | answered by | means |
|---|---|---|---|
| `shown` / `scheduled` / `cancelled` / `cleared` / `read` | — | each function its own | the platform took it |
| `withheld` | `not_permitted` | `Show`, `Schedule`, `ScheduleRecurring` | the standing answer is not a grant |
| `withheld` | `no_such_channel` | the same three | the operator switched this application's channel off. Android only: iOS has one switch per application and the permission already covers it |
| `withheld` | `the_time_has_passed` | `Schedule` | the moment asked for has been. Shown immediately instead would be an alert about something that was going to happen |
| `withheld` | `no_such_repeat` | `ScheduleRecurring` | the repeat names something no calendar has |
| `withheld` | `the_device_refused` | `Show` | the platform declined and said nothing useful about why |

`Pending` answers `read` and carries the identifiers still to come. Null on the
PHP side — rather than an empty list — where nothing answered at all, because
*nothing is scheduled* and *this process cannot ask what is scheduled* are
opposite answers and every machine that is not a handset gives the second.

### Three narrowings, each deliberate

**A notification carries a title and a body and nothing else.** The vendor's
takes a sound, a badge, a subtitle, an arbitrary data payload and up to three
action buttons. None of them has a caller here, and each is another place a
value could travel that this application never meant to send.

**A repeat may name a day of the month up to the 28th, and no further.** Every
month has a 28th and no month has a 31st, and the two platforms' calendars
disagree about what to do with one — `Calendar.nextDate` skips to a month that
has it, `java.time` raises rather than answer. A repeat an operator cannot
predict is worse than one this bridge declined to arm, so `no_such_repeat` is
the answer and the narrowing is written down rather than discovered.

**A scheduled notification does not survive a reboot on Android.** Alarms do
not, and re-arming one would mean keeping the rendered text somewhere this
bridge has decided not to keep it. iOS keeps its own pending requests and is
unaffected. Nothing in this application schedules anything today; the day
something does, this is the sentence to revisit.

### Where the two platforms differ, and why

| | Android | iOS |
|---|---|---|
| Repeating | `AlarmManager` is told one moment at a time and the bridge re-arms each occurrence. `setRepeating` is inexact and carries no way to say *this time of day in this zone*, so a repeat crossing a daylight-saving boundary would drift an hour and stay there | `UNCalendarNotificationTrigger` repeats on its own, from the same fields |
| Next occurrence | `java.time` | `Calendar.nextDate(after:matching:)` |
| What is still to come | kept by this plugin: `AlarmManager` can be asked to arm one and not asked what it holds | read from `UNUserNotificationCenter`, which keeps the list |
| The badge | none of its own — a launcher draws a count of the notifications showing, so clearing it is clearing those | a real badge, set to zero |
| Exactness | exact where `canScheduleExactAlarms()` allows it, approximate where it does not. `USE_EXACT_ALARM` is reviewed by a store and granted to alarm clocks; this is not one | as scheduled |

Both rules expose the same two things — `nextAfter` and `fixes` — and each shim
uses whichever its platform needs. The idioms differ on purpose; the answers do
not, and the paired tests ask both halves the same nine questions.

## Nothing secret

A notification carries a `Code` and nothing else sayable — the words come from
the catalogue, keyed by type. The shim logs the code and never the rendered
text, because the rendered text is the only place a stack's name could reach a
log line.

The plugin this replaced logged the title. `Log.d(TAG, "Showing notification:
id=$id, title=$title")` is in its `Show`, and the title this application
composes carries the stack's name — so every alert about a machine put that
machine's name in the device log.

## Watched on a handset

Samsung SM-A515F (`R58N12ZSQ3H`), Android 13, debug build, 18 September 2026.
Every one of the nine functions was called from the application's own PHP and
the answers read back off the device.

| | What was seen |
|---|---|
| The plugin compiles in | `Compiling plugin … lemonfiber/bridge (0.1.0)`, and all nine `Lemonfiber.Telling.*` in the generated registration |
| `Standing` | `granted`, read without a dialog appearing |
| `Ask`, first run | the platform's own prompt — *Toestaan dat lemonfiber je meldingen stuurt?* — raised, waited on, and `granted` after the operator allowed it |
| `Ask`, second run | no prompt at all, `granted` from the standing answer |
| `Show` | the notification in the shade, with the title and body the caller sent |
| `Schedule` | `scheduled`, and a `Schedule` for a moment already past answered `the_time_has_passed` |
| `ScheduleRecurring` | `scheduled`, and an hour of 25 answered `no_such_repeat` |
| `Pending` | 1 after scheduling, 0 after `Cancel`, 1 after the repeat, 0 after `CancelAll` |
| `ClearBadge` | returned `cleared` |
| The channel | `lemonfiber.alerts` created with the application's own label — already translated by the platform — at importance `DEFAULT` |
| The log | `telling lemonfiber.probe: shown`, `telling lemonfiber.later: scheduled`. An identifier and an outcome word, and no rendered text anywhere |

**What the handset found that no suite did.** The wire answers five refusal
words and the PHP knew three: `the_time_has_passed` and `no_such_repeat` reach
a caller from scheduling alone, and an unrecognised word falls to *the device
refused* — so a repeat this bridge deliberately declined came back as the device
having failed. Both are cases now, on both sides.

## iOS is unproven

The Swift compiles, `swift-format` passes in strict mode and the rules are unit
tested at the 100% floor, and that is all that can honestly be claimed: no
iPhone has been attached. What has not been watched is the shim — the
authorisation callback, `UNUserNotificationCenter.add`, the repeating trigger,
the badge — and one thing in particular is worth checking first, because it is
the same shape on every capability: the Swift half wraps its answer in
`BridgeResponse.success(data:)` where the Kotlin returns the map directly, and
whether the PHP reads the same keys through both has only ever been proven on
Android.
