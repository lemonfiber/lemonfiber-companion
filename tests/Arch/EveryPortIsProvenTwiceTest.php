<?php

declare(strict_types=1);

use Tests\Support\Imports;
use Tests\Support\OurCode;
use Tests\Support\Tree;

// G2 — every port has one contract test, run against the real adapter and its
// fake.
//
// The most valuable rule on the page, and until now the only one documented as
// `planned`. A fake that has drifted from its adapter makes the suite green
// while the application is broken, and nothing else here catches that: every
// module test hands its subject a fake directly — that is what `A3` is for —
// so the adapter those tests are standing in for is exercised by none of them.
//
// What makes a fake trustworthy is not care. It is being held to the same
// assertions as the thing it replaces, which is why this asks for every
// implementation to be named in one file rather than for each to have a test
// of its own. Two files with the same intent drift; one file run twice cannot.
//
// A port with a single implementation is refused too, and that is the half
// worth stating: one implementation run against a contract is a unit test with
// a longer name. The second one is the fake, and the fake is the reason the
// rest of the suite is allowed to be fast.
//
// **The rule said *every port* and the reading said *every kernel port*.** The
// list was built from `Module::all()` filtered to `kind === 'kernel'`, and two
// of this repository's trees are not modules at all: `bridge/src` is a path
// package and `bootstrap/Composition` is the composition root. `Keeps` and
// `Runloop` live there, each with three implementations and one of them a fake
// the suite stands on, and G2 could not see either. Two answers had already
// come apart at `Keeps` before anything asked.
//
// So the reading is now the one `phpunit.xml` already gives: an interface
// declared in a tree the coverage floor measures. Derived rather than listed
// for the reason `R4` exists — a second copy of *what is ours* loses a tree
// quietly, and every rule resting on it keeps reporting a green tick about the
// trees it still reads.
//
// **Named in a contract means named in its code.** The file is parsed rather
// than searched, and the whole name is compared rather than the last part of
// it, because a search for a word answers yes to two things that are not
// cover. One is a comment: these files carry long ones, and the reason an
// implementation is *not* covered is exactly the sort of thing written down on
// the way past. The other is another class — `Clients` is a port whose name
// sits inside `PinnedClients`, so *is `Clients` in this file* is a question a
// search answers with a different class entirely.

/**
 * Ports with no contract of their own yet, and why not.
 *
 * A register rather than a narrowing, for the reason `EveryPortSomethingTakesTest`
 * keeps one: a port waiting is a state this repository is genuinely in, and the
 * thing that must not happen is that state being invisible. Each is named with
 * why, the count may fall and may not rise, and a row that has grown a contract
 * has to come out.
 *
 * The reasons differ in kind and that is the part worth reading. Two of these
 * are already covered by something else and would be a second reading of it;
 * one is a gap.
 *
 * @var array<string, string> the port's short name => why nothing holds it yet
 */
const NOTHING_HOLDS_IT_YET = [
    'Clients' => 'Covered elsewhere. It extends `Modules\Kernel\Api\Reaching` and narrows that '
        . 'port\'s `object` to the SDK\'s own `Client`, so both of its implementations — '
        . '`PinnedClients` and `ClientsThatReachNothing` — are already named in '
        . '`ReachingContractTest` and run against its assertions. A file here would be that '
        . 'one again with a return type narrowed, and the narrowing is what PHPStan already '
        . 'refuses to let anybody break.',
    'StandsIn' => 'Covered elsewhere, twice. `EveryStandInReplacesItsOwnPortTest` runs over the '
        . 'whole registry and asserts no two claim the same port, that the port claimed is an '
        . 'interface, and that `which()` answers an instance of it; '
        . '`EveryPayloadTheStandInBuildsFitsTheContractTest` holds what each one builds against '
        . 'the wire contract. Six implementations, and a third reading of the same registry is '
        . 'what a file here would be.',
    'Doors' => 'A gap, and the one worth closing next. `PinnedDoors` opens the door the '
        . 'operator\'s password goes through and `DoorsThatOpenOnNothing` stands in for it, '
        . 'and nothing runs both against one set of assertions. That is the failure this rule '
        . 'exists for, at the one port carrying a credential.',
];

/**
 * How many may be waiting.
 *
 * A ratchet rather than a budget. Lower it when the register gets shorter; it
 * is a number that may not rise, which is the whole of what stops a register
 * from being a to-do list wearing a gate's clothes.
 */
const HOW_MANY_MAY_GO_UNHELD = 3;

/**
 * Every interface this repository declares in a tree the coverage floor measures.
 *
 * *Every port*, which is what the rule says on the page. Read from
 * `phpunit.xml` by way of {@see OurCode::sourceClasses()} rather than from a
 * list of trees written here, so a package added tomorrow is one this rule
 * covers the moment it is measured.
 *
 * @return list<class-string>
 */
function ports(): array
{
    $found = array_values(array_filter(OurCode::sourceClasses(), interface_exists(...)));

    sort($found);

    return $found;
}

/**
 * Everything that could implement a port, wherever it lives.
 *
 * Adapters come from the measured trees; fakes come from the root test
 * support, which is where they are kept so that none is reachable from
 * production code. Both are found rather than listed: a second adapter added
 * tomorrow is one this rule requires the contract to cover, without anyone
 * remembering to add a line.
 *
 * Read once and handed to {@see implementationsOf()} rather than read inside
 * it. It is a walk of every measured file with the parser over each, and
 * asking it per port is that walk once per port for one answer.
 *
 * @return list<class-string>
 */
function everythingThatCouldImplementOne(): array
{
    return [...OurCode::sourceClasses(), ...fakeClasses()];
}

/**
 * Which of those implement one port.
 *
 * Interfaces are not among them, and the distinction is the rule rather than
 * a detail: an interface that extends a port is another port, with nothing to
 * run against a contract, and it is judged here in its own right. Counted as
 * an implementation it does two wrong things at once — it is asked to appear
 * in a contract that could not use it, and it makes up half of the two
 * implementations a port is required to have, so a port with one class and
 * one sub-interface reads as held.
 *
 * @param list<class-string> $candidates
 *
 * @return list<class-string>
 */
function implementationsOf(string $port, array $candidates): array
{
    $found = array_filter(
        $candidates,
        static fn(string $name): bool => $name !== $port
            && ! interface_exists($name)
            && is_a($name, $port, allow_string: true),
    );

    sort($found);

    return $found;
}

/**
 * Every fake in the root test support, by name.
 *
 * @return list<class-string>
 */
function fakeClasses(): array
{
    $found = [];

    foreach (Tree::filesUnder(Tree::at('tests/Support/Fakes'), '.php') as $file) {
        $name = sprintf('Tests\Support\Fakes\%s', basename($file, '.php'));

        if (class_exists($name)) {
            $found[] = $name;
        }
    }

    return $found;
}

/**
 * The last segment of a name, which is what a file and a register are keyed by.
 *
 * A name with no separator in it answers itself. `(int) strrpos(...)` would
 * fold *not found* into *found at nought* and take the first character off a
 * short name, which is the spelling the register is written in — so the
 * question the pruning rule asks about a row would be asked about a word one
 * letter shorter than the row.
 */
function shortNameOf(string $name): string
{
    $at = strrpos($name, '\\');

    return $at === false ? $name : substr($name, $at + 1);
}

/** Where the contract for a port is kept, relative to the repository root. */
function contractPathFor(string $port): string
{
    return sprintf('tests/Contract/%sContractTest.php', shortNameOf($port));
}

it('G2 — the ports this rule judges are found', function (): void {
    // The floor under every rule below. The reading walks the trees
    // `phpunit.xml` measures and keeps the interfaces among them, so a tree
    // dropped from that file or a class map that stopped resolving leaves each
    // of them judging an empty list — and a green run over no ports looks
    // exactly like a green run over every one of them.
    expect(ports())->not->toBe([], 'no interface was found in any measured tree, so this rule read nothing');
});

it('G2 — every port has a contract test', function (): void {
    $missing = [];

    foreach (ports() as $port) {
        if (array_key_exists(shortNameOf($port), NOTHING_HOLDS_IT_YET)) {
            continue;  // named in the register, with why
        }

        $path = contractPathFor($port);

        if (! is_file(Tree::at($path))) {
            $missing[] = sprintf('%s has no %s', $port, $path);
        }
    }

    expect($missing)->toBe([], sprintf(
        "These ports are not held to anything:\n  %s\n\n"
        . 'A port is a promise two classes make to each other, and nothing else in '
        . "this suite looks at both of them. Write the contract and run it twice.\n"
        . 'The file is named for the port so that the pairing is findable from either '
        . "end (G2).\nA port that genuinely cannot have one yet goes in "
        . 'NOTHING_HOLDS_IT_YET with the reason, which is a register that may get '
        . 'shorter and may not get longer.',
        implode("\n  ", $missing),
    ));
});

it('G2 — every contract is run against at least two implementations', function (): void {
    $candidates = everythingThatCouldImplementOne();
    $thin = [];

    foreach (ports() as $port) {
        $implementations = implementationsOf($port, $candidates);

        if (count($implementations) < 2) {
            $thin[] = sprintf(
                '%s has %d implementation(s): %s',
                $port,
                count($implementations),
                $implementations === [] ? 'none' : implode(', ', $implementations),
            );
        }
    }

    expect($thin)->toBe([], sprintf(
        "These ports have nothing to compare:\n  %s\n\n"
        . 'One implementation run against a contract is a unit test with a longer '
        . 'name. The second is the fake every other test in this suite stands on, and '
        . "it is trustworthy only because it is held to the same assertions.\n"
        . 'Fakes live in tests/Support/Fakes, where nothing in production can reach '
        . 'them (G2, G1, G4).',
        implode("\n  ", $thin),
    ));
});

it('G2 — no implementation is left out of its port\'s contract', function (): void {
    $candidates = everythingThatCouldImplementOne();
    $unproven = [];

    foreach (ports() as $port) {
        $path = Tree::at(contractPathFor($port));

        if (! is_file($path)) {
            continue;  // reported by name above, or named in the register
        }

        $named = Imports::of($path);

        foreach (implementationsOf($port, $candidates) as $implementation) {
            if (! in_array($implementation, $named, strict: true)) {
                $unproven[] = sprintf('%s is not named in %s', $implementation, contractPathFor($port));
            }
        }
    }

    expect($unproven)->toBe([], sprintf(
        "These implement a port and are not in its contract:\n  %s\n\n"
        . 'A contract that covers two of three implementations is true about the two '
        . 'and silent about the third, and silence reads as a pass. Add it to the '
        . "list the contract runs over.\nNamed in a comment is not named: this reads "
        . 'the file as code, so the class has to be one the contract imports or writes '
        . "out.\nThis is the shape a second adapter arrives in: written, tested on its "
        . 'own, and never compared against the fake the rest of the suite trusts (G2).',
        implode("\n  ", $unproven),
    ));
});

it('G2 — every port on the register says why it is waiting', function (): void {
    // A row with no reason is a row nobody can act on, and the reason is the
    // only part that survives the person who wrote it. Whether the wait is a
    // gap or a duplicate of cover that already exists is the difference between
    // work worth doing and work worth refusing, and it is exactly the knowledge
    // that evaporates.
    $bare = [];

    foreach (NOTHING_HOLDS_IT_YET as $port => $why) {
        if (trim($why) === '') {
            $bare[] = $port;
        }
    }

    sort($bare);

    expect($bare)->toBe([], sprintf(
        "These are waiting for a contract and say nothing about why:\n  %s\n\n"
        . 'Say whether it is a gap or cover that already exists somewhere else, and '
        . 'name what covers it (G2).',
        implode("\n  ", $bare),
    ));
});

it('G2 — a port that has grown a contract comes off the register', function (): void {
    // The teeth. A register whose entries are never checked describes the
    // repository as it was, and the entry that goes stale first is the one
    // somebody finally wrote the contract for — which is the moment the
    // register should get shorter rather than quietly wrong. A row naming
    // nothing at all is the same problem arriving the other way: it holds a
    // place on the ratchet and describes no port.
    $stale = [];
    $shortNames = array_map(shortNameOf(...), ports());

    foreach (array_keys(NOTHING_HOLDS_IT_YET) as $port) {
        if (! in_array($port, $shortNames, strict: true)) {
            $stale[] = sprintf('%s is on the register and is not a port this repository declares', $port);

            continue;
        }

        if (is_file(Tree::at(contractPathFor($port)))) {
            $stale[] = sprintf('%s is on the register and has a contract now', $port);
        }
    }

    sort($stale);

    expect($stale)->toBe([], sprintf(
        "These rows no longer describe a port that is waiting:\n  %s\n\n"
        . "Take them out of NOTHING_HOLDS_IT_YET and lower HOW_MANY_MAY_GO_UNHELD by as "
        . "many.\nA register nobody prunes is a register that stops being read, and a row "
        . 'holding a place on the ratchet for a port that is gone is slack nobody asked '
        . 'for (G2).',
        implode("\n  ", $stale),
    ));
});

it('G2 — the register does not grow', function (): void {
    expect(count(NOTHING_HOLDS_IT_YET))->toBeLessThanOrEqual(HOW_MANY_MAY_GO_UNHELD, sprintf(
        "%d ports are waiting for a contract, and the ceiling is %d.\n"
        . 'The register may get shorter and may not get longer. A port written before its '
        . 'contract is ordinary; a habit of writing them is how the most valuable rule on '
        . 'the page comes to cover whichever ports somebody felt like covering (G2).',
        count(NOTHING_HOLDS_IT_YET),
        HOW_MANY_MAY_GO_UNHELD,
    ));
});
