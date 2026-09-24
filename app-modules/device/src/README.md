# Device

The adapter between this application and the phone it runs on. Each class
implements one kernel port over the platform:

| | |
|---|---|
| `SystemClock` | `Clock` |
| `SystemEntropy` | `Entropy` |
| `PlatformNetwork` | `Networking` |
| `PlatformNotifier` | `Notifier` |
| `PlatformScanner` | `Scanning` |
| `PlatformScreen` | `Capture` |
| `PlatformShare` | `Sharing` |
| `PlatformAuth` | `DeviceAuth` |

The phone's features are reached through `nativephp/mobile` and this
application's `lemonfiber/bridge` plugin. Secure storage is in `vault`.
