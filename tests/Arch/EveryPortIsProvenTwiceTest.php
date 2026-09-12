<?php

declare(strict_types=1);

use Tests\Support\Module;
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

/**
 * Every interface the kernel publishes, by name.
 *
 * The kernel is where ports live — that is what the module is — so this is the
 * set G8 binds and the set this rule proves.
 *
 * @return list<class-string>
 */
function ports(): array
{
    $found = [];

    foreach (Module::all() as $module) {
        if ($module->kind->value !== 'kernel') {
            continue;
        }

        foreach ($module->classNames() as $name) {
            if (interface_exists($name)) {
                $found[] = $name;
            }
        }
    }

    sort($found);

    return $found;
}

/**
 * Every class that implements a port, wherever it lives.
 *
 * Adapters come from the module manifests; fakes come from the root test
 * support, which is where they are kept so that none is reachable from
 * production code. Both are found rather than listed: a second adapter added
 * tomorrow is one this rule requires the contract to cover, without anyone
 * remembering to add a line.
 *
 * @return list<class-string>
 */
function implementationsOf(string $port): array
{
    $found = array_filter(
        [...moduleClasses(), ...fakeClasses()],
        static fn(string $name): bool => $name !== $port && is_a($name, $port, allow_string: true),
    );

    sort($found);

    return $found;
}

/**
 * Every class any module declares.
 *
 * @return list<class-string>
 */
function moduleClasses(): array
{
    $found = [];

    foreach (Module::all() as $module) {
        $found = [...$found, ...$module->classNames()];
    }

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

/** Where the contract for a port is kept, relative to the repository root. */
function contractPathFor(string $port): string
{
    $short = substr($port, (int) strrpos($port, '\\') + 1);

    return sprintf('tests/Contract/%sContractTest.php', $short);
}

it('G2 — every port has a contract test', function (): void {
    $missing = [];

    foreach (ports() as $port) {
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
        . 'end (G2).',
        implode("\n  ", $missing),
    ));
});

it('G2 — every contract is run against at least two implementations', function (): void {
    $thin = [];

    foreach (ports() as $port) {
        $implementations = implementationsOf($port);

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
    $unproven = [];

    foreach (ports() as $port) {
        $path = Tree::at(contractPathFor($port));

        if (! is_file($path)) {
            continue;  // reported by name above
        }

        $contract = (string) file_get_contents($path);

        foreach (implementationsOf($port) as $implementation) {
            $short = substr($implementation, (int) strrpos($implementation, '\\') + 1);

            if (! str_contains($contract, $short)) {
                $unproven[] = sprintf('%s is not named in %s', $implementation, contractPathFor($port));
            }
        }
    }

    expect($unproven)->toBe([], sprintf(
        "These implement a port and are not in its contract:\n  %s\n\n"
        . 'A contract that covers two of three implementations is true about the two '
        . 'and silent about the third, and silence reads as a pass. Add it to the '
        . "list the contract runs over.\nThis is the shape a second adapter arrives "
        . 'in: written, tested on its own, and never compared against the fake the '
        . 'rest of the suite trusts (G2).',
        implode("\n  ", $unproven),
    ));
});
