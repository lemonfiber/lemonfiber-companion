<?php

declare(strict_types=1);

use Tests\Support\OurCode;
use Tests\Support\Tree;

// R4 — a gate reads every tree this repository calls its own.
//
// Every rule on this page rests on a list of where to look, and there are five
// of those lists: the analyser's `paths`, the refactorer's `withPaths`, the
// namespaces the architecture expectations resolve, the exemptions that let a
// test do what production code may not, and the file list each text-scanning
// rule builds for itself. Each is a copy of one fact — *what is ours* — and a
// copy that loses a tree loses it in the one way nothing reports: every rule
// resting on it keeps passing, about the trees it still reads.
//
// That is not a worry. It was the state of this repository. `bridge/src` is
// production code, ships inside the application, and is held to the same 100%
// coverage floor as everything else — `phpunit.xml` says so in as many words,
// *being a package is not a reason to be held to a lower bar than the code that
// calls it* — and it was in the analyser's paths, the refactorer's paths and
// the architecture namespaces in exactly none of them. `time()`, an
// `Illuminate\Support\Facades\Cache::get()` and an `echo` planted in
// `bridge/src/Screen.php` made `composer analyse` report *No errors*; a
// non-final `WindowManager` holding a mutable public static passed all 189 Arch
// tests. `bootstrap/Composition` was outside the architecture namespaces for a
// different reason and cost the same thing, and `App` was inside them,
// resolving to the formatter's own source under `vendor`.
//
// So the lists are compared against the one statement of scope that cannot be
// narrowed without also narrowing what is measured: `phpunit.xml`. A tree
// dropped from there stops being covered, which is loud. A tree dropped from
// any of the others was silent until this rule.

/**
 * The directories one list in `phpstan.neon` holds.
 *
 * Read as lines rather than as NEON, which is what every other rule reading this
 * file does: the parser phpstan uses is namespaced into its own build and there
 * is no second one installed.
 *
 * @return list<string>
 */
function neonListUnder(string $key): array
{
    $configuration = (string) file_get_contents(Tree::at('phpstan.neon'));

    // The list is every `- entry` line following the key, comments included:
    // this file writes the reason for a path directly above it, and a block
    // that ended at the first comment would read `paths` as two entries.
    preg_match(
        sprintf('/^[ \t]+%s:[ \t]*$((?:\n[ \t]+(?:-[ \t]+\S+|#.*))*)/m', preg_quote($key, '/')),
        $configuration,
        $block,
    );

    return entriesIn($block[1] ?? '');
}

/**
 * The `- entry` lines of one NEON block.
 *
 * @return list<string>
 */
function entriesIn(string $block): array
{
    preg_match_all('/^[ \t]+-[ \t]+(\S+)[ \t]*$/m', $block, $named);

    return $named[1];
}

/**
 * Every `allowIn` list in `phpstan.neon`, one group per directive.
 *
 * Grouped rather than flattened, because the question is asked of a directive:
 * one that exempts a place tests live has to exempt all of them, and a flat list
 * of every pattern in the file would answer yes for the file as a whole while
 * each directive stayed half-written.
 *
 * @return list<list<string>>
 */
function everyTestExemption(): array
{
    $configuration = (string) file_get_contents(Tree::at('phpstan.neon'));

    // No comments inside this one, unlike `paths`: an `allowIn` here is a run
    // of patterns and the reason is written above the directive, so a block
    // that ended at the first line which is not a pattern is the whole of it.
    preg_match_all('/^[ \t]+allowIn:[ \t]*$((?:\n[ \t]+-[ \t]+\S+)*)/m', $configuration, $blocks);

    return array_map(entriesIn(...), $blocks[1]);
}

/** A path under a tree, of the shape a gate's pattern is written against. */
function aFileIn(string $directory): string
{
    return sprintf('%s/somewhere.php', $directory);
}

/**
 * Whether any of those patterns reaches a file in that tree.
 *
 * @param list<string> $patterns
 */
function reaches(array $patterns, string $directory): bool
{
    return array_any(
        $patterns,
        static fn(string $pattern): bool => $directory === $pattern
            || str_starts_with(aFileIn($directory), sprintf('%s/', $pattern))
            || fnmatch($pattern, aFileIn($directory)),
    );
}

/**
 * Every tree a gate has to read, relative to the root.
 *
 * Source and test alike: a rule about production code and a rule about test
 * files are both rules, and both were written against a list that had lost a
 * tree.
 *
 * @return list<string>
 */
function everyTreeWeOwn(): array
{
    return [
        ...array_map(relativeTree(...), OurCode::sourceTrees()),
        ...array_map(relativeTree(...), OurCode::testTrees()),
    ];
}

/** One tree as `phpunit.xml` writes it, which is already relative. */
function relativeTree(string $tree): string
{
    return trim($tree, '/');
}

it('R4 — every tree phpunit.xml names is on disk', function (): void {
    // The floor, and it is the one number here that is not written down
    // anywhere: a tree `phpunit.xml` names and nothing matches is a suite that
    // runs nothing or a source tree measured by nothing, and every comparison
    // below it would hold vacuously about a set that had quietly emptied.
    $missing = [];

    foreach (everyTreeWeOwn() as $tree) {
        if (glob(Tree::at($tree), GLOB_ONLYDIR) === []) {
            $missing[] = $tree;
        }
    }

    expect($missing)->toBe([], sprintf(
        "phpunit.xml names these and nothing on disk matches:\n  %s\n\n"
        . 'A source tree that matches nothing is measured by nothing, and a testsuite '
        . 'directory that matches nothing is a suite that runs no tests and reports a '
        . "green tick for it.\nEvery comparison in this file is against that list, so an "
        . 'entry matching nothing makes each of them hold about a set that is not there '
        . '(R4).',
        implode("\n  ", $missing),
    ));

    expect(OurCode::sourceTrees())->not->toBe([])
        ->and(OurCode::testTrees())->not->toBe([])
        ->and(OurCode::phpFiles())->not->toBe([]);
});

it('R4 — the analyser reads every tree this repository owns', function (): void {
    $paths = neonListUnder('paths');
    $unread = [];

    foreach (everyTreeWeOwn() as $tree) {
        if (! reaches($paths, $tree)) {
            $unread[] = $tree;
        }
    }

    expect($paths)->not->toBe([], 'phpstan.neon declares no paths, so this rule read nothing');

    expect($unread)->toBe([], sprintf(
        "phpunit.xml holds these to a floor and the analyser does not read them:\n  %s\n\n"
        . 'Every PHPStan-enforced rule on the page — A2 through A9, B1 to B4, C3 to C9, '
        . 'D5, D6, H3, H5, H8, L1, L3 to L6, P1 to P4, Q1 to Q4, S1, S3 — is scoped by '
        . "`paths`, so a tree missing from it is exempt from all of them at once.\n"
        . 'Add the directory to `paths` in phpstan.neon (R4).',
        implode("\n  ", $unread),
    ));
});

it('R4 — the refactorer reads them too', function (): void {
    $rector = (string) file_get_contents(Tree::at('rector.php'));

    preg_match_all("/__DIR__\s*\.\s*'\/([^']+)'/", $rector, $named);

    $paths = $named[1];
    $unread = [];

    foreach (everyTreeWeOwn() as $tree) {
        if (! reaches($paths, $tree)) {
            $unread[] = $tree;
        }
    }

    expect($paths)->not->toBe([], 'rector.php names no path, so this rule read nothing');

    expect($unread)->toBe([], sprintf(
        "phpunit.xml holds these to a floor and the refactorer does not read them:\n  %s\n\n"
        . 'A tree Rector does not visit keeps whatever idiom it was written in, and the '
        . 'dead-idiom gate reports a green tick over the trees it does visit. Add the '
        . 'directory to `withPaths()` in rector.php (R4).',
        implode("\n  ", $unread),
    ));
});

it('R4 — an exemption for the tests names every place tests live', function (): void {
    // This repository keeps its tests in three homes: the root suites under
    // `tests/`, each module's own under `app-modules/<name>/tests`, and the
    // plugin's under `bridge/tests`. All three are testsuites in `phpunit.xml`
    // and all three are analysed.
    //
    // An exemption naming some of them holds the rest to the rules written for
    // production code while its own message says "the tests are the exception".
    // Nineteen of them named one of the three; all twenty named two of the
    // three. What that looks like from inside a test is the analyser refusing a
    // clock, a filesystem or a reflection, and the cure looking like rewriting
    // the test rather than like a path written against one tree of several.
    $places = array_map(relativeTree(...), OurCode::testRoots());
    $lonely = [];

    foreach (everyTestExemption() as $patterns) {
        $named = array_values(array_filter($places, static fn(string $tree): bool => reaches($patterns, $tree)));

        if ($named !== [] && count($named) !== count($places)) {
            $lonely[] = sprintf(
                'the exemption listing %s names %s and not %s',
                implode(', ', $patterns),
                implode(', ', $named),
                implode(', ', array_diff($places, $named)),
            );
        }
    }

    expect(everyTestExemption())->not->toBe([], 'phpstan.neon declares no allowIn, so this rule read nothing');

    expect($lonely)->toBe([], sprintf(
        "These exempt the tests and name some of the places tests live:\n  %s\n\n"
        . 'A module\'s tests and the plugin\'s tests are tests: each is a testsuite in '
        . 'phpunit.xml, each is in the analyser\'s paths, and every one of these messages '
        . 'already says the tests are the exception (R4, R3).',
        implode("\n  ", $lonely),
    ));
});

it('R4 — every tree the coverage floor measures is judged by the architecture rules', function (): void {
    // The third list, and the one with no file of its own to check: the
    // architecture expectations resolve namespaces, not paths, so a tree is in
    // scope exactly when some registered PSR-4 prefix points into it.
    // `OurCode::namespaces()` derives that rather than keeping it, which is what
    // makes this an assertion about the derivation rather than about a list —
    // and a derivation that quietly answered nothing would read the same way
    // from outside as the hand-kept list did.
    $unjudged = [];

    foreach (OurCode::sourceTrees() as $tree) {
        $directories = glob(Tree::at($tree), GLOB_ONLYDIR);

        foreach ($directories === false ? [] : $directories as $directory) {
            if (Tree::filesUnder($directory, '.php') === []) {
                continue;  // an empty module is nothing to judge, and Pest raises on one
            }

            if (! namesSomethingIn($directory)) {
                $unjudged[] = str_replace(sprintf('%s/', Tree::root()), '', $directory);
            }
        }
    }

    expect(OurCode::namespaces())->not->toBe([]);

    expect($unjudged)->toBe([], sprintf(
        "These hold PHP the coverage floor measures and no namespace the rules judge:\n  %s\n\n"
        . 'An architecture expectation resolves a string against the autoloader\'s '
        . 'registered PSR-4 prefixes, so a tree nothing is registered under is a tree '
        . "every rule in ArchitectureTest reads past — H1, H2, H6, final, A1, A3, C3 and "
        . "D4's name half, all of them, silently.\n"
        . 'Register the tree in composer.json (R4).',
        implode("\n  ", $unjudged),
    ));
});

/** Whether one of the judged namespaces is registered against this directory. */
function namesSomethingIn(string $directory): bool
{
    /** @var array<string, array<int, string>> $registered */
    $registered = require Tree::at('vendor/composer/autoload_psr4.php');

    foreach (OurCode::namespaces() as $namespace) {
        foreach ($registered[sprintf('%s\\', $namespace)] ?? [] as $at) {
            $resolved = realpath($at);

            if (is_string($resolved) && str_starts_with(sprintf('%s/', $resolved), sprintf('%s/', $directory))) {
                return true;
            }
        }
    }

    return false;
}
