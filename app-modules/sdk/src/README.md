# SDK

The adapter between this application and a stack. It is the only shipped
module whose manifest requires `lemonfiber/sdk-php`, so it is the one place a
request to a stack can be made.

| | |
|---|---|
| Adapters | One class per kernel port that asks a stack something, such as `Questions` for `Asking` and `Supervisors` for `Supervising` |
| Readers | Static translators from an envelope to kernel values, such as `Reports` and `Rosters`, each with an exception for an answer it cannot read |
| Connections | `PinnedClients` and `PinnedDoors`, which open every connection pinned to the certificate that pairing recorded |

A reader passes each envelope through `Modules\Sdk\Internal\Wire`, which refuses a
wire version this application does not read. An adapter answers with a kernel
outcome type.

One adapter keeps something between calls: `Listeners`, for `Hearing`, holds a
stack's event stream open for the one screen it belongs to, and reads it without
waiting for what has not arrived.
