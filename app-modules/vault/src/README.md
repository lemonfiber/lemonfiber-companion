# Vault

The adapter between this application and the platform's secure store. Each
class implements one kernel port over it:

| | |
|---|---|
| `PlatformKeychain` | `SecureStorage`, which holds one session per stack |
| `PlatformStacks` | `Stacks`, the stacks this device is paired with |
| `PlatformStandings` | `Standings`, the word each stack's one line last said |

The store is reached through the `lemonfiber/bridge` plugin. Nothing this
module keeps is written to a file.
