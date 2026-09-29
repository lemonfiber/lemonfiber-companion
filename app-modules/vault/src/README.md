# Vault

The adapter between this application and the platform's secure store. Each
class implements one kernel port over it:

| | |
|---|---|
| `PlatformKeychain` | `SecureStorage`, which holds one session per stack |
| `PlatformStacks` | `Stacks`, the stacks this device is paired with |
| `PlatformStandings` | `Standings`, the word each stack's one line last said |
| `PlatformWorkLeftRunning` | `WorkLeftRunning`, the handle of work a screen left running on each stack |
| `PlatformSealKeys` | `HoldsTheSealKeys`, the two keys what the phone keeps is sealed under |

The store is reached through the `lemonfiber/bridge` plugin. Nothing this
module keeps is written to a file.

What each keeps, and the key it keeps it under. `<stack>` is the stack's own
identifier and `<kind>` is a `KindOfWork`, such as `walkthrough`:

| Key | Holds |
|---|---|
| `lemonfiber.session.<stack>` | The session for one stack, and whose it is |
| `lemonfiber.stacks` | Every paired stack: its identifier, name, address and pinned fingerprint, with a shape number |
| `lemonfiber.standings` | The word each stack's one line last said and when, with a shape number |
| `lemonfiber.left-running.<kind>.<stack>` | The handle of the work of one kind left running on one stack, with a shape number |
| `lemonfiber.seal.data` | The key every kept value is sealed under, as hex, asked to be readable only while the device is unlocked |
| `lemonfiber.seal.stack` | The key a stack's identity is hashed under, as hex, asked to be readable only while the device is unlocked |
