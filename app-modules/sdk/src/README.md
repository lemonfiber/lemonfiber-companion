# SDK

The adapter between this application and a stack. It is the only shipped
module whose manifest requires `lemonfiber/sdk-php`, so it is the one place a
request to a stack can be made.

| | |
|---|---|
| Adapters | One class per kernel port that asks a stack something, such as `Questions` for `Asking` and `Supervisors` for `Supervising` |
| Readers | Static translators from an envelope to kernel values, such as `Reports` and `Rosters`, each with an exception for an answer it cannot read |
| Connections | `PinnedClients` and `PinnedDoors`, which open every connection pinned to the certificate that pairing recorded |
| The gate | `ClientsThatAskWhatIsOffered`, which asks a stack whether it serves a path before a request is sent there, and `GatedClient`, which every adapter reaches a stack through so each request names its path; `EveryRequestThisAppSends` is every path the app sends |

A reader passes each envelope through `Modules\Sdk\Internal\Wire`, which refuses a
wire version this application does not read. An adapter answers with a kernel
outcome type, and a request the stack does not declare is answered as what
stood in the way rather than sent.

What each stack declares it serves is held in memory by
`Modules\Sdk\Internal\WhatEachStackOffers`, for the gate and for `Assessors`,
which answers `KnowingWhatAStackOffers` for a screen drawing a button. It is
never kept: asking again, removing the stack and a screen opening after a
break let go of it. Asking a stack it holds nothing for is the frame's one
reading of that stack, so the request or the button it was asked for waits for
the next frame (`Modules\Kernel\Api\TheReadingWaitsAFrame`), unless the stack
refused the request, which sends nothing more.

Three adapters hold a stream between calls: `Listeners`, for `Hearing`,
`Narrators`, for `HearingTheWalk`, and `StartLines`, for `HearingTheStart`. Each
holds a stack's event stream open for the one screen it belongs to, and reads
it without waiting for what has not arrived: the first for the health summary
and the newest the stack names, the second for the steps of a running walk,
the third for what a running start is waiting for.
