# Seal

The adapter between this application and Laravel's encrypter. One class
implements one kernel port over it:

| | |
|---|---|
| `EncrypterSeal` | `Sealed`, which seals what the phone keeps and hashes which stack it is kept for |

Values are sealed with AES-256-GCM under a data key, and a stack is named by
its HMAC-SHA256 under a second key. Both keys are thirty-two bytes, kept in the
platform's secure storage by `vault` and asked for through `HoldsTheSealKeys`;
a key that has to be made is drawn from `Entropy`. The framework's own key and
the `Crypt` facade are not used.

This is the only module that names the encrypter, the `Crypt` facade or
`hash_hmac`.
