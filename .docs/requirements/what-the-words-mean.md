# What the words mean

lemonfiber's own words, as the stack's glossary explains them: each with a
short gloss, a longer one for whoever asks, and what else the thing is called;
and what setup settled, as facts about the stack rather than as setup offered
again. The code is the glossary half of `N15` in `app-modules/kernel` and
`app-modules/sdk`, drawn by `WhatTheWordsMean`, and the settings screen,
`WhatThisStackIsSetTo`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N15-R3` | A word the glossary carries can be explained, the short form in place and the longer one available without leading | `ShowsWhatItsWordsMean`: a screen that draws one of the stack's words draws the glossary's short line under it, with the way to the longer one a tap away. The room screen does so under a ratio and the stalled screen under a stage. The glossary screen draws the short line under each word and opens the longer one only when asked |
| `N15-R4` | What a word is also called is shown and searchable | The other names are drawn under each word, and `AWord::answers()` searches the word, every other name and every form |
| `N15-R9` | The app defines no word the glossary does not carry, and substitutes no term of its own | Every word and gloss on the screen is the stack's text. `TheWordsExplained` refuses a word with no short gloss rather than drawing one half-explained |
| `N15-R1` | What setup settled is shown as facts about the stack, and showing them is not offering first-run setup | `WhatThisStackIsSetTo` draws every setting the stack lists, as it holds it now, and says that what setup decided is among them and that changing one is reconfiguration. It singles none out: the stack does not say which settings setup wrote, recorded below |
| `N15-R2` | Where an operator reaches first-run setup, it is declined with the reason rather than absent | The settings screen, where somebody looking to set the stack up again would look, says first-run setup is not done from this app and why: setting a stack up is what makes it reachable from a phone at all. It is said in words and offered as nothing. The first run says the same before pairing |
| `N15-R10` | A glossary or setup state that could not be read is told apart from idle | A glossary that could not be read is an obstacle, drawn as one. An empty glossary and a search that found nothing each have their own sentence. The settings that could not be read are an obstacle too, and a stack with nothing set has a sentence of its own |

The glossary is asked for once and held, so typing narrows what is shown
without asking the machine again.

A word is found by its own name, any other name the glossary gives it, or any
form the glossary says lemonfiber writes it in, whatever the case: the stage
`grabbed` is explained by the entry for `grab`, which lists `grabbed` among its
`forms`. The forms are not drawn, because they are the word rather than another
name for it; a word the glossary does not carry in any form is drawn as it came.

`N15-R5` to `N15-R8`, and the half of `N15-R10` about a stage, are the
walkthrough, kept on [watching one thing arrive](watching-one-thing-arrive.md).

## What the wire does not carry

What setup settled — the data root, the protocols, the service user — is
carried whole only by the `setup` envelope, which only the command line writes.
Setup's own read answers with `wizard`, whose plan is empty once setup has
applied, and the SDK names no path for that read, so this app cannot ask
whether a stack has setup left to do. The settings listing carries what setup
wrote among everything else, and says nothing about which. So `N15-R1` is kept
as far as the settings go and no further, and the settings screen says setup is
done at the machine on every stack rather than only on one that has setup left.
`WhatTheContractDoesNotCarryTest` holds the gap.
