<?php

declare(strict_types=1);

use Tests\Support\OurCode;
use Tests\Support\Tree;

// GOV-R6 — a requirement identifier does not belong in a code comment.
//
// Every implementation repository in the org carries none: `sdk-php`, `sdk-ts`
// and `lemonfiber-web` have zero identifiers in source, and the Rust stack keeps
// a middle layer so that its code needs none. This one keeps `.docs/requirements/`
// for the same purpose.
//
// **The argument is not about tidiness.** An identifier in a comment gestures at
// a page rather than saying anything, and it rots the moment that page is
// superseded — silently, because nothing reads a comment and no gate resolved
// one. What is worth keeping is the sentence around it, which says why the code
// is the way it is, and that stays exactly where it is. What the number does is
// move to `.docs/requirements/`, where a page can be revised, a link can reach
// it, and a reader can find the requirement from the code and the code from the
// requirement.
//
// **It exempts itself**, which `Rules::THAT_ONLY_NAME_THEM` already does for the
// three files that can only speak about rules by naming them. A rule against
// writing an identifier into a comment has to write one into a comment to say
// which rule it is.

/**
 * Whether a line is a comment, by the four markers this repository writes one
 * with.
 *
 * `#` is deliberately not among them: in PHP 8 it opens an attribute as often
 * as a comment, and `#[Lazy]` is not prose. A string holding an identifier is a
 * different question and not this one — the rule says comment, and a test's own
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
 * Every comment in one file that names a requirement, and the line it is on.
 *
 * @return list<string>
 */
function whatOneFileStillNames(string $path): array
{
    $source = file_get_contents($path);
    $found = [];

    foreach (explode("\n", is_string($source) ? $source : '') as $at => $line) {
        // `preg_match_all` answers false only for a pattern that cannot
        // compile, and this one is a literal — but the analyser cannot know
        // that, and a cast would be a claim rather than a check.
        $matched = aCommentRatherThanCode($line)
            ? preg_match_all('/\b[A-Z]+\d*-R\d+\b/', $line, $named)
            : 0;

        if (is_int($matched) && $matched > 0) {
            $found[] = sprintf(
                '%s:%d names %s',
                str_replace(sprintf('%s/', Tree::root()), '', $path),
                $at + 1,
                implode(', ', $named[0]),
            );
        }
    }

    return $found;
}

/**
 * The same, across everything this repository wrote.
 *
 * @return list<string>
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

it('GOV-R6 — no comment names a requirement', function (): void {
    $found = whatStillNamesARequirement();

    sort($found);

    expect($found)->toBe([], sprintf(
        "These comments name a requirement:\n  %s\n\n"
        . 'An identifier in a comment gestures at a page rather than saying anything, and rots '
        . 'the moment that page is superseded — silently, because nothing reads a comment. Keep '
        . 'the sentence, which says why the code is the way it is, and put the number on a page '
        . 'under `.docs/requirements/` that says what the requirement asks and what keeps it '
        . 'here (GOV-R6).',
        implode("\n  ", $found),
    ));
});
