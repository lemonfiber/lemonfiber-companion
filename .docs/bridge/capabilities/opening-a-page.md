# Opening a page

One request: hand an https page to the phone's browser, and say whether it
opened.

Serves `N3-R44`: the page where a member approves a sign-in code.

## What it answers

`Lemonfiber.Page.Open`, carrying `address`:

| outcome | means |
|---|---|
| `opened` | the page was handed to the phone's browser |
| `refused` | the address was not an https page, or nothing would open it |

**Only `opened` is a yes.** Any other word, a malformed envelope, or no bridge
at all reads as the page not having opened.

## Which pages

Only an `https://` address naming somewhere, with no blank or control
character in it. `PageRule` decides, before anything is asked of the platform,
and both halves hold it with the same cases.

## How each platform answers

**iOS.** The URL is opened with `UIApplication.shared.open` on the main queue.

**Android.** `ACTION_VIEW` in the browsable category is started; a phone with
nothing to answer it is a refusal.

## Nothing secret

The shim logs the outcome word, and on Android the kind of failure where nothing
answered; never the address.
