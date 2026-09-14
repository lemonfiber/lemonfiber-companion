<?php

declare(strict_types=1);

use Tests\Support\Calls;
use Tests\Support\Tree;

// The files that have to name what they refuse in order to refuse it: this one
// holds the idioms, and the rule-proving harness holds a fixture carrying each
// of them plus the prose explaining why `->only()` suppresses the very check
// that reports it.
const NAMES_WHAT_IT_REFUSES = [
    'tests/Arch/TestConventionsTest.php',
    'tests/Guards/EveryRuleRefusesAViolationTest.php',
];

// How a test is written, checked over the text of the test files themselves.
//
// Reading the source rather than reflecting over loaded tests is deliberate:
// several of these are about things that are true before a test runs, and one
// of them — a committed `->only()` — stops the rest of the suite from running
// at all, so a check that depended on the suite running would be the first
// casualty of the thing it is looking for.

/**
 * Every test file, paired with its contents.
 *
 * @return array<string, string>
 */
function testSources(): array
{
    $found = [];

    foreach (Tree::testFiles() as $path) {
        $contents = file_get_contents($path);

        if (is_string($contents)) {
            $found[str_replace(sprintf('%s/', Tree::root()), '', $path)] = $contents;
        }
    }

    return $found;
}

/**
 * Every top-level function a test file declares, and the namespace it lands in.
 *
 * Read out of the text rather than from `get_defined_functions()`, because the
 * failure this looks for happens *while* the suite loads: the second
 * declaration is a fatal, so by the time anything could ask PHP what exists,
 * the run is over and the message is about the wrong file.
 *
 * @return array<string, list<string>> "namespace\name" => the files declaring it
 */
function helpersByName(): array
{
    $found = [];

    foreach (testSources() as $path => $source) {
        $namespace = preg_match('/^namespace\s+([^;]+);/m', $source, $matched) === 1 ? $matched[1] : '';

        // Functions and file-level constants alike. They collide the same way
        // and for the same reason — one namespace, two files — and PHP reports
        // them differently, which is the only thing that makes them feel like
        // two problems. A duplicate `function` is a fatal; a duplicate `const`
        // is a warning today and a fatal in PHP 9, so the constant is the one
        // that ships. `A_DIGEST` was declared in `FingerprintTest` and again in
        // `StackTest`, and the run said so in a line nobody reads.
        //
        // A `const` inside a class is not this: it is scoped to the class and
        // cannot clash. Anchoring at the start of a line is what tells them
        // apart, since a class constant is always indented here.
        preg_match_all('/^function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/m', $source, $functions);
        preg_match_all('/^const\s+([A-Za-z_][A-Za-z0-9_]*)\s*=/m', $source, $constants);

        foreach ([...$functions[1], ...$constants[1]] as $name) {
            $found[sprintf('%s\\%s', $namespace, $name)][] = $path;
        }
    }

    return $found;
}

it('G5 — a test asserts one way', function (): void {
    $offenders = [];

    foreach (testSources() as $path => $contents) {
        if (in_array($path, NAMES_WHAT_IT_REFUSES, strict: true)) {
            continue;  // this file names the idiom it refuses, in order to refuse it
        }

        if (preg_match('/\bassert[A-Z]\w*\s*\(/', $contents) === 1) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These mix PHPUnit's assertions into a Pest suite:\n  %s\n\n"
        . 'Two assertion idioms in one suite means two failure-message formats, two sets '
        . 'of negation rules and two things to learn before reading a test. Pest\'s '
        . 'expect() is the one this suite uses: expect($x)->toBe($y), not '
        . 'assertSame($y, $x) — which also puts the arguments in the other order, so '
        . 'mixing them is how a reversed expectation gets read as correct (G5).',
        implode("\n  ", $offenders),
    ));
});

it('G1 — nothing mocks a type we do not own', function (): void {
    // Read as text rather than as a namespace expectation: `PHPUnit\` is not a
    // registered PSR-4 prefix here, so an expectation naming it resolves to no
    // files, and Mockery is not installed — which would make the rule report
    // nothing in both halves while looking exactly like a rule that holds.
    $idioms = ['Mockery::', 'Mockery\\', 'createMock(', 'getMockBuilder(', 'createStub(', 'shouldReceive('];
    $offenders = [];

    foreach (testSources() as $path => $contents) {
        if (in_array($path, NAMES_WHAT_IT_REFUSES, strict: true)) {
            continue;
        }

        foreach ($idioms as $idiom) {
            if (str_contains($contents, $idiom)) {
                $offenders[] = sprintf('%s uses %s', $path, $idiom);
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These stand in for a type we do not own:\n  %s\n\n"
        . 'A mock of a foreign type encodes a guess about how that type behaves, and the '
        . 'guess keeps passing after the type changes — the suite stays green while the '
        . 'application is broken. Write a fake for our own port instead, and let the '
        . 'contract test prove the fake and the real adapter agree (G1, G2).',
        implode("\n  ", $offenders),
    ));
});

// A committed `->only()` narrows whatever run loads the file it is in — which
// includes this test, if that run loaded it. That is why this reads the file as
// text rather than reflecting over loaded tests, and why the `rules` CI job runs
// Arch and Templates alone: a `->only()` in a module test cannot suppress a suite
// that never loads module tests. The residual hole is an `->only()` inside
// tests/Arch itself, which narrows the Arch run and takes this with it.
it('G6 — no committed ->only(, and no skip without a reason', function (): void {
    $offenders = [];

    foreach (testSources() as $path => $contents) {
        if (in_array($path, NAMES_WHAT_IT_REFUSES, strict: true)) {
            continue;
        }

        if (str_contains($contents, '->only(')) {
            $offenders[] = sprintf('%s narrows the run to one test', $path);
        }

        foreach (skipsWithoutAReason($contents) as $line) {
            $offenders[] = sprintf('%s:%d skips without saying why', $path, $line);
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These change what the suite runs:\n  %s\n\n"
        . 'A committed `->only()` makes Pest run that one test and report green, so every '
        . "other rule in this repository stops holding and the run says nothing is\n"
        . 'wrong. It is the single most expensive thing that can be committed here, and '
        . 'it is one word. A `->skip()` without a reason is the smaller version: the test '
        . 'stops running and nobody can tell whether it is waiting for something or '
        . 'simply broken (G6).',
        implode("\n  ", $offenders),
    ));
});

/**
 * Every `->skip()` in a test file that leaves the reader no reason.
 *
 * Pest takes the reason as the last argument — `skip('why')`, or
 * `skip($condition, 'why')` — so that is what is asked for, and asked for as
 * the argument rather than as a quote anywhere in the brackets. Empty brackets
 * are one shape of this and the smaller one: `skip(true)` switches a test off
 * just as completely and reads, to a pattern looking for `()`, exactly like a
 * test that runs. So does a condition carrying a string of its own —
 * `skip(fn (): bool => PHP_OS_FAMILY === 'Darwin')` says which machines and
 * still says nothing about why.
 *
 * @return list<int> the lines they are written on
 */
function skipsWithoutAReason(string $source): array
{
    $tokens = token_get_all($source);
    $found = [];

    foreach ($tokens as $at => $token) {
        if (! Calls::isNamed($token, 'skip') || ! isReachedWithAnArrow($tokens, $at)) {
            continue;
        }

        if (! endsWithAReason(Calls::argumentsAt($tokens, $at))) {
            $found[] = $token[2];
        }
    }

    return $found;
}

/**
 * Whether the last of those arguments is the sentence a reader needs.
 *
 * @param list<list<array{int, string, int}|string>> $arguments
 */
function endsWithAReason(array $arguments): bool
{
    return $arguments !== [] && Calls::isALoneString($arguments[count($arguments) - 1]);
}

/**
 * Whether the name at `$at` is being called on an object rather than declared.
 *
 * @param list<array{int, string, int}|string> $tokens
 */
function isReachedWithAnArrow(array $tokens, int $at): bool
{
    $before = $tokens[$at - 1] ?? null;

    return is_array($before) && $before[0] === T_OBJECT_OPERATOR;
}

it('H7 — a test is named for the behaviour it pins', function (): void {
    $offenders = [];

    foreach (testSources() as $path => $contents) {
        if (in_array($path, NAMES_WHAT_IT_REFUSES, strict: true)) {
            continue;
        }

        foreach (['MiscTest', 'GeneralTest', 'VariousTest', 'UtilsTest', 'HelpersTest'] as $grabBag) {
            if (str_ends_with($path, sprintf('/%s.php', $grabBag))) {
                $offenders[] = sprintf('%s is a name that permits anything', $path);
            }
        }

        // `it('should return true')` describes the code. `it('refuses a stack
        // that has never answered')` describes the behaviour, which is the
        // thing that has to keep being true.
        preg_match_all("/\b(?:it|test)\(\s*'(test |should |can |will |must )/i", $contents, $found);

        foreach ($found[1] as $opening) {
            $offenders[] = sprintf('%s opens a description with "%s"', $path, trim($opening));
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These describe a test rather than a behaviour:\n  %s\n\n"
        . 'A description is read in a failure report by somebody who did not write it, '
        . 'so it should say what stopped being true. "should return true" says what the '
        . 'code does; "refuses a stack that has never answered" says what the '
        . "application promises. And a grab-bag file name is how a test file acquires\n"
        . 'everything, for the same reason H1 refuses Manager (H7, H1).',
        implode("\n  ", $offenders),
    ));
});

it('G10 — no two test files share a helper or constant name', function (): void {
    $clashes = [];

    foreach (helpersByName() as $name => $files) {
        if (count($files) > 1) {
            sort($files);
            $clashes[] = sprintf('%s is declared in %s', $name, implode(' and ', $files));
        }
    }

    sort($clashes);

    expect($clashes)->toBe([], sprintf(
        "These declare the same helper twice:\n  %s\n\n"
        . 'A module\'s test files share one namespace, and the root suites share the '
        . 'global one, so two files declaring `fold` are a fatal the moment both load. '
        . 'It cannot ship — but PHP reports the second declaration rather than the '
        . 'clash, so the message names a file that is fine and says nothing about the '
        . "one it collided with.\nName the helper for its subject — `foldReading`, "
        . '`foldReach` — or move it to `tests/Support` where it can be shared on '
        . 'purpose (G10).',
        implode("\n  ", $clashes),
    ));
});
