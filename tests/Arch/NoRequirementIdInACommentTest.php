<?php

declare(strict_types=1);

use Tests\Support\OurCode;
use Tests\Support\Tree;

// GOV-R6 — a requirement identifier does not belong in a code comment.
//
// The spec is unambiguous and this repository was the org's one exception. Every
// other implementation repo carries none: `sdk-php`, `sdk-ts` and
// `lemonfiber-web` have zero identifiers in source, and the Rust stack keeps a
// middle layer so that its code needs none. This one had a few thousand.
//
// **The argument is not about tidiness.** An identifier in a comment gestures at
// a page rather than saying anything, and it rots the moment that page is
// superseded — silently, because nothing reads a comment and no gate resolved
// one. What is worth keeping is the sentence around it, which says why the code
// is the way it is, and that stays exactly where it is. What moves is the
// number, to `.docs/requirements/`, where a page can be revised, a link can
// reach it, and a reader can find the requirement from the code and the code
// from the requirement.
//
// **A ratchet rather than a sweep, because the sweep is long.** Four hundred
// and forty-three files still hold one. Converting them is editorial — the
// identifiers are woven into the sentences rather than sitting beside them —
// and a rule that failed until the last one was done would be switched off on
// the first afternoon. So this pins what is left and refuses any increase, and
// refuses a decrease that was not written down: progress that is not recorded
// is progress the next branch can undo without noticing.
//
// This is the shape `Q-R67` uses for an issue backlog and the version manifest
// uses for a coverage floor, for the same reason in each case: a number that may
// only fall converges, and one that is merely watched does not.
//
// **It exempts itself**, which `Rules::THAT_ONLY_NAME_THEM` already does for the
// three files that can only speak about rules by naming them. A rule against
// writing an identifier into a comment has to write one into a comment to say
// which rule it is.

/**
 * What is left to convert, and what this may not exceed.
 *
 * Lower it when a module is converted — the failure below says what to lower it
 * to. It reaches zero when the last comment is rewritten, and this file and the
 * count go together when the org's gate takes over.
 */
const IDENTIFIERS_STILL_IN_COMMENTS = 1443;

/**
 * Whether a line is a comment, by the four markers this repository writes one
 * with.
 *
 * `#` is deliberately not among them: in PHP 8 it opens an attribute as often
 * as a comment, and `#[Lazy]` is not prose. A string holding an identifier is a
 * different question and not this one — `GOV-R6` says comment, and a test's own
 * title is read by a runner rather than by somebody reading the code.
 *
 * Named for this file: the root suites share one namespace (G10).
 */
function aCommentRatherThanCode(string $line): bool
{
    $said = ltrim($line);

    return str_starts_with($said, '//')
        || str_starts_with($said, '*')
        || str_starts_with($said, '/*')
        || str_starts_with($said, '{{--');
}

/**
 * Every comment in one file that names a requirement, against how many it names.
 *
 * @return list<array{string, int}>
 */
function whatOneFileStillNames(string $path): array
{
    $source = file_get_contents($path);
    $found = [];

    foreach (explode("\n", is_string($source) ? $source : '') as $line) {
        // `preg_match_all` answers false only for a pattern that cannot
        // compile, and this one is a literal — but the analyser cannot know
        // that, and a cast would be a claim rather than a check.
        $matched = aCommentRatherThanCode($line)
            ? preg_match_all('/\b[A-Z]+\d*-R\d+\b/', $line)
            : 0;

        $count = is_int($matched) ? $matched : 0;

        if ($count > 0) {
            $found[] = [str_replace(sprintf('%s/', Tree::root()), '', $path), $count];
        }
    }

    return $found;
}

/**
 * The same, across everything this repository wrote.
 *
 * @return list<array{string, int}>
 */
function whatStillNamesARequirement(): array
{
    $found = [];

    foreach (OurCode::phpFiles() as $path) {
        // This file has to name the rule it keeps in order to say which rule it
        // is, which `Rules::THAT_ONLY_NAME_THEM` already allows for the three
        // files that can only speak about rules by naming them.
        if ($path === Tree::at('tests/Arch/NoRequirementIdInACommentTest.php')) {
            continue;
        }

        $found = [...$found, ...whatOneFileStillNames($path)];
    }

    return $found;
}

it('GOV-R6 — the identifiers left in a comment may only fall', function (): void {
    $found = whatStillNamesARequirement();
    $left = array_sum(array_column($found, 1));

    expect($left)->toBeLessThanOrEqual(IDENTIFIERS_STILL_IN_COMMENTS, sprintf(
        "This change writes %d requirement identifiers into comments where %d were left.\n\n"
        . 'An identifier in a comment gestures at a page rather than saying anything, and rots '
        . 'the moment that page is superseded — silently, because nothing reads a comment. Keep '
        . 'the sentence, which says why the code is the way it is, and put the number on a page '
        . "under `.docs/requirements/` that says what the requirement asks and what keeps it "
        . "here (GOV-R6).",
        $left,
        IDENTIFIERS_STILL_IN_COMMENTS,
    ));

    expect($left)->toBe(IDENTIFIERS_STILL_IN_COMMENTS, sprintf(
        "There are %d left and this file still says %d.\n\n"
        . 'Lower it to %d. A ratchet that is not written down is not one: the next branch can '
        . 'put them back and nothing here would notice, which is the whole reason the number '
        . "lives in the repository rather than in somebody's head (GOV-R6).",
        $left,
        IDENTIFIERS_STILL_IN_COMMENTS,
        $left,
    ));
});
