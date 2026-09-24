<?php

declare(strict_types=1);

use Tests\Support\Catalogue;

// A translated screen keeps the words that are also names.
//
// One concept uses one term consistently across all surfaces and
// messages. A locale is where that is hardest to see: the term stays consistent
// *within* the translated screens, so nothing inside them disagrees, and the
// disagreement is with the compose file, the subcommand and the documentation —
// surfaces this suite cannot read. The locale rule is the same requirement made
// checkable from here, by asking that the source locale and every other one
// name the same things.
//
//
// `G2` already asks that the plain phrasing not stop an operator learning the
// real term, because they will need it to search for help. A second language
// puts that under more pressure, because it hands a translator a word that fits
// the sentence and names nothing.
//
// A *form* is a group of services that start and stop together. Dutch has an
// ordinary word a dictionary gives for *form*, and it means a form somebody
// fills in — a different object. An operator reading it holds a word that
// appears in no compose file, no subcommand, and no page of the documentation
// they would search the moment the screen stops being enough.
//
// **The rule needs no list of wrong words, and that is the point.** Enumerating
// the translations to refuse fails open the first time somebody picks one
// nobody thought of. This asks the opposite question: where the source names a
// thing, every other locale must name it too. What a translator did instead
// never has to be guessed at.
//
// It is checked against the catalogues rather than against the screens because
// a catalogue is where a name is translated. A screen holds a key.

/**
 * The names lemonfiber gives its own things.
 *
 * Declared rather than inferred, which is asked for and is the only
 * way this can work: nothing in a sentence says whether a noun is text or a
 * label, and a name added to the product without being written down here is a
 * name a translator will reasonably translate.
 *
 * A name earns a line by appearing somewhere this product does not control —
 * a filename, a subcommand, an argument — where it has one spelling and an
 * operator will meet it again.
 */
const THE_NAMES_THIS_PRODUCT_GIVES = [
    'form' => 'names a combination of profiles a stack declares, and an argument to the verbs that act on one',
    'stack' => 'names the machine in every command, every path and every page of the documentation',
];

/**
 * Whether a line uses the name as a word rather than as the start of one.
 *
 * Both boundaries, and the near miss is the reason: *formulier* begins with
 * *form* and is the exact word this rule exists to refuse, so a rule anchored
 * only at the front would read the mistranslation as a match and pass. `s?`
 * because an English plural is the same name.
 *
 * Named for this file: the root suites share one namespace (G10).
 */
function aLineNaming(string $line, string $name): bool
{
    return preg_match(sprintf('/\b%ss?\b/iu', preg_quote($name, '/')), $line) === 1;
}

it('G2-R14 — every locale keeps the names this product gives its own things', function (): void {
    $locales = Catalogue::locales();
    $first = $locales[0];
    $source = Catalogue::all($first);

    expect($source)->not->toBeEmpty('No source catalogue was read, so this compares nothing.');

    $translated = [];

    foreach ($locales as $locale) {
        if ($locale === $first) {
            continue;
        }

        foreach (Catalogue::all($locale) as $key => $said) {
            foreach (array_keys(THE_NAMES_THIS_PRODUCT_GIVES) as $name) {
                if (! aLineNaming($source[$key] ?? '', $name) || aLineNaming($said, $name)) {
                    continue;
                }

                $translated[] = sprintf('%s/%s drops `%s`: %s', $locale, $key, $name, $said);
            }
        }
    }

    sort($translated);

    expect($translated)->toBe([], sprintf(
        "These lines translate a name rather than the text around it:\n  %s\n\n"
        . 'Each names a thing in the source locale and does not name it here. The word that '
        . 'replaced it fits the sentence and appears in no compose file, no subcommand and no '
        . 'page of the documentation — so an operator reading it holds two names for one thing '
        . 'and nothing that says they are the same thing. Translate the sentence and leave the '
        . "name alone (G2-R14).\n\n"
        . 'If the line is right and this is wrong, the name is not a label after all: take it out '
        . 'of `THE_NAMES_THIS_PRODUCT_GIVES` and say why there.',
        implode("\n  ", $translated),
    ));
});
