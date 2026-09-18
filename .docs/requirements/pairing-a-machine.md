# Pairing a machine, and opening the app

How this device comes to know a stack, and what it decides the moment somebody
launches it. The code is `app-modules/connection`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## Learning a machine

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R6` | Two roads in — a camera and a person typing — and they are not equally safe | `Introducing`; `ADR-0018` chose the software comparison a camera makes |
| `N4-R3` | Typed entry exists on a device whose operator declined the camera | `Introducing` — it is a route, not a courtesy, and it has no software comparison behind it |
| `N1-R18` | The pinned fingerprint is carried from the pairing material and never looked up on the network | `Introducing` — a fingerprint learned from the connection it is meant to validate proves nothing |
| `N1-R22` | Trust is pinned to the stack rather than to where it answers | `Introducing`; a stack keyed on its address becomes a different stack when DHCP moves it |
| `N1-R11` | A device holds more than one stack, and a person has to tell them apart | `Introducing` asks for the name rather than deriving one |
| `N1-R49` | Pairing material expires, and expiry is not reported as a typing mistake | `WhatTheCodeSaysSoFar` — telling somebody to check the characters sends them to look for a mistake that is not there |
| `N1-R50` | Typed pairing requires the fingerprint to be confirmed, and the app may not proceed without it | `FingerprintWasConfirmed`, which is a type rather than a `bool` |
| `N1-R51` | The confirmation carries a short form derived from the whole fingerprint — what was compared, not merely that something was | `FingerprintWasConfirmed` |
| `N1-R20` | Re-pairing is the remedy for a certificate that changed, and is deliberately a different act from first pairing | `Introducing` does not offer it |
| `N1-R10` | A device offering no store at all and a store that refused are different things with different remedies | `HowThePairingWent` |

## Opening it

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R37` | A launch with no network, one that cannot reach the stack, and one where the app is locked are told apart | `Opening` |
| `N1-R35`, `N1-R36` | And the two that are not failures at all: no stack paired yet, and a stack paired but not reachable | `Opening` |
| `N1-R31` | Two stacks are not interchangeable, so which one opens is answered rather than chosen on the operator's behalf | `Opening` |
| `N1-R66` | Nothing but a stated cadence or an operator's act makes a screen reach a machine | `Opening` — opening one is an act |
| `N4-R19` | The device's own authentication on a cold start, asked before anything touches a network | `Opening` |
| `N4-R22` | A lock over an empty store protects nothing, and is not asked for | `Opening` |
| `N4-R23` | The store itself decides that, not a flag, so an unlocked first run cannot outlive it | `Opening` |
| `N4-R17` | A refused local-network permission is a condition distinct from an unreachable stack | `HowTheSignInWent` |
