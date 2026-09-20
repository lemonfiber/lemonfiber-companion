<?php

declare(strict_types=1);
use Tests\Support\Module;

// G7 — every module states the bar it is held to, in its own manifest.
//
// The floors were one number for the whole run, which meant one module hid
// another: a well-covered capability carried a bare adapter and the report said
// everything was fine. That is true about what it averaged and silent about
// what it averaged over.
//
// Per-module floors only help if every module has one, so an undeclared floor
// is an error rather than a default. A default would put the number back where
// nobody chose it, and a module added tomorrow would inherit a bar somebody
// picked for a different module — which is the silent exemption this change
// exists to remove. The kind's convention is named in the message instead, so
// declaring the floor is a ten-second job rather than a guess.

it('G7 — every module declares a coverage and a mutation floor', function (): void {
    $undeclared = [];

    foreach (Module::all() as $module) {
        if ($module->coverageFloor === null) {
            $undeclared[] = sprintf(
                '%s declares no coverage floor (a %s module is usually %d)',
                $module->name,
                $module->kind->value,
                $module->kind->conventionalCoverageFloor(),
            );
        }

        if ($module->mutationFloor === null) {
            $undeclared[] = sprintf(
                '%s declares no mutation floor (a %s module is usually %d)',
                $module->name,
                $module->kind->value,
                $module->kind->conventionalMutationFloor(),
            );
        }
    }

    expect($undeclared)->toBe([], sprintf(
        "These modules are held to no bar of their own:\n  %s\n\n"
        . "Add it to the module's own manifest, beside the kind that already generates "
        . "its boundary rules:\n\n"
        . "    \"extra\": { \"lemonfiber\": { \"kind\": \"…\", \"floors\": "
        . "{ \"coverage\": 100, \"mutation\": 100 } } }\n\n"
        . 'There is deliberately no default. A default is a number nobody chose for this '
        . 'module, and a module that inherits one is exempt from the decision rather than '
        . 'held to it — which is the failure this rule exists to remove (G7).',
        implode("\n  ", $undeclared),
    ));
});

/**
 * What is wrong with one floor and the argument beside it, or nothing.
 *
 * Both directions, because a register only means something when its rows come
 * out again: an argument left behind by a floor that has risen describes a
 * position nobody holds any more, and it reads as current.
 *
 * Named for this file: the root suites share one namespace (`G10`).
 *
 * @return list<string>
 */
function whatIsWrongWithAFloor(string $module, string $which, ?int $floor, ?string $why): array
{
    if ($floor === 0 && ($why === null || trim($why) === '')) {
        return [sprintf('%s declares a %s floor of 0 and nothing about what holds it instead', $module, $which)];
    }

    if ($floor !== null && $floor > 0 && $why !== null) {
        return [sprintf('%s declares a %s floor of %d and still argues for one of 0', $module, $which, $floor)];
    }

    return [];
}

it('G7 — a floor of zero says what holds the module instead', function (): void {
    // The number without the argument is the shape this rule is really about.
    // A floor of zero is a position — a component holds state, an adapter
    // forwards a call, a stand-in is a fake by construction — and that position
    // lived in the runner's source and in `Kind`, where it is said once about
    // everybody. The manifests carried the bare number, so nothing asked a
    // module declaring one what, in that module, holds the decisions instead.
    //
    // Nothing here judges whether the argument is a good one. What it refuses
    // is a zero nobody wrote an argument for, which is the state in which there
    // is nothing to judge.
    $unargued = [];

    foreach (Module::all() as $module) {
        $unargued = [
            ...$unargued,
            ...whatIsWrongWithAFloor($module->name, 'coverage', $module->coverageFloor, $module->whyCoverageIsZero),
            ...whatIsWrongWithAFloor($module->name, 'mutation', $module->mutationFloor, $module->whyMutationIsZero),
        ];
    }

    sort($unargued);

    expect($unargued)->toBe([], sprintf(
        "These floors say a number and nothing else:\n  %s\n\n"
        . "Put the argument beside the number, in the module's own manifest:\n\n"
        . "    \"floors\": { \"mutation\": 0, \"mutation-is-zero-because\": \"…\" }\n\n"
        . 'Say what holds that module\'s decisions instead — the contract its adapters are '
        . 'run through, the rule that reads its registry, the test that reads its table arm '
        . 'by arm. An argument that travels with the number is one the next reader can '
        . 'disagree with; a bare zero is one nobody can (G7).',
        implode("\n  ", $unargued),
    ));
});

it('G7 — the floors may be raised and may not quietly net out', function (): void {
    // A ratchet on the declared numbers rather than on the measured ones.
    //
    // The obvious rule — the floor tracks actual coverage and may only rise —
    // is a gate that blocks its own cure: a module gaining a well-tested class
    // raises its real coverage without anyone deciding to, and the build turns
    // red for an improvement. Declared floors move only when somebody edits a
    // manifest, so this can never be tripped by code getting better.
    //
    // A lowered floor is one line in a reviewed diff. This catches the case
    // where nobody reads it.
    //
    // Flush with what is declared, because a ratchet with slack in it is a
    // budget. At 2,100 against 2,200 declared, one module could drop its
    // mutation floor from 100 to 0 and this would still pass — which is the
    // one move the number exists to catch, permitted by the number itself.
    //
    // A commit that raises a floor raises this in the same breath. Leaving it
    // behind is how the slack comes back, one improvement at a time.
    $total = 2200;

    $declared = array_sum(array_map(
        static fn(Module $module): int => ($module->coverageFloor ?? 0) + ($module->mutationFloor ?? 0),
        Module::all(),
    ));

    expect($declared)->toBeGreaterThanOrEqual($total, sprintf(
        "The declared floors now total %d and the ratchet is %d.\n\n"
        . 'Floors may be raised freely. Lowering one means raising another or lowering '
        . 'this number, and lowering this number is the deliberate act it should be — the '
        . 'same shape as the `planned` ceiling, and for the same reason: a budget decays '
        . "quietly and a ratchet does not.\nIf a module genuinely cannot hold its floor, "
        . 'say which and why in the commit that lowers it (G7).',
        $declared,
        $total,
    ));

    fwrite(STDOUT, sprintf("\n  declared floors total: %d (ratchet %d)\n", $declared, $total));
});
