<?php

declare(strict_types=1);

use function Tests\Support\documentedRules;

use Tests\Support\Fixture;
use Tests\Support\Fixtures;
use Tests\Support\Proof;
use Tests\Support\Tree;

// The third leg.
//
// TheRulesAreRealTest says a documented rule has an artifact carrying its
// identifier. That is necessary and not sufficient: an artifact can carry the
// right identifier, run on every commit, and check nothing at all. A namespace
// no autoloader registers resolves to no files. An `expect()` list holding both
// a function name and a namespace voids itself. A Pest expectation can simply
// not report what its name says it reports. All three read as a green tick, and
// all three were sitting in this repository at once.
//
// So this plants the smallest violation of every rule, runs the machine that
// enforces it, and asserts the rule reported — and names the fixture in the
// report, so a rule cannot pass on somebody else's violation.
//
// A rule with no fixture fails here. That is the part worth having: it makes "I
// did not check this one" impossible to leave implicit, which is the condition
// the three above needed in order to survive.

beforeEach(function (): void {
    writeFixtures();
});

afterEach(function (): void {
    removeFixtures();
});

it('has a fixture for every rule that claims to be enforced', function (): void {
    $covered = array_map(static fn(Fixture $f): string => $f->rule, Fixtures::all());
    $missing = [];

    foreach (documentedRules() as $id => $claim) {
        // A rule nobody has built yet has nothing to violate. The ratchet in
        // TheRulesAreRealTest is what keeps that honest.
        if ($claim === 'planned') {
            continue;
        }

        if (! in_array($id, $covered, strict: true)) {
            $missing[] = sprintf('%s (claims "%s")', $id, $claim);
        }
    }

    expect($missing)->toBe([], sprintf(
        "These rules have never been shown to refuse anything:\n  %s\n\n"
        . 'Add the smallest snippet that violates the rule to Tests\\Support\\Fixtures, '
        . 'with the mark it must leave — the identifier for an analyser rule, the '
        . 'failing test description for a suite rule. Where no snippet can break it, '
        . 'say so with Fixture::notDrivable() and give the reason, which is an answer '
        . 'rather than a gap.',
        implode("\n  ", $missing),
    ));
});

it('the analyser reports every rule it is supposed to', function (): void {
    $fixtures = array_values(array_filter(
        Fixtures::all(),
        static fn(Fixture $f): bool => $f->proof === Proof::Analyser,
    ));

    $reported = analyserFindings();

    // Told apart from "every rule stayed quiet" on purpose. A malformed module
    // manifest stops Larastan booting, the analyser writes a stack trace to
    // stderr and nothing to stdout, and every fixture below then reads as a
    // rule that did not fire — forty findings, all of them wrong, none of them
    // the real one.
    expect($reported)->not->toBeNull(
        'The analyser produced no readable output at all, so nothing below was measured. '
        . 'Run `vendor/bin/phpstan analyse .rule-fixtures` and read stderr: a bootstrap '
        . 'failure looks exactly like every rule going silent at once.',
    );

    $silent = [];

    foreach ($fixtures as $fixture) {
        $path = sprintf('%s/%s', Fixtures::ANALYSER_TREE, $fixture->path);
        $found = false;

        foreach ($reported[$path] ?? [] as $message) {
            if (str_contains($message, $fixture->marker)) {
                $found = true;
            }
        }

        if (! $found) {
            $silent[] = sprintf('%s — %s did not produce "%s"', $fixture->rule, $path, $fixture->marker);
        }
    }

    expect($silent)->toBe([], sprintf(
        "The analyser read these violations and said nothing:\n  %s\n\n"
        . 'The rule exists, is registered, and carries its identifier — and does not '
        . 'fire. Either the fixture is not where the rule is scoped to look, or the '
        . 'rule does not do what its name says.',
        implode("\n  ", $silent),
    ));
});

it('the suite fails every rule it is supposed to', function (): void {
    $fixtures = array_values(array_filter(
        Fixtures::all(),
        static fn(Fixture $f): bool => $f->proof === Proof::Suite,
    ));

    $failures = suiteFailures();
    $silent = [];

    // The isolated ones each get a pass to themselves, with only their own
    // file on disk, because each changes what the rest of the run does.
    foreach (Fixtures::all() as $alone) {
        if ($alone->proof !== Proof::IsolatedSuite) {
            continue;
        }

        removeFixtures();
        writeFixture(Tree::at($alone->path), $alone->code);

        // The Arch suite alone, because a `->only()` in a module test narrows
        // whatever run loads it — including the test that reports it. G6 is a
        // text scan for exactly that reason: it reads the file rather than
        // running it, so a suite that does not load the file still reports it.
        if (! wasRefused(suiteFailures('Arch'), $alone)) {
            $silent[] = sprintf('%s — "%s" did not fail on its own', $alone->rule, $alone->marker);
        }

        removeFixture(Tree::at($alone->path));
        writeFixtures();
    }

    foreach ($fixtures as $fixture) {
        if (! wasRefused($failures, $fixture)) {
            $silent[] = sprintf(
                '%s — "%s" did not fail, or did not name %s',
                $fixture->rule,
                $fixture->marker,
                $fixture->evidence,
            );
        }
    }

    expect($silent)->toBe([], sprintf(
        "The suite read these violations and stayed green:\n  %s\n\n"
        . 'A rule has to fail AND name the fixture that broke it. Passing on another '
        . "fixture's violation is how a vacuous rule hides in a run where something "
        . 'else went wrong anyway.',
        implode("\n  ", $silent),
    ));
});

it('reports the rules nothing can be planted under', function (): void {
    $undrivable = array_values(array_filter(
        Fixtures::all(),
        static fn(Fixture $f): bool => $f->proof === Proof::NotDrivable,
    ));

    expect($undrivable)->toBeArray();

    fwrite(STDOUT, sprintf(
        "\n  rules proven by hand rather than by fixture: %d (%s)\n",
        count($undrivable),
        implode(', ', array_map(static fn(Fixture $f): string => $f->rule, $undrivable)),
    ));
});

/**
 * Every analyser finding, by the path it was reported against.
 *
 * One run over the whole fixture tree rather than one per rule: booting the
 * framework for larastan is the expensive part and it is the same boot each
 * time. The tree is passed on the command line, which replaces the `paths` in
 * the configuration and leaves the rules and their scoping intact.
 *
 * Null where the analyser did not run at all, which is a different fact from
 * an analyser that ran and reported nothing.
 *
 * @return array<string, list<string>>|null
 */
function analyserFindings(): ?array
{
    $raw = shell_exec(sprintf(
        '%s/vendor/bin/phpstan analyse %s --error-format=json --no-progress 2>/dev/null',
        Tree::root(),
        escapeshellarg(Tree::at(Fixtures::ANALYSER_TREE)),
    ));

    /** @var mixed $decoded */
    $decoded = json_decode((string) $raw, associative: true);
    $files = is_array($decoded) ? ($decoded['files'] ?? null) : null;

    if (! is_array($files)) {
        return null;
    }

    $found = [];

    foreach ($files as $path => $file) {
        $relative = str_replace(sprintf('%s/', Tree::root()), '', (string) $path);
        $found[$relative] = messagesIn($file);
    }

    return $found;
}

/**
 * @return list<string>
 */
function messagesIn(mixed $file): array
{
    $messages = is_array($file) ? ($file['messages'] ?? null) : null;

    if (! is_array($messages)) {
        return [];
    }

    $found = [];

    foreach ($messages as $message) {
        if (! is_array($message)) {
            continue;
        }

        $found[] = sprintf('%s %s', text($message['message'] ?? ''), text($message['identifier'] ?? ''));
    }

    return $found;
}

function text(mixed $value): string
{
    return is_string($value) ? $value : '';
}

/**
 * Every failing test, as its description paired with everything reported about
 * it — the class it lives in and the failure text, so a fixture can be found by
 * name in whichever of the three it appears.
 *
 * @return array<string, string>
 */
function suiteFailures(string $suites = 'Arch,Templates,Modules,Feature,Floors'): array
{
    $log = sprintf('%s/fixtures-junit.xml', sys_get_temp_dir());

    shell_exec(sprintf(
        '%s/vendor/bin/pest --testsuite=%s --log-junit=%s > /dev/null 2>&1',
        Tree::root(),
        escapeshellarg($suites),
        escapeshellarg($log),
    ));

    $report = is_file($log) ? file_get_contents($log) : false;

    if (! is_string($report) || $report === '') {
        return [];
    }

    $xml = simplexml_load_string($report);

    if ($xml === false) {
        return [];
    }

    $found = [];

    foreach ($xml->xpath('//testcase[failure or error]') ?? [] as $case) {
        $name = (string) $case['name'];

        $found[$name] = sprintf(
            '%s %s %s%s',
            $name,
            (string) $case['class'],
            (string) $case->failure,
            (string) $case->error,
        );
    }

    return $found;
}

/**
 * @param array<string, string> $failures
 */
function wasRefused(array $failures, Fixture $fixture): bool
{
    return array_any($failures, fn(string $report, string $name): bool => str_contains($name, $fixture->marker) && str_contains($report, $fixture->evidence));
}

function writeFixtures(): void
{
    foreach (Fixtures::companions() as $path => $code) {
        writeFixture(Tree::at($path), $code);
    }

    foreach (Fixtures::all() as $fixture) {
        if ($fixture->proof === Proof::Analyser) {
            writeFixture(Tree::at(sprintf('%s/%s', Fixtures::ANALYSER_TREE, $fixture->path)), $fixture->code);
        }

        if ($fixture->proof === Proof::Suite) {
            writeFixture(Tree::at($fixture->path), $fixture->code);
        }
    }
}

function writeFixture(string $path, string $code): void
{
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0o755, recursive: true);
    }

    file_put_contents($path, sprintf("%s\n", trim($code)));
}

/**
 * Take every fixture back out.
 *
 * Written as an afterEach rather than at the end of a test so that a fixture
 * survives nothing — not a failure, not an exception, not an interrupted run.
 * A fixture left behind turns every later run red for a reason that looks
 * nothing like the reason it is actually red.
 */
function removeFixtures(): void
{
    foreach (array_keys(Fixtures::companions()) as $path) {
        removeFixture(Tree::at($path));
    }

    foreach (Fixtures::all() as $fixture) {
        if ($fixture->proof === Proof::Suite || $fixture->proof === Proof::IsolatedSuite) {
            removeFixture(Tree::at($fixture->path));
        }
    }

    shell_exec(sprintf('rm -rf %s', escapeshellarg(Tree::at(Fixtures::ANALYSER_TREE))));

    pruneEmptyFixtureDirectories();
}

function removeFixture(string $path): void
{
    if (is_file($path)) {
        unlink($path);
    }
}

/**
 * Remove the directories the fixtures brought with them, and nothing else.
 *
 * Deepest first, so a directory whose only content was another fixture
 * directory goes too — and only when it is empty, because these paths run up
 * into `app-modules/health/src`, which is part of the repository.
 */
function pruneEmptyFixtureDirectories(): void
{
    $directories = [];

    $paths = [
        ...array_keys(Fixtures::companions()),
        ...array_map(static fn(Fixture $f): string => $f->path, Fixtures::all()),
    ];

    foreach ($paths as $path) {
        for ($directory = dirname($path); str_contains($directory, '/'); $directory = dirname($directory)) {
            $directories[$directory] = strlen($directory);
        }
    }

    arsort($directories);

    foreach (array_keys($directories) as $directory) {
        removeIfEmpty(Tree::at($directory));
    }
}

function removeIfEmpty(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $contents = scandir($directory);

    if ($contents !== false && count($contents) === 2) {
        rmdir($directory);
    }
}
