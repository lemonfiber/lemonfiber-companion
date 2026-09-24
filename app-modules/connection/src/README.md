# Connection

What this application decides while a device is paired with a stack and signed
in to it.

| | |
|---|---|
| `WhatTheCodeSaysSoFar`, `WhereTheCodeGot` | A pairing code read as far as it has been typed |
| `FingerprintWasConfirmed`, `PairingWasNotConfirmed` | The operator's confirmation of the fingerprint a typed pairing shows |
| `Introducing`, `HowThePairingWent` | Pairing material becoming a stack this device keeps |
| `HowTheSignInWent` | What became of a credential offered to a stack |
| `Opening` | What the app found when it opened |
| `WhetherItOpened` | Whether the device let the operator in |

It depends on `kernel` alone. The sign-in request is made by `Admissions` in
`sdk`, through the `Admitting` port.
