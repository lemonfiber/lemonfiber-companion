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
 * How a requirement identifier is spelled, wherever one is looked for.
 *
 * One pattern rather than one per reader. The two rules below ask the same
 * question of two languages, and a spelling that drifted would make one of them
 * quietly narrower than the other — which is the shape of a rule that reports
 * nothing because it is looking for something nobody writes.
 */
const A_REQUIREMENT_IDENTIFIER = '/\b[A-Z]+\d*-R\d+\b/';

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
            ? preg_match_all(A_REQUIREMENT_IDENTIFIER, $line, $named)
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

/**
 * The trees the two languages live in.
 *
 * Named rather than derived, because `phpunit.xml` does not know about them: it
 * declares what the coverage floor measures, which is PHP, and these are the two
 * languages it cannot see. The harnesses are in here as well as the sources, and
 * that is the widening — a test's title sits in the file somebody reads, so an
 * identifier there gestures at a page exactly as one in a comment does.
 */
const WHERE_NATIVE_SOURCE_SITS = ['bridge/resources', 'bridge/android/src', 'bridge/ios/Tests'];

/**
 * Every Kotlin and Swift file this repository wrote, against its contents.
 *
 * Named for this file: the root suites share one namespace (G10).
 *
 * @return array<string, string> path => contents
 */
function everyNativeSourceThisRepositoryWrote(): array
{
    $found = [];

    foreach (whereNativeSourceSits() as $path) {
        $source = file_get_contents($path);

        if (is_string($source)) {
            $found[str_replace(sprintf('%s/', Tree::root()), '', $path)] = $source;
        }
    }

    ksort($found);

    return $found;
}

/**
 * Every Kotlin and Swift file in one of those trees.
 *
 * Per tree rather than over all three, so the rule below can say which of them
 * it read. A walk that answers with everything at once is non-empty while two of
 * its three parts have moved, and the part that goes missing quietly is the one
 * nobody is looking at.
 *
 * @return list<string>
 */
function nativeSourceUnder(string $tree): array
{
    $found = [];

    foreach (['.kt', '.swift'] as $suffix) {
        $found = [...$found, ...Tree::filesUnder(Tree::at($tree), $suffix)];
    }

    return $found;
}

/**
 * All of them, across the three trees.
 *
 * @return list<string>
 */
function whereNativeSourceSits(): array
{
    $found = [];

    foreach (WHERE_NATIVE_SOURCE_SITS as $tree) {
        $found = [...$found, ...nativeSourceUnder($tree)];
    }

    return $found;
}

/**
 * Every line of one native source that names a requirement.
 *
 * Every line, rather than every comment. Kotlin and Swift have nowhere a
 * citation belongs: there is no middle layer on either side, the test titles are
 * prose a reader reads, and a string holding one would be a citation with a
 * runner in front of it rather than a reader. So the question is simply whether
 * the file says a number at all, which is also a question with no comment
 * grammar to get wrong.
 *
 * @return list<string>
 */
function whereANativeSourceNamesOne(string $path, string $source): array
{
    $found = [];

    foreach (explode("\n", $source) as $offset => $line) {
        if (preg_match(A_REQUIREMENT_IDENTIFIER, $line) === 1) {
            $found[] = sprintf('%s:%d: %s', $path, $offset + 1, trim($line));
        }
    }

    return $found;
}

it('GOV-R6 — no Kotlin or Swift source names a requirement', function (): void {
    // Assert the reading before what it says, and per tree: a rule whose
    // subjects are discovered has a state in which it examines nothing, and that
    // state looks exactly like every subject passing.
    foreach (WHERE_NATIVE_SOURCE_SITS as $tree) {
        expect(nativeSourceUnder($tree))->not->toBe([], sprintf(
            '%s holds no Kotlin or Swift, so this rule proved nothing about it',
            $tree,
        ));
    }

    $sources = everyNativeSourceThisRepositoryWrote();
    $found = [];

    foreach ($sources as $path => $source) {
        $found = [...$found, ...whereANativeSourceNamesOne($path, $source)];
    }

    expect($found)->toBe([], sprintf(
        "These name a requirement:\n  %s\n\n"
        . 'Say what the rule asks, in words, and leave the number on the page under '
        . '`.docs/` that carries it. A zero rather than a ratchet, because there were '
        . 'few enough of these to convert in one go and a floor that has reached the '
        . "ground is a rule rather than a promise (GOV-R6).",
        implode("\n  ", $found),
    ));
});

it('GOV-R6 — that reader would recognise one in either language', function (): void {
    // Driven directly rather than by planting a file under `bridge/resources`,
    // which would leave a real source wrong for the length of a run — and a run
    // that is killed leaves what it planted behind.
    expect(whereANativeSourceNamesOne('Rule.kt', "package x\n// N4-R9 — the task switcher"))
        ->toBe(['Rule.kt:2: // N4-R9 — the task switcher']);

    expect(whereANativeSourceNamesOne('RuleTests.swift', '@Test("N4-R19 — a cold start is locked")'))
        ->toBe(['RuleTests.swift:1: @Test("N4-R19 — a cold start is locked")']);

    // And it does not answer yes to everything. A matcher that matched anything
    // would satisfy the loop above while reading nothing, which is the vacuous
    // green every rule in this suite is written against.
    expect(whereANativeSourceNamesOne('Rule.kt', '// the task switcher does not wait for that'))->toBe([]);
});
