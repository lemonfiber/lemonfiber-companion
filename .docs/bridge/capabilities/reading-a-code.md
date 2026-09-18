# Reading a code

The camera, opened once, to read the pairing code a stack is showing.

Serves `N4-R2`, `N4-R3`, `N1-R54`.

## Why the outcome is a closed set

A scan can end four ways and each one is a different sentence on the screen. A
boundary carrying them as free strings puts a `default` arm behind a spelling:
a misspelt refusal falls through to *the operator pressed back*, and that screen
offers to try again forever without mentioning the typed road.

The set is closed on both sides of the wire, so a wrong word is a compile error.

## What it answers

`Lemonfiber.Scanning.Read`, taking one parameter — `prompt`, the application's
own sentence, which is painted over the preview and is the last thing an
operator reads before the platform's permission dialog takes the screen.

| outcome | `because` | `may_ask_again` | means |
|---|---|---|---|
| `read` | — | — | a code was read; it rides in `payload` |
| `nothing` | `the_operator_closed_it` | `true` | they backed out; the screen offers another go and the typed road |
| `nothing` | `the_camera_is_not_permitted` | `true` | declined in the dialog; the screen offers to ask again |
| `nothing` | `the_camera_is_not_permitted` | `false` | settled in settings; the screen names Settings |
| `nothing` | `there_is_no_camera` | `false` | no camera on this device at all |

**One distinction the vendor cannot make and ours must.** A camera refused *in
the dialog just now* and a camera refused *in settings some time ago* arrive
identically, and they need different sentences: the first can be asked again at
the point of first use, the second cannot and has to send the operator to
settings. `CameraRule` takes the same shape as the notification one — whether
the platform would still explain, and whether this application has ever asked —
and answers `the_camera_is_not_permitted` either way, with `may_ask_again`
saying which it is.

`may_ask_again` is the one thing a refusal may carry, and `Envelope` gives it a
factory of its own rather than a payload parameter. A fact about the refusal is
not a value a caller handed in, and a general `refusing(outcome, because,
carrying)` would make the two indistinguishable at a call site.

## The code comes back in the answer, not on an event

This is the correction worth reading before anything else here. The obvious
carrier for a scanned payload is an event, and on Android it is the wrong one.

`NativeElementBridge.sendNativeEvent` writes the payload into the PHP queue
**and**, for any event name not beginning with `__`, hands it to the web
delivery arm. That arm interpolates the payload into a JavaScript source string
and evaluates it in the WebView, where it becomes a DOM `CustomEvent` any script
on the page can listen for, a `window.Livewire.dispatch`, and an HTTP POST to
`/_native/api/events`. On iOS `sendNativeEvent` is a binary write into the PHP
queue and nothing else.

A bridge answer is a JNI string return on Android and a direct return on iOS. It
touches no WebView, no DOM, no Livewire and no HTTP.

So the narrow road is the answer, and this capability blocks until the scanner
closes in order to have one. That is what `Telling.Ask` already does for the
permission dialog: the bridge calls a function off the main thread, so the
camera has a thread to run on while the call waits. The vendor's scanner returns
immediately and therefore *has* to broadcast — it is not a choice it made badly,
it is a choice its shape denies it.

**The vendor plugin also logs what it read.** `ScannerActivity.kt` writes
`New barcode scanned: $data` at debug level, which for this application is a
stack's address and a one-time credential in logcat. Between that and the WebView
broadcast, pairing material had two ways out of the device it was never meant to
leave.

## Nothing secret

**The scanned payload is pairing material.** It never appears in a log line, at
any level, in either shim — not truncated, not hashed, not "first eight
characters". The log lines carry closed words only: an outcome and a refusal.
`ScanningActivity.kt` and `ScanningViewController.swift` write no log at all.

This is the one capability where a debug log left in by accident would be a
disclosure rather than an annoyance, so the test that reads the source for
logged values is not optional here.

## One format, and it is a fact about pairing material

Both shims read QR and nothing else. That is not a setting on a scanner: the
payload is a JSON object carrying an address and a certificate digest, and the
other symbologies cannot hold one. A reader that accepted them all would answer
with whatever else happened to be in shot — a product code on a desk beside the
stack is not pairing material, and reporting it would dismiss the scanner and
then report a code that would not parse.

## Watched on a handset

Samsung SM-A515F (`R58N12ZSQ3H`), Android 13, debug build via `native:run`.

Walked from the first run to *Introduce this phone to your stack*, named the
stack, and opened the camera. The preview came up full screen with the
application's own sentence in a band across the bottom — *"The camera is used
once, to read the pairing code on your stack."* — which is the catalogue line
`Permission::Camera->reason()` resolves, not a key and not a string written into
the shim.

Pressing back closed it, and the round trip is the whole design in four lines:

```
23:59:46.019  nativephp_call() called with method: Lemonfiber.Scanning.Read
23:59:46.147  Displayed app.lemonfiber.native.ScanningActivity: +119ms
23:59:53.733  D Lemonfiber: scanning: nothing read, the scanner closed
23:59:53.733  Result JSON: {"may_ask_again":true,"outcome":"nothing","because":"the_operator_closed_it"}
```

The call held for the seven seconds the camera was on screen and answered the
envelope. The screen then said *"The camera closed before it read a code."* and
*"Open it again when you are ready, or type the code instead."* — the reason and
its own remedy, rather than one sentence for all four ways of getting nothing.

**What was not watched: a code actually being read.** That needs somebody to
hold the phone up to a stack's pairing screen, and this was driven over `adb`
from a machine that cannot point a camera. The `read` outcome and the `payload`
key are exercised by `ScanningTest` against the real bridge call and by
`ScanningContractTest` against the adapter and the stand-in, and the frame path
that produces them is nine lines of ML Kit in `ScanningActivity.kt`. It is the
one thing on this page proven by tests rather than by a device, and it should be
looked at the next time somebody has both a phone and a stack in front of them.

## iOS is unproven

The Swift compiles and `CameraRule` passes its own tests at the 100% floor, and
that is all that can honestly be claimed. `ScanningFunctions.swift` and
`ScanningViewController.swift` are excluded from the SwiftPM target — they need
AVFoundation and a device — so nothing has run them, not even a compiler
targeting iOS. No phone has been attached.

Two things are worth checking first when one is:

- `may_ask_again` is always false here after a refusal, because iOS shows the
  camera prompt once in the life of an install. The Android half can answer true
  once, in the window between a first refusal and a settled one. That is a real
  platform difference rather than a bug, and the two halves are meant to differ
  here.
- the scanner is presented over whatever is topmost, walked at the moment it is
  needed rather than remembered from launch. A presentation that lands
  underneath something is what that walk exists to prevent.
