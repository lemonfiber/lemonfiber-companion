<?php

declare(strict_types=1);

use Tests\Support\Catalogue;

// G2-R13 — an acronym an operator reads is explained, or declared ordinary.
//
// A domain term used with nothing attached is a defect, and the hard part is
// that most jargon cannot be told from ordinary writing by a machine. An
// acronym can be, and it is jargon at its sharpest: somebody who does not know
// `NZB` cannot infer it from the letters, cannot look it up under a word they
// never saw spelled out, and has nothing to go on but the sentence around it.
//
// **The product already holds this and this app did not.** `lemonfiber` refuses
// the class across every string literal it ships, with a list of words declared
// ordinary and a glossary behind the rest. Nothing here refused anything: the
// four capital runs in these catalogues are bare labels — `VPN`, `MB`, `GB`,
// `TB`, each the whole of its line — and the guard that class is exempt from is
// a guard this repository does not have. So the requirement was satisfied by
// what nobody had written yet, and the first sentence carrying an acronym would
// have shipped.
//
// **The list starts empty and grows by decision, which is the point.** Copying
// the thirty-seven words the stack has declared would put a second copy of a
// list in a second language in a second repository, and the two would disagree
// the first time either moved — quietly, because neither is read by the other.
// What a word costs here is one line and a reason, and the requirement is
// written around exactly that cost: not deciding is what it refuses.
//
// **Only inside sentences**, which is the stack's limit and is taken on
// deliberately rather than inherited by accident. A bare label is looked up
// rather than read: `MB` beside a number is a unit, and demanding a gloss for
// it would make the rule noise on the day it is most needed. What that costs is
// real — an acronym alone on a screen is not checked here — and it is the same
// cost the product accepted, which is worth more than a stricter rule that
// disagrees with the surface beside it.

/**
 * The words this app declares ordinary, and why each is.
 *
 * Empty because nothing has needed one yet. A word earns a line when an
 * operator would read it in a sentence and not need it explained — and the
 * reason is the line's whole job: it is what somebody reads before adding the
 * next one.
 *
 * @var array<string, string>
 */
const WORDS_AN_OPERATOR_ALREADY_KNOWS = [];

/**
 * Whether a line was written to be read rather than looked up.
 *
 * Several words, at least one of them ordinary lower-case prose. A label, a
 * key and a unit all fail it, which is what keeps this from demanding a gloss
 * for the `MB` after a number.
 *
 * Named for this file: the root suites share one namespace (G10).
 */
function readsAsASentence(string $said): bool
{
    $words = preg_split('/\s+/', trim($said), flags: PREG_SPLIT_NO_EMPTY);
    $words = is_array($words) ? $words : [];

    if (count($words) < 2) {
        return false;
    }

    return array_any($words, static fn(string $word): bool => preg_match('/^[a-z]{2,}$/u', $word) === 1);
}

/**
 * The capital runs in one line, less the ones that are not acronyms.
 *
 * A placeholder is the product's own name for a value and is replaced before
 * anybody reads it; a word in capitals for emphasis is the word, not an
 * acronym, and is left to the reader.
 *
 * @return list<string>
 */
function theAcronymsIn(string $said): array
{
    $prose = preg_replace('/:[A-Za-z_][A-Za-z0-9_]*/', ' ', $said);

    preg_match_all('/\b[A-Z][A-Z0-9]+\b/', is_string($prose) ? $prose : '', $found);

    return array_values(array_unique($found[0]));
}

it('G2-R13 — every acronym in a sentence is explained or declared ordinary', function (): void {
    $unexplained = [];

    foreach (Catalogue::locales() as $locale) {
        foreach (Catalogue::all($locale) as $key => $said) {
            if (! readsAsASentence($said)) {
                continue;
            }

            foreach (theAcronymsIn($said) as $short) {
                if (! array_key_exists($short, WORDS_AN_OPERATOR_ALREADY_KNOWS)) {
                    $unexplained[] = sprintf('%s/%s reads `%s`: %s', $locale, $key, $short, $said);
                }
            }
        }
    }

    sort($unexplained);

    expect($unexplained)->toBe([], sprintf(
        "An operator is shown these and given nothing to make sense of them:\n  %s\n\n"
        . 'Somebody who does not know the word cannot infer it from the letters and cannot look '
        . 'it up under a word they never saw spelled out. Explain it where the sentence is, or '
        . 'add it to `WORDS_AN_OPERATOR_ALREADY_KNOWS` with the reason an operator would already '
        . "know it (G2-R13).\n\n"
        . 'Neither costs much. Not deciding is what this refuses.',
        implode("\n  ", $unexplained),
    ));
});
