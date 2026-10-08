# Finding a machine by its name

One question: what addresses does a machine's name resolve to, as the phone
itself resolves it.

Serves `G6-R5`, `N1-R15`, `N1-R19`.

## Why it is asked

A stack is paired by its mDNS name where it has one, so the pairing outlives a
change of address. The app's PHP runtime resolves names through a resolver that
does not answer a `.local` name. The phone's own resolver does. Without this
call, a stack paired by its `.local` name is not found at all.

The app sends to what this call answers. The URL, the `Host` header, the TLS
server name and the pinned fingerprint all stay those of the name it was
paired with. Nothing resolved is kept.

## What it answers

`Lemonfiber.Resolve`, with `host`:

| outcome | carrying | means |
|---|---|---|
| `found` | `addresses`, at least one | the name resolved to addresses the app may send to, IPv4 first |
| `nothing` | `addresses`, empty | it did not resolve, did not resolve in time, or resolved only to addresses the app cannot use |

**Only addresses are load-bearing.** Any other word, an entry that is not an
address, a malformed envelope, or no bridge at all reads as nothing found. The
app then resolves the name the way it would without this call.

## The states it keeps apart

`ResolveRule` decides on both platforms, and the Kotlin and Swift tests ask it
the same six questions:

- nothing found, with no address;
- an IPv4 address, found and usable;
- IPv4 before IPv6, each family in the order the platform gave;
- an IPv6 address that needs its interface (link-local) is dropped, because a
  TLS connection opened from PHP cannot name the phone's interface;
- only unusable addresses is nothing found;
- an address given twice is sent to once.

## How each platform answers

**Android.** `InetAddress.getAllByName` on a thread of its own, with a
three-second wait. It asks the system resolver, which answers `.local` names
over multicast DNS. NsdManager is not needed. Run on the A51 (Android 13)
through `app_process`, which resolves through the same system resolver an app
does, a Mac's `.local` name resolved to four addresses in 172 ms, and a
`.local` name nothing answers for failed after 4.2 s.

**iOS.** `getaddrinfo` on a queue of its own, with the same three-second wait.
It is the system resolver every iOS networking API ends in, and it answers
`.local` names through mDNSResponder.

## Nothing secret

The shims log the outcome word, how many addresses are usable, and the class
of what the resolver raised. They never log the name or an address.

## Watched

Not yet in a device build. Both rules pass their tests, and the Android look-up
has run on the A51 outside the app. Neither shim has run inside the app on a
handset.
