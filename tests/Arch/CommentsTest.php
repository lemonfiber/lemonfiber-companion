<?php

declare(strict_types=1);

use Tests\Support\Tree;

// The two files that must name what they refuse in order to refuse it: this
// one holds the markers, and the fixture registry holds a snippet carrying each
// of them so the rule can be shown to fire.
const EXEMPT = ['tests/Arch/CommentsTest.php', 'tests/Support/Fixtures.php'];

/**
 * How long a paragraph must be before K3 compares it.
 *
 * A one-line summary can honestly resemble another — "Which stack this was
 * about." is a sentence several types have earned. What K3 is looking for is a
 * paragraph of reasoning written out twice.
 */
const SUBSTANTIAL = 120;

/**
 * How alike two paragraphs may be before one of them is a copy.
 *
 * Measured, not picked. Across every docblock in this repository exactly one
 * pair scores above 60% and it scores 90%, so this sits in open space: the real
 * defect is well clear of it and nothing legitimate is anywhere near.
 */
const TOO_ALIKE = 75.0;

// K1/K2 — what a comment is for.
//
// A comment states the situation as it is and why it is that way. It does not
// narrate how it came to be, because the reader does not share the timeline,
// cannot verify the story, and has no way to tell a fact from a memory. "PHP's
// `glob()` has no globstar, so a double star matches one level" stays useful
// forever. "This used to use glob and silently saw one level" is true only
// until someone reads it, and stops being checkable the moment the person who
// wrote it is gone.
//
// Only the crude half of that is mechanical. The markers below are the phrases
// that reliably introduce a story, and the last four are the ones SonarCloud
// flags anyway, so that part of the check earns its keep twice. The rest rests
// on review, which is the honest answer and is counted as one.

/**
 * The repository's own PHP and configuration, read once.
 *
 * Shared by every rule in this file. Each used to build its own file list, and
 * two lists of the directories a repository owns is one fact written twice —
 * the copy that gets a new directory added to it is whichever the person
 * editing happened to open.
 *
 * @return array<string, string> path => contents
 */
function commentedFiles(): array
{
    $found = [];

    $files = [
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('tests'), '.php'),
        ...Tree::filesUnder(Tree::at('phpstan'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
        ...Tree::filesUnder(Tree::at('routes'), '.php'),
        ...Tree::filesUnder(Tree::at('native'), '.php'),
        Tree::at('phpstan.neon'),
    ];

    foreach ($files as $path) {
        $contents = file_get_contents($path);

        if (is_string($contents)) {
            $found[str_replace(sprintf('%s/', Tree::root()), '', $path)] = $contents;
        }
    }

    return $found;
}

/**
 * Every comment line in those files.
 *
 * @return array<string, list<string>> path => comment lines
 */
function commentLines(): array
{
    $found = [];

    foreach (commentedFiles() as $path => $contents) {
        $comments = [];

        foreach (explode("\n", $contents) as $number => $line) {
            $trimmed = ltrim($line);

            if (preg_match('{^(//|\*|/\*|#)}', $trimmed) === 1) {
                $comments[] = sprintf('%d: %s', $number + 1, trim($trimmed));
            }
        }

        if ($comments !== []) {
            $found[$path] = $comments;
        }
    }

    return $found;
}

/**
 * One docblock, split into prose paragraphs.
 *
 * Tags end a paragraph as surely as a blank line does: `@param` and the lines
 * under it are a different kind of writing, and folding them into the prose
 * above would compare a sentence against a type.
 *
 * @return list<string>
 */
function paragraphsOf(string $block): array
{
    $paragraphs = [];
    $sentence = [];

    foreach (explode("\n", $block) as $line) {
        $said = trim(preg_replace('{^\s*/?\*+/?}', '', $line) ?? '');

        if ($said !== '' && ! str_starts_with($said, '@')) {
            $sentence[] = $said;

            continue;
        }

        if ($sentence !== []) {
            $paragraphs[] = implode(' ', $sentence);
            $sentence = [];
        }
    }

    if ($sentence !== []) {
        $paragraphs[] = implode(' ', $sentence);
    }

    return $paragraphs;
}

/**
 * Every docblock in the repository, by the line it opens on.
 *
 * @return array<string, array<int, list<string>>> path => line => paragraphs
 */
function docblockParagraphs(): array
{
    $found = [];

    foreach (commentedFiles() as $path => $contents) {
        $blocks = [];

        if (preg_match_all('{/\*\*.*?\*/}s', $contents, $matches, PREG_OFFSET_CAPTURE) === false) {
            continue;
        }

        foreach ($matches[0] as [$block, $offset]) {
            $blocks[substr_count(substr($contents, 0, $offset), "\n") + 1] = paragraphsOf($block);
        }

        if ($blocks !== []) {
            $found[$path] = $blocks;
        }
    }

    return $found;
}

/**
 * Pairs of paragraphs that say the same thing, named for the failure message.
 *
 * @param list<string> $paragraphs
 *
 * @return list<string>
 */
function repeatedIn(array $paragraphs): array
{
    $said = array_map(
        static fn(string $paragraph): string => trim((string) preg_replace(
            ['{[^a-z ]}', '{\s+}'],
            ['', ' '],
            strtolower($paragraph),
        )),
        $paragraphs,
    );

    $repeats = [];

    foreach ($said as $one => $first) {
        foreach ($said as $other => $second) {
            // Only forward, so a pair is reported once rather than twice.
            if ($other <= $one) {
                continue;
            }

            // A one-line summary can honestly resemble another. What this is
            // looking for is a paragraph of reasoning written out twice.
            if (strlen($first) < SUBSTANTIAL || strlen($second) < SUBSTANTIAL) {
                continue;
            }

            similar_text($first, $second, $alike);

            if ($alike > TOO_ALIKE) {
                $repeats[] = sprintf(
                    '%d%% alike: "%s…" and "%s…"',
                    (int) $alike,
                    substr($paragraphs[$one], 0, 60),
                    substr($paragraphs[$other], 0, 60),
                );
            }
        }
    }

    return $repeats;
}

it('K1 — a comment says what is true, not what happened', function (): void {
    // Split so that this list is not itself a run of the phrases it refuses.
    $markers = [
        'previous' . 'ly', 'used ' . 'to be', 'was ' . 'broken', 'I ' . 'found',
        'now ' . 'fixed', 'TO' . 'DO', 'FIX' . 'ME', 'HA' . 'CK', 'X' . 'XX',
    ];

    $offenders = [];

    foreach (commentLines() as $path => $lines) {
        if (in_array($path, EXEMPT, strict: true)) {
            continue;
        }

        foreach ($lines as $line) {
            foreach ($markers as $marker) {
                if (stripos($line, $marker) !== false) {
                    $offenders[] = sprintf('%s %s', $path, $line);
                }
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These comments tell a story rather than state a situation:\n  %s\n\n"
        . 'Say what is true now and why, in a form the next reader can check against '
        . 'the code in front of them. A reader who was not there cannot tell a fact '
        . 'from a recollection, and the recollection is the half that goes stale. Where '
        . "a comment earns its place by explaining a trap, keep the trap and drop the\n"
        . 'tense: the behaviour of a language or a library is a fact and stays (K1).',
        implode("\n  ", $offenders),
    ));
});

it('K2 — a docblock says what a type cannot', function (): void {
    $offenders = [];

    foreach (commentLines() as $path => $lines) {
        if (in_array($path, EXEMPT, strict: true)) {
            continue;
        }

        foreach ($lines as $line) {
            // A tag whose type holds no shape — no generic, no union, no key
            // type — says exactly what the signature beside it already says.
            if (preg_match('/@(param|return|var)\s+\\\\?[A-Za-z_][A-Za-z0-9_\\\\]*(\s+\$\w+)?\s*$/', $line) === 1) {
                $offenders[] = sprintf('%s %s', $path, $line);
            }

            if (preg_match('/@(author|package|version|copyright|since|created)\b/', $line) === 1) {
                $offenders[] = sprintf('%s %s', $path, $line);
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These docblock tags restate the signature:\n  %s\n\n"
        . 'A native type is checked, refactored with the code, and cannot drift. A tag '
        . 'repeating it is a second copy that can, and it buys nothing — the analyser '
        . 'already knew. A tag earns its place by saying what the type cannot: '
        . '`list<Finding>`, `array<string, Kind>`, `non-empty-string`, `int<0, 100>`. '
        . 'Authorship and version belong in git (K2).',
        implode("\n  ", $offenders),
    ));
});

it('K3 — a docblock does not say the same thing twice', function (): void {
    // The defect this exists for was a paragraph pasted twice into
    // `PairingIsNotReadable`, with every `{@see}` and every backticked class
    // name stripped out of the copy. What was left read as a sentence with
    // holes in it — "rather than , which is what every other refusal here
    // extends" — and it sat there through review, because a reader skims a
    // docblock they have already read the top of.
    //
    // Compared as prose rather than as lines. Two paragraphs that differ only
    // in the names they lost are not two facts, and the copy is the one that
    // goes stale: somebody correcting the reasoning corrects the half they can
    // see.
    //
    // The threshold is measured rather than picked. Across every docblock in
    // this repository exactly one pair scores above 0.60, and it scores 0.90 —
    // so 0.75 sits in open space with the real defect well clear of it, and
    // nothing legitimate anywhere near. Short paragraphs are skipped: two
    // one-line summaries can honestly resemble each other.
    $offenders = [];

    foreach (docblockParagraphs() as $path => $blocks) {
        if (in_array($path, EXEMPT, strict: true)) {
            continue;
        }

        foreach ($blocks as $at => $paragraphs) {
            foreach (repeatedIn($paragraphs) as $repeat) {
                $offenders[] = sprintf('%s:%d %s', $path, $at, $repeat);
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These docblocks say the same thing twice:\n  %s\n\n"
        . 'Two paragraphs saying one thing are two copies of it, and only one of them '
        . 'gets corrected — whichever the next reader happens to be looking at. The '
        . 'usual cause is a paste, and the paste is usually the damaged one: a copy '
        . 'that lost its `{@see}` tags and its backticked names reads as a sentence '
        . 'with holes in it, which is exactly what a skimming reader does not stop on. '
        . 'Keep the paragraph that still names things, and delete the other (K3).',
        implode("\n  ", $offenders),
    ));
});
