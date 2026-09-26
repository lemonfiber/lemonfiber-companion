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

The glossary is asked for once and held, so typing narrows what is shown
without asking the machine again.

A word is found by its own name, any other name the glossary gives it, or any
form the glossary says lemonfiber writes it in, whatever the case: the stage
`grabbed` is explained by the entry for `grab`, which lists `grabbed` among its
`forms`. The forms are not drawn, because they are the word rather than another
name for it; a word the glossary does not carry in any form is drawn as it came.

## Asked for, and not drawn yet

`N15-R1`, `N15-R2` and `N15-R5` are about setup and job stages, each a reading
of its own. `N15-R6` to `N15-R8` are the walkthrough, kept on
[watching one thing arrive](watching-one-thing-arrive.md).
