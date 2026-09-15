<?php

declare(strict_types=1);

use Tests\Support\OurCode;
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
 * Shared by every rule in this file, and asked of {@see OurCode} rather than
 * written out. Six directories were listed here and the repository owns more
 * than six: `scripts/` and `config/` are both in the analyser's paths — *build
 * tooling is code too*, as that file says — and were in none of these rules. A
 * comment reading `TODO: this used to be broken, I found it previously` sat in
 * `scripts/mutation.php` through a full Arch run, carrying four of `K1`'s nine
 * markers, and every one of these rules reported a green tick (`R4`).
 *
 * @return array<string, string> path => contents
 */
function commentedFiles(): array
{
    $found = [];

    $files = [...OurCode::phpFiles(), Tree::at('phpstan.neon')];

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
 * PHP is read as tokens, and everything else by line, because a line scan
 * cannot reach the placement a note left behind actually takes. A comment
 * opened at the end of a line of code sits on a line that begins with the code,
 * so an expression anchored at the start of the line reads the whole file,
 * finds nothing and passes — and this repository writes eight of them. Tokens
 * also stop an attribute reading as a hash comment and a `*` inside a heredoc
 * reading as a docblock, neither of which is a note anybody left.
 *
 * A comment token spanning several lines is handed back one line at a time,
 * keeping its own numbering, because K2 asks its questions of a line.
 *
 * @return array<string, list<string>> path => comment lines
 */
function commentLines(): array
{
    $found = [];

    foreach (commentedFiles() as $path => $contents) {
        $comments = str_ends_with($path, '.php') && ! str_ends_with($path, '.blade.php')
            ? commentTokensIn($contents)
            : commentLinesIn($contents);

        if ($comments !== []) {
            $found[$path] = $comments;
        }
    }

    return $found;
}

/**
 * The comments PHP itself found, split back into lines.
 *
 * @return list<string>
 */
function commentTokensIn(string $contents): array
{
    $found = [];

    foreach (token_get_all($contents) as $token) {
        if (! is_array($token) || ! in_array($token[0], [T_COMMENT, T_DOC_COMMENT], strict: true)) {
            continue;
        }

        foreach (explode("\n", $token[1]) as $offset => $line) {
            $said = trim($line);

            if ($said !== '') {
                $found[] = sprintf('%d: %s', $token[2] + $offset, $said);
            }
        }
    }

    return $found;
}

/**
 * The same, for a file PHP does not parse.
 *
 * `phpstan.neon` carries rule identifiers in comments above the settings they
 * explain, and a Blade template is not a token stream either.
 *
 * @return list<string>
 */
function commentLinesIn(string $contents): array
{
    $found = [];

    foreach (explode("\n", $contents) as $number => $line) {
        $trimmed = ltrim($line);

        if (preg_match('{^(//|\*|/\*|#)}', $trimmed) === 1) {
            $found[] = sprintf('%d: %s', $number + 1, trim($trimmed));
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

/**
 * A marker is a word, not a run of letters.
 *
 * A comment quotes the code it explains, and a type named for a decision —
 * {@see LemonFiber\Kernel\Api\WhatToDoWithIt} — carries one of these markers
 * inside its own name. A letter on either side means the match is part of an
 * identifier rather than a note somebody left behind; a trailing plural is
 * still the note.
 */
function narrativeMarker(string $marker): string
{
    return sprintf('/(?<![A-Za-z])%ss?(?![A-Za-z])/i', preg_quote($marker, '/'));
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
                if (preg_match(narrativeMarker($marker), $line) === 1) {
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

/**
 * The bare identifiers a file gives a meaning to, which are not class names.
 *
 * Two notations, one exception. `@return TSaid` beside `: object` is a bare
 * identifier and says a great deal the signature cannot: which object, tied to
 * what the caller's closure answered. `@param Bindings $bindings` beside
 * `: array` is the same thing said the other way — the alias is where the shape
 * is written down, and `array` is what the signature is able to say.
 *
 * An alias is admitted where the file declares it with `@phpstan-type` or takes
 * it from another with `@phpstan-import-type`, which is exactly the condition
 * PHPStan itself resolves one under: a name neither declared nor imported is an
 * error there and a restated signature here, and both are the same complaint.
 *
 * Read per file rather than per docblock for the reason a template needs it:
 * both are in scope for the file that declares them and refused anywhere else,
 * so a wider read cannot admit a tag a narrower one would have caught.
 *
 * @param list<string> $lines
 *
 * @return list<string>
 */
function templatesIn(array $lines): array
{
    $named = [];

    foreach ($lines as $line) {
        if (preg_match('/@(?:phpstan-)?template(?:-covariant)?\s+([A-Za-z_]\w*)/', $line, $found) === 1) {
            $named[] = $found[1];
        }

        if (preg_match('/@phpstan-(?:import-)?type\s+([A-Za-z_]\w*)/', $line, $found) === 1) {
            $named[] = $found[1];
        }
    }

    return $named;
}

it('K2 — a docblock says what a type cannot', function (): void {
    $offenders = [];

    foreach (commentLines() as $path => $lines) {
        if (in_array($path, EXEMPT, strict: true)) {
            continue;
        }

        $templates = templatesIn($lines);

        foreach ($lines as $line) {
            // A tag whose type holds no shape — no generic, no union, no key
            // type — says exactly what the signature beside it already says.
            // A template parameter is the exception and is why this asks what
            // the file declares: it is a bare identifier carrying the one thing
            // a native type has no way to write down.
            if (
                preg_match('/@(param|return|var)\s+\\\\?([A-Za-z_][A-Za-z0-9_\\\\]*)(\s+\$\w+)?\s*$/', $line, $found) === 1
                && ! in_array($found[2], $templates, strict: true)
            ) {
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

it('K4 — a docblock has something to describe', function (): void {
    // A docblock on the line after another docblock describes nothing. PHP
    // attaches the *second* one to whatever follows, and the first is left
    // pointing at it — so the method it was written for now has none, and the
    // paragraph explaining that method sits above a different one.
    //
    // It happens one way: a method is inserted in front of an anchor, or moved
    // away from it, and the docblock stays where it was. Four were found when
    // this was written, and two of them were strays left behind by a move whose
    // real docblock was intact fifty lines further down — so the file carried
    // the same paragraph twice, with one copy describing the wrong thing.
    //
    // A blank line between them is enough to say the first describes the file
    // rather than the next symbol, which is the one legitimate arrangement.
    $offenders = [];

    foreach (commentedFiles() as $path => $contents) {
        if (in_array($path, EXEMPT, strict: true)) {
            continue;
        }

        foreach (explode("\n", $contents) as $number => $line) {
            $next = explode("\n", $contents)[$number + 1] ?? '';

            if (trim($line) === '*/' && str_starts_with(trim($next), '/**')) {
                $offenders[] = sprintf('%s:%d', $path, $number + 1);
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These docblocks describe the docblock below them:\n  %s\n\n"
        . 'PHP attaches the second one to whatever follows, so the method the first was '
        . 'written for has none, and the paragraph explaining it now sits above something '
        . "else.\nIt happens when a method is inserted in front of its docblock or moved "
        . 'away from it. Move the docblock to the thing it describes, or delete it if the '
        . 'real one is already there — a stray copy is worse than none, because it reads '
        . 'as current. A blank line between them says the first describes the file (K4).',
        implode("\n  ", $offenders),
    ));
});
