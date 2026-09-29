# Health

What this application decides about a stack's diagnostic report that the stack
does not decide for it. Three are queries over the findings a report holds:

| | |
|---|---|
| `WorstFirst` | The findings ordered by severity, worst first |
| `InCategory` | The findings from one category of checks |
| `TheCauseBeforeItsSymptoms` | Findings that share a cause, grouped, with the cause first |

And two values about what the core publishes on its event stream:

| | |
|---|---|
| `WhatWasHeardSoFar` | The health summary a screen holding the stream has heard, whether it still stands, and when to open the stream again |
| `WhatTheWalkSaidSoFar` | The step a running walk last said, whether it still stands, and when to open the stream again |

And one decision about what the phone keeps of it between launches:

| | |
|---|---|
| `KeepingTheLastReading` | The newest summary per stack, sealed before it is kept, handed back on opening with when it was read, and forgotten after thirty days or where it does not read |

It depends on `kernel` alone. The report is read by `Questions` in `sdk`, and
the repairs a stack offers are asked for through the `Mending` port. A kept
summary is sealed through `Sealed` and stored through `HealthReadingsKept`,
which `health-kept` implements.
