<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;
use Tests\Support\Fixture;
use Tests\Support\Fixtures;
use Tests\Support\Proof;
use Tests\Support\Rules;
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
// Every violation is planted in a throwaway copy of the checkout, and the
// analyser and the suite run inside that copy. The checkout itself is only
// read, so a run can be killed at any point and leave it exactly as it was,
// and anything else can read or run in the checkout while this runs. A copy a
// killed run left behind is swept at the start of the next one.
//
// A rule with no fixture fails here. That is the part worth having: it makes "I
// did not check this one" impossible to leave implicit, which is the condition
// the three above needed in order to survive.

beforeAll(function (): void {
    sweepAbandonedCopies();
});

afterAll(function (): void {
    discardTheRun();
});

it('sweeps away a copy a run left behind, and only that one', function (): void {
    // A killed run leaves its copy on disk and its lock released, because the
    // kernel drops a lock with the process that held it. A run still going
    // holds its lock, and its copy is the one thing the sweep must not touch.
    $abandoned = sprintf('%s/abandoned-%s', whereCopiesAreMade(), aFreshName());
    $live = sprintf('%s/live-%s', whereCopiesAreMade(), aFreshName());

    foreach ([$abandoned, $live] as $copy) {
        mkdir(sprintf('%s/app-modules', $copy), 0o755, recursive: true);
        file_put_contents(sprintf('%s/app-modules/Planted.php', $copy), '<?php // planted');
        file_put_contents(theReportOf($copy), '<testsuites/>');
        file_put_contents(sprintf('%s.lock', $copy), '');
    }

    lockTheCopy($live);

    sweepAbandonedCopies();

    expect(is_dir($abandoned))->toBeFalse()
        ->and(is_file(theReportOf($abandoned)))->toBeFalse()
        ->and(is_file(sprintf('%s.lock', $abandoned)))->toBeFalse()
        ->and(is_file(sprintf('%s/app-modules/Planted.php', $live)))->toBeTrue()
        ->and(is_file(sprintf('%s.lock', $live)))->toBeTrue();

    releaseTheLock($live);
    sweepAbandonedCopies();

    expect(is_dir($live))->toBeFalse()
        ->and(is_file(sprintf('%s.lock', $live)))->toBeFalse();
});

it('plants nothing in the checkout it was started from', function (): void {
    // Asserted over the planted copy rather than trusted from where the paths
    // point: every fixture is in its copy, and none of them is in the checkout.
    $copy = theRun()['copy'];
    $looked = 0;
    $missing = [];
    $leaked = [];

    foreach (Fixtures::all() as $fixture) {
        $where = whereTheFixtureWasPlanted($fixture);

        if ($fixture->proof === Proof::Edit) {
            $looked++;

            if (! str_contains((string) file_get_contents(sprintf('%s/%s', $copy, $where)), $fixture->code)) {
                $missing[] = $where;
            }

            if (str_contains((string) file_get_contents(Tree::at($where)), $fixture->code)) {
                $leaked[] = $where;
            }
        }

        if (! $fixture->proof->isAFileOfItsOwn()) {
            continue;
        }

        if ($fixture->proof !== Proof::IsolatedSuite) {
            $looked++;

            if (! is_file(sprintf('%s/%s', $copy, $where))) {
                $missing[] = $where;
            }
        }

        // By what the file holds rather than by whether it is there, because
        // one fixture takes the path of a real report: `coverage/clover.xml`
        // is where `test:report` writes, and a checkout may well have one.
        if (is_file(Tree::at($where)) && trim((string) file_get_contents(Tree::at($where))) === trim($fixture->code)) {
            $leaked[] = $where;
        }
    }

    expect($looked)->toBeGreaterThan(0)
        ->and($missing)->toBe([])
        ->and($leaked)->toBe([]);
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

    $run = theRun();
    $reported = analyserFindings($run['analyser'], $run['copy']);

    // Told apart from "every rule stayed quiet" on purpose. A malformed module
    // manifest stops Larastan booting, the analyser writes a stack trace to
    // stderr and nothing to stdout, and every fixture below then reads as a
    // rule that did not fire — forty findings, all of them wrong, none of them
    // the real one.
    expect($reported)->not->toBeNull(sprintf(
        "The analyser produced no readable output at all, so nothing below was measured. "
        . "A bootstrap failure looks exactly like every rule going silent at once. What it "
        . "wrote to stderr:\n\n%s",
        whatItSaidOnStderr($run['analyser']),
    ));

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

    $run = theRun();
    $failures = suiteFailures($run['suite'], $run['copy']);
    $silent = [];

    // The isolated ones each get a pass to themselves, in a copy holding only
    // their own file, because each changes what the rest of the run does. The
    // Arch suite alone, because a `->only()` in a module test narrows whatever
    // run loads it — including the test that reports it. G6 is a text scan for
    // exactly that reason: it reads the file rather than running it, so a
    // suite that does not load the file still reports it.
    foreach ($run['isolated'] as [$alone, $pass, $copy]) {
        if (! wasRefused(suiteFailures($pass, $copy), $alone)) {
            $silent[] = sprintf('%s — "%s" did not fail on its own', $alone->rule, $alone->marker);
        }
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
function analyserFindings(Process $analyser, string $copy): ?array
{
    $analyser->wait();

    /** @var mixed $decoded */
    $decoded = json_decode($analyser->getOutput(), associative: true);
    $files = is_array($decoded) ? ($decoded['files'] ?? null) : null;

    if (! is_array($files)) {
        return null;
    }

    $found = [];

    foreach ($files as $path => $file) {
        $relative = str_replace(sprintf('%s/', $copy), '', (string) $path);
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
function suiteFailures(Process $pass, string $copy): array
{
    $pass->wait();

    $log = theReportOf($copy);
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

/**
 * The planted copy, and every process reading it.
 *
 * Made on first use and shared by the tests that read it. Every pass starts at
 * once — the analyser and the suite over the copy holding every fixture, and an
 * Arch pass over a copy of its own for each fixture that has to be read alone —
 * because each writes only to its own copy and its own report, so the run lasts
 * as long as the slowest pass rather than as long as all of them.
 *
 * @return array{copy: string, analyser: Process, suite: Process, isolated: list<array{Fixture, Process, string}>}
 */
function theRun(): array
{
    /** @var array{copy: string, analyser: Process, suite: Process, isolated: list<array{Fixture, Process, string}>}|null $run */
    static $run = null;

    if ($run !== null) {
        return $run;
    }

    $copy = aCopy();
    plantEverything($copy);

    $isolated = [];

    foreach (Fixtures::all() as $alone) {
        if ($alone->proof !== Proof::IsolatedSuite) {
            continue;
        }

        $own = aCopy();
        plantFile(sprintf('%s/%s', $own, $alone->path), $alone->code);
        $isolated[] = [$alone, started(theSuiteIn($own, 'Arch')), $own];
    }

    $run = [
        'copy' => $copy,
        'analyser' => started(theAnalyserIn($copy)),
        'suite' => started(theSuiteIn($copy, 'Arch,Templates,Modules,Feature,Floors')),
        'isolated' => $isolated,
    ];

    return $run;
}

/**
 * The analyser, over the fixture tree and each in-place fixture, inside a copy.
 *
 * Run from the copy, so the configuration it reads, the vendor directory it
 * boots and the cache it writes are all the copy's own.
 */
function theAnalyserIn(string $copy): Process
{
    return new Process(
        [
            PHP_BINARY,
            'vendor/bin/phpstan',
            'analyse',
            ...array_map(
                static fn(string $where): string => sprintf('%s/%s', $copy, $where),
                whereTheAnalyserIsPointed(),
            ),
            '--error-format=json',
            '--no-progress',
        ],
        $copy,
        timeout: null,
    );
}

/** The named suites, inside a copy, reporting to the file beside it. */
function theSuiteIn(string $copy, string $suites): Process
{
    $pass = new Process(
        [
            PHP_BINARY,
            'vendor/bin/pest',
            sprintf('--testsuite=%s', $suites),
            sprintf('--log-junit=%s', theReportOf($copy)),
        ],
        $copy,
        timeout: null,
    );

    $pass->disableOutput();

    return $pass;
}

function whatItSaidOnStderr(Process $process): string
{
    return $process->getErrorOutput();
}

function started(Process $process): Process
{
    $process->start();
    runningProcesses()->append($process);

    return $process;
}

/**
 * Where a suite pass over a copy writes its JUnit report.
 *
 * Beside the copy rather than in it, so no test in the pass can read it, and
 * named after it, so the sweep that removes one removes the other.
 */
function theReportOf(string $copy): string
{
    return sprintf('%s.junit.xml', $copy);
}

/**
 * Every process this run started, so the end of the run can stop any still
 * going before their copies are removed from under them.
 *
 * @return ArrayObject<int, Process>
 */
function runningProcesses(): ArrayObject
{
    /** @var ArrayObject<int, Process> $running */
    static $running = new ArrayObject();

    return $running;
}

/**
 * The lock held on every copy this run made, by the copy's path.
 *
 * Held for as long as the copy is in use. The kernel releases a lock when the
 * process holding it ends, however it ends, which is what lets the sweep tell a
 * copy a killed run left behind from one a live run is reading.
 *
 * @return ArrayObject<string, resource>
 */
function heldLocks(): ArrayObject
{
    /** @var ArrayObject<string, resource> $held */
    static $held = new ArrayObject();

    return $held;
}

/**
 * Where every copy of the tree a guards run plants into is made, one directory
 * per copy with a lock file beside it.
 *
 * Under the system's temporary directory rather than inside the checkout, so a
 * walk of the checkout never meets one. Resolved, because on macOS the
 * temporary directory is reached through a symlink and the analyser reports
 * the resolved path, which is what a finding is matched against.
 */
function whereCopiesAreMade(): string
{
    $temporary = realpath(sys_get_temp_dir());

    return sprintf('%s/lemonfiber-guards', $temporary === false ? sys_get_temp_dir() : $temporary);
}

/**
 * A fresh copy of the checkout, locked for this run.
 *
 * What git would call the working tree: every tracked file as it is on disk
 * now, uncommitted changes included, and every untracked file git does not
 * ignore — so a run checks the tree somebody is working on, and nothing a
 * build or an editor left lying about. Then `vendor`, which git ignores and
 * everything here needs, copied whole rather than linked: the autoloader and
 * Pest each find the project from where their own files sit, so a linked
 * `vendor` would lead both back to the checkout. On macOS the copy
 * is a clone, which shares blocks with the original until one side writes.
 */
function aCopy(): string
{
    $copies = whereCopiesAreMade();

    if (! is_dir($copies)) {
        mkdir($copies, 0o755, recursive: true);
    }

    $copy = sprintf('%s/%s', $copies, aFreshName());
    lockTheCopy($copy);

    copyTheWorkingTree($copy);

    shell_exec(sprintf(
        'cp -R%s %s %s',
        PHP_OS_FAMILY === 'Darwin' ? 'c' : '',
        escapeshellarg(Tree::at('vendor')),
        escapeshellarg(sprintf('%s/vendor', $copy)),
    ));

    if (! is_file(sprintf('%s/vendor/autoload.php', $copy))) {
        throw new RuntimeException(sprintf('vendor was not copied into %s.', $copy));
    }

    return $copy;
}

/** Every file git lists in the working tree, as it is on disk now. */
function copyTheWorkingTree(string $copy): void
{
    $listed = shell_exec(sprintf(
        'git -C %s ls-files -z --cached --others --exclude-standard',
        escapeshellarg(Tree::root()),
    ));

    if (! is_string($listed) || $listed === '') {
        throw new RuntimeException(sprintf(
            'git listed no files in %s, so there is nothing to copy and nothing to plant into.',
            Tree::root(),
        ));
    }

    foreach (explode("\0", trim($listed, "\0")) as $path) {
        // Tracked and deleted in the working tree, which is the state a run
        // should see.
        if (! is_file(Tree::at($path))) {
            continue;
        }

        $to = sprintf('%s/%s', $copy, $path);

        if (! is_dir(dirname($to))) {
            mkdir(dirname($to), 0o755, recursive: true);
        }

        copy(Tree::at($path), $to);
    }
}

/** Take the lock on a copy, and hold it until the copy is discarded. */
function lockTheCopy(string $copy): void
{
    $lock = fopen(sprintf('%s.lock', $copy), 'c');

    if ($lock === false || ! flock($lock, LOCK_EX)) {
        throw new RuntimeException(sprintf('Could not lock %s for this run.', $copy));
    }

    heldLocks()[$copy] = $lock;
}

function releaseTheLock(string $copy): void
{
    $lock = heldLocks()[$copy] ?? null;

    if ($lock === null) {
        return;
    }

    flock($lock, LOCK_UN);
    fclose($lock);
    unset(heldLocks()[$copy]);
}

/** A name no other copy has, so two runs never plant into one directory. */
function aFreshName(): string
{
    return sodium_bin2hex(random_bytes(8));
}

/**
 * Remove every copy no live run holds.
 *
 * A lock that can be taken is a lock nobody holds, so its copy belongs to a
 * run that has ended — normally one killed before it could discard it.
 */
function sweepAbandonedCopies(): void
{
    $locks = glob(sprintf('%s/*.lock', whereCopiesAreMade()));

    foreach ($locks === false ? [] : $locks as $path) {
        $lock = fopen($path, 'c');

        if ($lock === false) {
            continue;
        }

        if (flock($lock, LOCK_EX | LOCK_NB)) {
            removeTheCopy(substr($path, 0, -strlen('.lock')));
            unlink($path);
        }

        fclose($lock);
    }
}

/** Stop whatever is still running, and remove every copy this run made. */
function discardTheRun(): void
{
    foreach (runningProcesses() as $process) {
        $process->stop(0);
    }

    foreach (array_keys(heldLocks()->getArrayCopy()) as $copy) {
        discardCopy($copy);
    }
}

function discardCopy(string $copy): void
{
    removeTheCopy($copy);

    if (is_file(sprintf('%s.lock', $copy))) {
        unlink(sprintf('%s.lock', $copy));
    }

    releaseTheLock($copy);
}

function removeTheCopy(string $copy): void
{
    shell_exec(sprintf('rm -rf %s %s', escapeshellarg($copy), escapeshellarg(theReportOf($copy))));
}

/**
 * Every fixture, and every file a fixture needs in order to compile, planted
 * in a copy.
 *
 * All but the ones that are read alone, which get a copy each.
 */
function plantEverything(string $copy): void
{
    foreach (Fixtures::companions() as $path => $code) {
        plantFile(sprintf('%s/%s', $copy, $path), $code);
    }

    foreach (Fixtures::all() as $fixture) {
        if ($fixture->proof->readByAnalyser()) {
            plantFile(sprintf('%s/%s', $copy, whereTheFixtureWasPlanted($fixture)), $fixture->code);
        }

        if ($fixture->proof === Proof::Suite) {
            plantFile(sprintf('%s/%s', $copy, $fixture->path), $fixture->code);
        }

        if ($fixture->proof === Proof::Edit) {
            plantEdit($copy, $fixture);
        }
    }
}

/**
 * Put a fixture's change into the copy of a file this repository owns.
 *
 * The match is asserted before the edit, not after. A `$replacing` that no
 * longer appears — the real file was reformatted, the method renamed — would
 * leave the file unedited and the rule reported as refusing a violation that
 * was never planted, which is exactly the vacuous green everything here exists
 * to make impossible. It raises instead, naming the fixture.
 *
 * Once, because two occurrences mean the snippet is not specific enough to say
 * which one is being broken, and a fixture that edits both is not the smallest
 * violation of anything.
 */
function plantEdit(string $copy, Fixture $fixture): void
{
    $path = sprintf('%s/%s', $copy, $fixture->path);
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

    plantFile($path, str_replace($fixture->replacing, $fixture->code, $was));
}

function plantFile(string $path, string $code): void
{
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0o755, recursive: true);
    }

    file_put_contents($path, sprintf("%s\n", trim($code)));
}
