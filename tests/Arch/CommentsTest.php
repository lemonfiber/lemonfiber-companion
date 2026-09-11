<?php

declare(strict_types=1);

use Tests\Support\Tree;

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
 * Every comment line in the repository's own PHP and configuration.
 *
 * @return array<string, list<string>> path => comment lines
 */
function commentLines(): array
{
    $found = [];

    $files = [
        ...Tree::filesUnder(Tree::at('app'), '.php'),
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('tests'), '.php'),
        ...Tree::filesUnder(Tree::at('phpstan'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
        ...Tree::filesUnder(Tree::at('routes'), '.php'),
        Tree::at('phpstan.neon'),
    ];

    foreach ($files as $path) {
        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            continue;
        }

        $comments = [];

        foreach (explode("\n", $contents) as $number => $line) {
            $trimmed = ltrim($line);

            if (preg_match('{^(//|\*|/\*|#)}', $trimmed) === 1) {
                $comments[] = sprintf('%d: %s', $number + 1, trim($trimmed));
            }
        }

        if ($comments !== []) {
            $found[str_replace(sprintf('%s/', Tree::root()), '', $path)] = $comments;
        }
    }

    return $found;
}

it('K1 — a comment says what is true, not what happened', function (): void {
    // Split so that this list is not itself a run of the phrases it refuses.
    $markers = [
        'previous' . 'ly', 'used ' . 'to be', 'was ' . 'broken', 'I ' . 'found',
        'now ' . 'fixed', 'TO' . 'DO', 'FIX' . 'ME', 'HA' . 'CK', 'X' . 'XX',
    ];

    $offenders = [];

    foreach (commentLines() as $path => $lines) {
        if ($path === 'tests/Arch/CommentsTest.php') {
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
        if ($path === 'tests/Arch/CommentsTest.php') {
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
