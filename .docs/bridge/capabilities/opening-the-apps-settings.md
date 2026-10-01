# Opening the app's settings

One request: leave the app for its own page in the phone's settings, and say
whether the page opened.

Serves `N4-R17`.

## Why it is lemonfiber's own call

The framework has `System.OpenAppSettings`, and its PHP side discards what the
handset answers. A button that did nothing has to be able to say so, so this is
a call that answers.

## What it answers

`Lemonfiber.Settings.Open`, carrying nothing:

| outcome | means |
|---|---|
| `opened` | the page was asked for, and the platform has it in front of the operator |
| `refused` | the platform would not open it |

**Only `opened` is a yes.** Any other word, a malformed envelope, or no bridge
at all reads as the page not having opened, and the screen says so beside the
button.

## How each platform answers

**iOS.** `UIApplication.openSettingsURLString` is opened on the main queue where
the platform says it can be. `SettingsRule` decides the word, and `swift test`
holds it.

**Android.** `ACTION_APPLICATION_DETAILS_SETTINGS` for this app's package is
started; a phone with nothing to answer it is a refusal. `SettingsRule` decides
the word, and its Kotlin test holds it.

## Nothing secret

The shim logs the outcome word, and on Android the kind of failure where nothing answered; nothing else.
