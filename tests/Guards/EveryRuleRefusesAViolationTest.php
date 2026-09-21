<?php

declare(strict_types=1);

use Tests\Support\Fixture;
use Tests\Support\Fixtures;
use Tests\Support\Proof;
use Tests\Support\Rules;
use Tests\Support\Tree;

/**
 * Where this run records what it has written.
 *
 * Beside the analyser tree and gitignored with it, because it is the same kind
 * of thing: a working file of the guards run, present only while one is going.
 */
const WRITTEN = '.rule-fixtures.written';

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
    // Swept *before* planting, not only after. `afterEach` is the right place
    // for the normal end of a run and it is not reached by the abnormal one: a
    // killed process runs no hook, and this suite takes long enough to be the
    // thing somebody kills. What it leaves behind then survives the whole of the
    // next run, which fails on a rule nobody touched.
    removeFixtures();
    writeFixtures();
});

afterEach(function (): void {
    removeFixtures();
});

it('takes back out what a killed run left behind', function (): void {
    // The case `afterEach` cannot cover, asserted directly rather than trusted.
    //
    // A run that is killed leaves files and a manifest naming them. This stands
    // in for that run: a path the current fixture list does not name, recorded
    // the way a write records itself. Removing by name would walk straight past
    // it — which is what happened, and what surfaced was an unrelated rule
    // failing an hour before anybody suspected the tree rather than the rule.
    $stray = Tree::at('app-modules/health/tests/Fixtures/LeftBehindTest.php');

    writeFixture($stray, '<?php // yesterday\'s run, killed');

    expect(is_file($stray))->toBeTrue();

    removeFixtures();

    expect(is_file($stray))->toBeFalse();
});

it('puts back what a killed run wrote over', function (): void {
    // The other half, and the one with teeth. A fixture that edits a file the
    // repository owns cannot be swept by deleting it, and a sweep that deleted
    // it would take a source file with it. What the manifest records is
    // therefore not the path but what the path held, so the file goes back
    // exactly as it was — after a failure, an exception, or a kill.
    $real = Tree::at('app-modules/health/tests/Fixtures/AlreadyHere.php');
    $was = "<?php // the file this repository owns\n";

    // Put there the way the repository has it — not through `writeFixture`,
    // which would record it as something this run wrote and therefore as
    // something the sweep should take away.
    if (! is_dir(dirname($real))) {
        mkdir(dirname($real), 0o755, recursive: true);
    }

    file_put_contents($real, $was);

    writeFixture($real, '<?php // what a fixture put over it');
    writeFixture($real, '<?php // and what a second fixture put over that');

    removeFixtures();

    expect(is_file($real))->toBeTrue()
        ->and(file_get_contents($real))->toBe($was);

    unlink($real);
});

it('has a fixture for every rule that claims to be enforced', function (): void {
    $missing = rulesWithNoFixture(
        Rules::documented(),
        array_map(static fn(Fixture $f): string => $f->rule, Fixtures::all()),
    );

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

it('names an enforced rule nothing is planted under', function (): void {
    // R2's violation, handed to R2. There is no file that could carry it: the
    // subject is this run, so planting it would mean documenting a rule and
    // leaving it uncovered here — and the run that read that would be this one.
    $claims = [
        'A1' => 'test: the Arch suite',
        'A2' => 'planned',
        'A3' => 'test: the Arch suite',
    ];

    expect(rulesWithNoFixture($claims, ['A1', 'A3']))->toBe([]);
    expect(rulesWithNoFixture($claims, ['A1']))
        ->toBe(['A3 (claims "test: the Arch suite")']);

    // A rule still being planned is not yet a claim to enforce anything, and
    // naming it here would make a gap in the roadmap read as a gap in the
    // harness. TheRulesAreRealTest is what keeps *that* honest.
    expect(rulesWithNoFixture(['A2' => 'planned'], []))->toBe([]);

    // And the empty answer is not the only one it can give. Everything above
    // holds just as well for a function that returns `[]` whatever it is asked,
    // which is the shape of every vacuous check this harness exists to refuse.
    expect(rulesWithNoFixture($claims, []))->toHaveCount(2);
});

it('the analyser reports every rule it is supposed to', function (): void {
    $fixtures = array_values(array_filter(
        Fixtures::all(),
        static fn(Fixture $f): bool => $f->proof->readByAnalyser(),
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
        $path = whereTheFixtureWasPlanted($fixture);
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
        static fn(Fixture $f): bool => $f->proof->readBySuite(),
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
    $named = static function (Proof $proof): string {
        $rules = array_map(
            static fn(Fixture $f): string => $f->rule,
            array_values(array_filter(
                Fixtures::all(),
                static fn(Fixture $f): bool => $f->proof === $proof,
            )),
        );

        return sprintf('%d (%s)', count($rules), implode(', ', $rules));
    };

    // An answer without a reason is a gap wearing an answer's clothes, so what
    // is asserted here is that every rule on either line says why it is there.
    // Both kinds carry the reason in `marker`, having no file to point at.
    $silent = array_map(
        static fn(Fixture $f): string => $f->rule,
        array_values(array_filter(
            Fixtures::all(),
            static fn(Fixture $f): bool => in_array(
                $f->proof,
                [Proof::Direct, Proof::NotDrivable],
                strict: true,
            ) && trim($f->marker) === '',
        )),
    );

    expect($silent)->toBe([], sprintf(
        "These rules are answered with neither a fixture nor a reason:\n  %s\n\n"
        . 'Both kinds are read by nobody but a person, and a person given a rule '
        . 'and no reason has been told nothing at all.',
        implode("\n  ", $silent),
    ));

    // Two answers to one question, and the difference is the point: the first
    // group is checked on every run and the second is checked when somebody
    // remembers. A rule moving from the second line to the first is the work.
    fwrite(STDOUT, sprintf(
        "\n  rules driven by calling their own judgement: %s"
        . "\n  rules proven by hand rather than by fixture: %s\n",
        $named(Proof::Direct),
        $named(Proof::NotDrivable),
    ));
});

/**
 * The enforced rules nothing has been planted under.
 *
 * Split out of the test above it because this is R2's own judgement, and a
 * judgement that has only ever run against a document where the answer is
 * "none" is one whose failure nobody has seen. Given the claims and the
 * coverage directly, it can be watched naming a rule — which is what every
 * other rule here gets from a file dropped in beside the real ones.
 *
 * @param  array<string,string>  $claims  rule identifier => how it is enforced
 * @param  list<string>  $covered  rule identifiers something is planted under
 * @return list<string>
 */
function rulesWithNoFixture(array $claims, array $covered): array
{
    $missing = [];

    foreach ($claims as $id => $claim) {
        // A rule nobody has built yet has nothing to violate. The ratchet in
        // TheRulesAreRealTest is what keeps that honest.
        if ($claim === 'planned') {
            continue;
        }

        if (! in_array($id, $covered, strict: true)) {
            $missing[] = sprintf('%s (claims "%s")', $id, $claim);
        }
    }

    return $missing;
}

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
        implode(' ', array_map(
            static fn(string $where): string => escapeshellarg(Tree::at($where)),
            whereTheAnalyserIsPointed(),
        )),
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
 * Where a fixture that is a file of its own was planted, relative to the root.
 *
 * One answer rather than one per caller. The writer, the reader of the report
 * and the sweep all need it, and three copies of the same path join is three
 * places for a fixture to be written somewhere the analyser is never pointed —
 * which reports as a rule that did not fire, and is the one failure this whole
 * harness exists to make impossible.
 *
 * Only the fixture tree gets a prefix. Every other kind already names a real
 * path, because that is what makes it reachable by a rule that narrows to one.
 */
function whereTheFixtureWasPlanted(Fixture $fixture): string
{
    return $fixture->proof === Proof::Analyser
        ? sprintf('%s/%s', Fixtures::ANALYSER_TREE, $fixture->path)
        : $fixture->path;
}

/**
 * Every path the analyser is run over.
 *
 * The fixture tree, and then each in-place fixture by name. Taken from the
 * fixtures themselves rather than from a list of directories kept beside them,
 * so the set of places the analyser looks cannot drift from the set of places
 * something was planted. A list kept by hand goes stale the day a fixture moves
 * — and it goes stale as a rule reporting nothing, which is the one failure
 * this harness cannot tell from success.
 *
 * @return list<string>
 */
function whereTheAnalyserIsPointed(): array
{
    $pointed = [Fixtures::ANALYSER_TREE];

    foreach (Fixtures::all() as $fixture) {
        if ($fixture->proof === Proof::AnalyserInPlace) {
            $pointed[] = $fixture->path;
        }
    }

    return $pointed;
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
        if ($fixture->proof->readByAnalyser()) {
            writeFixture(Tree::at(whereTheFixtureWasPlanted($fixture)), $fixture->code);
        }

        if ($fixture->proof === Proof::Suite) {
            writeFixture(Tree::at($fixture->path), $fixture->code);
        }

        if ($fixture->proof === Proof::Edit) {
            editFixture($fixture);
        }
    }
}

/**
 * Put a fixture's change into a file this repository owns.
 *
 * The match is asserted before the edit, not after. A `$replacing` that no
 * longer appears — the real file was reformatted, the method renamed — would
 * leave the tree unedited and the rule reported as refusing a violation that was
 * never planted, which is exactly the vacuous green everything here exists to
 * make impossible. It raises instead, from `beforeEach`, naming the fixture.
 *
 * Once, because two occurrences mean the snippet is not specific enough to say
 * which one is being broken, and a fixture that edits both is not the smallest
 * violation of anything.
 */
function editFixture(Fixture $fixture): void
{
    $path = Tree::at($fixture->path);
    $was = is_file($path) ? (string) file_get_contents($path) : '';
    $found = substr_count($was, $fixture->replacing);

    if ($found !== 1) {
        throw new RuntimeException(sprintf(
            'The fixture for %s replaces text that appears %d times in %s, so it would '
            . "plant %s. What it looks for:\n\n%s\n\nEither the file has moved on and "
            . 'the fixture should follow it, or the snippet needs to be specific enough '
            . 'to name one place.',
            $fixture->rule,
            $found,
            $fixture->path,
            $found === 0 ? 'nothing at all and leave the rule passing on an unedited tree' : 'in more than one place',
            $fixture->replacing,
        ));
    }

    writeFixture($path, str_replace($fixture->replacing, $fixture->code, $was));
}

function writeFixture(string $path, string $code): void
{
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0o755, recursive: true);
    }

    // What was there first, recorded before it is gone. A fixture path is
    // normally a name nothing else uses and the sweep can simply delete it —
    // but a fixture that *edits* an existing file has to put that file back,
    // and a fixture path that collided with a real one would otherwise have the
    // sweep delete a source file and say nothing. One record covers both.
    $before = is_file($path) ? (string) file_get_contents($path) : '';

    file_put_contents($path, sprintf("%s\n", trim($code)));
    file_put_contents(
        Tree::at(WRITTEN),
        sprintf("%s\t%s\n", $path, sodium_bin2hex($before)),
        FILE_APPEND,
    );
}

/**
 * Every path this run has written, with whatever was there before it.
 *
 * An empty string means there was nothing — the ordinary case, where the sweep
 * deletes. Anything else is a file to put back exactly as it was.
 *
 * @return array<string, string>
 */
function fixturesWritten(): array
{
    $manifest = Tree::at(WRITTEN);

    if (! is_file($manifest)) {
        return [];
    }

    $said = file_get_contents($manifest);

    if (! is_string($said)) {
        return [];
    }

    $written = [];

    foreach (explode("\n", trim($said)) as $line) {
        if ($line === '') {
            continue;
        }

        // A line from before this record carried what it replaced is a path and
        // nothing else, and a path with nothing behind it is the ordinary case.
        [$path, $before] = array_pad(explode("\t", $line, 2), 2, '');

        // The *first* record for a path, not the last. Two fixtures can edit
        // one file, and the second one records what the first one left — so
        // replaying in order would restore the file to a state this run made.
        // What is wanted is what was there before this run touched it at all,
        // which is the earliest line naming it.
        if (array_key_exists($path, $written)) {
            continue;
        }

        $written[$path] = $before === '' ? '' : sodium_hex2bin($before);
    }

    return $written;
}

/**
 * Take every fixture back out.
 *
 * Written as an afterEach rather than at the end of a test so that a fixture
 * survives nothing — not a failure, not an exception, not an interrupted run.
 * A fixture left behind turns every later run red for a reason that looks
 * nothing like the reason it is actually red.
 *
 * **The manifest is what makes that true of the interrupted run.** Removing by
 * name can only remove what the *current* fixture list names, and neither of the
 * two cases that matter is in it: a process killed mid-run leaves files nobody
 * asked about, and a fixture list edited between runs leaves yesterday's paths
 * unreachable by today's cleaner. Both happened on 2026-09-12, and what surfaced
 * was `G6` failing — a rule that had not been touched, whose fixture was fine,
 * and which passed the moment it was reproduced by hand. An hour went into
 * reading it as a real regression.
 *
 * So every write records its path, and the sweep reads that record first. The
 * by-name pass stays: it is the one that still works when the manifest itself is
 * what went missing.
 */
function removeFixtures(): void
{
    foreach (fixturesWritten() as $path => $before) {
        if ($before === '') {
            removeFixture($path);

            continue;
        }

        file_put_contents($path, $before);
    }

    removeFixture(Tree::at(WRITTEN));

    foreach (array_keys(Fixtures::companions()) as $path) {
        removeFixture(Tree::at($path));
    }

    foreach (Fixtures::all() as $fixture) {
        if ($fixture->proof->isAFileOfItsOwn()) {
            removeFixture(Tree::at(whereTheFixtureWasPlanted($fixture)));
        }

        // Never by name: the path is a file this repository owns, and deleting
        // it is the one outcome worse than leaving it edited. Undone by putting
        // the text back, which needs no manifest and is what covers the run
        // where the manifest is itself what went missing.
        if ($fixture->proof === Proof::Edit) {
            unEditFixture($fixture);
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
 * Take a fixture's change back out of a file this repository owns.
 *
 * Reads what is there rather than trusting that the manifest pass has run:
 * where the planted text is present it goes back, and where it is not there is
 * nothing to do — which is the ordinary case, because the manifest restored the
 * file a moment ago.
 */
function unEditFixture(Fixture $fixture): void
{
    $path = Tree::at($fixture->path);

    if (! is_file($path)) {
        return;
    }

    $now = (string) file_get_contents($path);

    if (! str_contains($now, $fixture->code)) {
        return;
    }

    file_put_contents($path, str_replace($fixture->code, $fixture->replacing, $now));
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
