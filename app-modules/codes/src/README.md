# Codes

The adapter between this application and the QR encoder. One class
implements one kernel port over it:

| | |
|---|---|
| `QrCodes` | `Encoding`, an address as squares another phone can scan |

The encoder is `bacon/bacon-qr-code`. It is handed an address exactly as the
stack sent it and nothing else, and what comes back is drawn by a screen out of
EDGE shapes: no image is written anywhere.
