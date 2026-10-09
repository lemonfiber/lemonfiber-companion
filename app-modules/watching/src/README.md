# Watching

What the app decides about how a member chose titles to play on this phone.
One decision so far, about the languages they hear and read titles in:

| | |
|---|---|
| `KeepingTheirLanguages` | What the member signed in to a stack chose to hear titles in (the title's original, Dutch or English) and to read subtitles in (none, Dutch or English). It is kept sealed with whose choice it is, and handed back for that member alone. Where nothing is chosen, or the choice does not read, the title plays as it comes |

The choice belongs to the phone in the member's hand, not to the household, so
the house is never told it. Profile offers it, and the player is handed it each
time a title opens. A title with no sound in the chosen language plays its own
sound, and one with no subtitles in it plays with none. The players decide
that from the tracks they find, and nothing here refuses a title for it.

It depends on `kernel`, on `store-kit` from its store, and on Laravel's
database in its store alone. A choice is sealed through `Sealed` and stored
through `LanguagesKept`, a port this module declares in `Internal` and answers
in `Internal/Store`:

| | |
|---|---|
| `LanguagesInTheDatabase` | `LanguagesKept`, over the app's own database: one table, `watching_languages`, created by this module's migration in `database/migrations`, and queried through `store-kit`'s `ATableOfReadings` |

A row holds a stack's keyed hash, the shape the choice was written in, when it
was chosen, and the choice as this module sealed it. A choice is not a reading
and grows no older, so nothing forgets it by age. It goes when the stack leaves
the phone, or when everything the phone keeps is cleared.

## What is deliberately not here

**Where a member is in a title.** The core keeps the place (`KeepingThePlace`),
and the phone holds no second copy of it.
