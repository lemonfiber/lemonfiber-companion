# The wire

## Names

Every function this bridge answers to is `Lemonfiber.<Capability>.<Verb>`.

```
Lemonfiber.Storage.Keep          Lemonfiber.Telling.Show
Lemonfiber.Storage.Read          Lemonfiber.Telling.Schedule
Lemonfiber.Storage.Forget        Lemonfiber.Telling.ScheduleRecurring
                                 Lemonfiber.Telling.Cancel
Lemonfiber.Scanning.Read         Lemonfiber.Telling.CancelAll
                                 Lemonfiber.Telling.Pending
Lemonfiber.Link.Status           Lemonfiber.Telling.ClearBadge
                                 Lemonfiber.Telling.Standing
Lemonfiber.Handover.File         Lemonfiber.Telling.Ask
Lemonfiber.Handover.Url
```

Capture protection and the app lock keep flat names — `Lemonfiber.Conceal`,
`Lemonfiber.Reveal`, `Lemonfiber.IsProtected`, `Lemonfiber.IsInFront`,
`Lemonfiber.Authenticate`, `Lemonfiber.CanAuthenticate`. `IsInFront` answers
from the lifecycle observer capture protection installs, which is why it sits
with them. Renaming a working function to match a pattern is
a change with no reader on the other end of it.

**Our own names.** The shape that comes back is the thing worth owning: a
facade answering a bool is a facade the adapter has to guess behind, and a name
borrowed from elsewhere arrives with the answer that was chosen there.

## What a call carries

Parameters and results are JSON objects. A result always carries the outcome as
a word, never as a bare boolean:

```json
{ "outcome": "kept" }
{ "outcome": "refused", "because": "no_store_on_this_device" }
```

`outcome` is one of a closed set per function, listed on the capability's page.
`because` is present only where the outcome is a refusal and is likewise closed.
Neither ever carries a value the caller passed in — no key, no token, no
address, no scanned payload — which is what keeps a refusal safe to log.

**A word rather than a code.** A number would be smaller on the wire and worse
everywhere else: a log line reading `because: no_store_on_this_device` explains
itself to whoever is reading it at the time, and a log line reading `because: 3`
sends them to this file.

## Declaring one

`bridge/nativephp.json` is the manifest. Each entry names the wire function, the
Android class and method, the iOS symbol, and a description in words — with no
requirement identifier in it, for the reason the rest of the code has none.

```json
{
  "name": "Lemonfiber.Storage.Read",
  "android": "app.lemonfiber.native.StorageFunctions.Read",
  "ios": "StorageFunctions.Read",
  "description": "Read a value the device kept, or say why there is none"
}
```

## Every declared function has a handler

The manifest is a promise, and the build fails where an entry names a symbol
that does not exist on either platform.

Checked at build time rather than at call time, on purpose. A missing handler
reached when an operator taps a control is a crash or a silence; a missing
handler reached when the plugin compiles is a build error with a name in it.
