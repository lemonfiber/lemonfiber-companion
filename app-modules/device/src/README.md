# Device

The adapter between this application and the phone it runs on. Each class
implements one kernel port over the platform:

| | |
|---|---|
| `SystemClock` | `Clock` |
| `SystemEntropy` | `Entropy` |
| `PlatformNetwork` | `Networking` |
| `PlatformLocalNetwork` | `TheLocalNetwork` |
| `PlatformAppsSettings` | `TheAppsSettings` |
| `PlatformNotifier` | `Notifier` |
| `PlatformScanner` | `Scanning` |
| `PlatformScreen` | `Capture` |
| `PlatformShare` | `Sharing` |
| `PlatformAuth` | `DeviceAuth` |
| `PlatformPlayer` | `Playing` |

The phone's features are reached through `nativephp/mobile` and this
application's `lemonfiber/bridge` plugin. Secure storage is in `vault`.

`PlatformShare` hands a report and an invitation to the share sheet as text,
and writes nothing. It hands a support bundle over as a file: the bridge writes
it into one app-private directory used for nothing else, grants the chosen app
read access to that one file, and empties the directory before every new
handover and on the next launch, so at most one bundle is ever on the device.
Nothing reports which app was chosen.

`PlatformPlayer` hands the device's player one title as the core stated it:
the location, the door's fingerprint and the member's grant, and where to start.
The native half reads each field again, pins the door's certificate and carries
the grant in a header; nothing about the door is decided here. Where the player
stands is read by asking, in whole seconds.
