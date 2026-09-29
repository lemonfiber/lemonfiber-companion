# Health kept

The adapter between `health` and the app's own database. One class implements
one kernel port over it:

| | |
|---|---|
| `HealthReadingsInTheDatabase` | `HealthReadingsKept`, the newest health reading of each stack, kept between launches |

It owns one table, `health_readings`, created by its own migration in
`database/migrations`: a stack's keyed hash, the shape the reading was written
in, when it was read, and the reading itself as `health` sealed it. Nothing it
is handed can be read without the seal's key.

It depends on `kernel` and Laravel's database, and on nothing else. No other
module names its table.
