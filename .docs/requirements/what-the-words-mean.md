# What the words mean

lemonfiber's own words, as the stack's glossary explains them: each with a
short gloss, a longer one for whoever asks, and what else the thing is called.
The code is the glossary half of `N15` in `app-modules/kernel` and
`app-modules/sdk`, drawn by `WhatTheWordsMean`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N15-R3` | A word the glossary carries can be explained, the short form in place and the longer one available without leading | `ShowsWhatItsWordsMean`: a screen that draws one of the stack's words draws the glossary's short line under it, with the way to the longer one a tap away. The room screen does so under a ratio and the stalled screen under a stage. The glossary screen draws the short line under each word and opens the longer one only when asked |
| `N15-R4` | What a word is also called is shown and searchable | The other names are drawn under each word, and `AWord::answers()` searches the word, every other name and every form |
| `N15-R9` | The app defines no word the glossary does not carry, and substitutes no term of its own | Every word and gloss on the screen is the stack's text. `TheWordsExplained` refuses a word with no short gloss rather than drawing one half-explained |
| `N15-R10` | A glossary that could not be read is told apart from idle | A glossary that could not be read is an obstacle, drawn as one. An empty glossary and a search that found nothing each have their own sentence |
| `N15-R11` | Where the held glossary has no entry for a word, the operator may ask the stack for that one word, which is explained on `N15-R3` and `N15-R4`'s terms; asking is the operator's act and never a read per rendered word, and a word the stack has no entry for either is shown as it came | `Explaining::wordOn()`, which `Explainers` answers from `GET /api/explain?word=` and `TheWordsExplained::one()`; a refusal as absent is `WhatWasSaidOfOneWord::unexplained()`, not an obstacle. `WhatTheWordsMean::askTheStack()` asks for the word searched for, only when the operator taps it and only where the held glossary has no entry for it; a word the stack explains joins the held glossary and is drawn, searched and opened as every other word is, and one it has no entry for is said to be unexplained and not offered again. A word drawn elsewhere that the glossary does not carry offers the way to that screen, opened on it (`HowAGlossReads`), and reaching it asks nothing |

The glossary is asked for once and held, so typing narrows what is shown
without asking the machine again.

A word is found by its own name, any other name the glossary gives it, or any
form the glossary says lemonfiber writes it in, whatever the case: the stage
`grabbed` is explained by the entry for `grab`, which lists `grabbed` among its
`forms`. The forms are not drawn, because they are the word rather than another
name for it; a word the glossary does not carry in any form is drawn as it came.

The stack matches a word asked for alone against the same forms, so an
inflection it sent is answered with the entry for the word it belongs to, and
that entry then explains the inflection on this screen as well.

## Asked for, and not drawn yet

`N15-R1`, `N15-R2` and `N15-R5` are about setup and job stages, each a reading
of its own. `N15-R6` to `N15-R8` are the walkthrough, kept on
[watching one thing arrive](watching-one-thing-arrive.md).
