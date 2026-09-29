# What the phone keeps

How what the phone keeps between launches is sealed before any store sees it.
The code is `app-modules/seal`, the seal keys in `app-modules/vault`, and the
ports and values they share in `app-modules/kernel`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## Sealed, under a key the platform holds

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N27-R3` | Everything kept is encrypted under a key in the platform's secure storage, readable only while the device is unlocked, and never under a key in the application's own files | `EncrypterSeal` seals with AES-256-GCM under a data key it asks `HoldsTheSealKeys` for; `PlatformSealKeys` keeps that key in the platform's store, asked for at `WhenAValueMayBeRead::WhileUnlocked`; rules `S4` and `S5` keep the encrypter and the `Crypt` facade in `Modules\Seal` |
| `N27-R4` | A kept row names its stack only by a keyed hash, never by its identity, name or address | `SealedStack`, which `EncrypterSeal` builds as the HMAC-SHA256 of the stack's identity under a second key; rule `S6` keeps `hash_hmac` in `Modules\Seal` |
| `N27-R5` | With no secure storage the app keeps nothing between launches, and says so on App settings | `SealStanding` answers `Unavailable` and `Sealing` is refused with `WhyNothingIsSealed`, so nothing can be sealed and so nothing can be kept; App settings is a screen this app does not have |
