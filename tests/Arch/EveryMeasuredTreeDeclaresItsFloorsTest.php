<?php

declare(strict_types=1);
use Tests\Support\MeasuredTree;

// G7 — every tree the coverage report measures is held to a bar, declared in
// the manifest nearest it.
//
// The floors were one number for the whole run, which meant one tree hid
// another: a well-covered capability carried a bare adapter and the report said
// everything was fine. That is true about what it averaged and silent about
// what it averaged over.
//
// Per-tree floors only help if every measured tree has one, so an undeclared
// floor is an error rather than a default. A default would put the number back
// where nobody chose it, and a tree added tomorrow would inherit a bar somebody
// picked for a different tree — which is the silent exemption this change
// exists to remove. Where the manifest declares a kind, that kind's convention
// is named in the message instead, so declaring the floor is a ten-second job
// rather than a guess.
//
// **The rule was "in the module's own manifest", and two trees could not obey
// it.** `bridge/src` is a path package rather than a module — its namespace is
// `Lemonfiber\Native\` and its manifest declares no kind, so nothing could read
// it as one — and `bootstrap/Composition` has no manifest at all. Between them
// they are 2,900 lines of shipped PHP that `phpunit.xml` measures for coverage
// and no mutation bar reached, with nowhere to declare one. Nearest-above is
// the same principle with the module-shaped assumption taken out of it, and it
// is a derivation rather than a list: `MeasuredTree` reads the trees out of
// `phpunit.xml` and the floors out of whichever manifest is nearest each.

it('G7 — every measured tree is held to a floor its nearest manifest declares', function (): void {
    $undeclared = [];

    foreach (MeasuredTree::all() as $tree) {
        if ($tree->coverageFloor === null) {
            $undeclared[] = sprintf(
                '%s is measured and %s declares no coverage floor%s',
                $tree->path,
                $tree->manifest,
                $tree->conventionalCoverage(),
            );
        }

        if ($tree->mutationFloor === null) {
            $undeclared[] = sprintf(
                '%s is measured and %s declares no mutation floor%s',
                $tree->path,
                $tree->manifest,
                $tree->conventionalMutation(),
            );
        }
    }

    expect($undeclared)->toBe([], sprintf(
        "These trees are measured and held to no bar of their own:\n  %s\n\n"
        . "Add it to the manifest nearest the tree — a module's own, the plugin's, or "
        . "the root's — beside the kind that already generates a module's boundary "
        . "rules:\n\n"
        . "    \"extra\": { \"lemonfiber\": { \"floors\": "
        . "{ \"coverage\": 100, \"mutation\": 100 } } }\n\n"
        . 'There is deliberately no default. A default is a number nobody chose for this '
        . 'tree, and a tree that inherits one is exempt from the decision rather than '
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
function whatIsWrongWithAFloor(string $manifest, string $which, ?int $floor, ?string $why): array
{
    if ($floor === 0 && ($why === null || trim($why) === '')) {
        return [sprintf('%s declares a %s floor of 0 and nothing about what holds it instead', $manifest, $which)];
    }

    if ($floor !== null && $floor > 0 && $why !== null) {
        return [sprintf('%s declares a %s floor of %d and still argues for one of 0', $manifest, $which, $floor)];
    }

    return [];
}

it('G7 — a floor of zero says what holds the tree instead', function (): void {
    // The number without the argument is the shape this rule is really about.
    // A floor of zero is a position — a component holds state, an adapter
    // forwards a call, a stand-in is a fake by construction — and that position
    // lived in the runner's source and in `Kind`, where it is said once about
    // everybody. The manifests carried the bare number, so nothing asked a
    // manifest declaring one what, in that tree, holds the decisions instead.
    //
    // Nothing here judges whether the argument is a good one. What it refuses
    // is a zero nobody wrote an argument for, which is the state in which there
    // is nothing to judge.
    $unargued = [];

    foreach (MeasuredTree::all() as $tree) {
        $unargued = [
            ...$unargued,
            ...whatIsWrongWithAFloor($tree->manifest, 'coverage', $tree->coverageFloor, $tree->whyCoverageIsZero),
            ...whatIsWrongWithAFloor($tree->manifest, 'mutation', $tree->mutationFloor, $tree->whyMutationIsZero),
        ];
    }

    sort($unargued);

    expect($unargued)->toBe([], sprintf(
        "These floors say a number and nothing else:\n  %s\n\n"
        . "Put the argument beside the number, in the manifest that declares it:\n\n"
        . "    \"floors\": { \"mutation\": 0, \"mutation-is-zero-because\": \"…\" }\n\n"
        . 'Say what holds that tree\'s decisions instead — the contract its adapters are '
        . 'run through, the rule that reads its registry, the test that reads its table arm '
        . 'by arm. An argument that travels with the number is one the next reader can '
        . 'disagree with; a bare zero is one nobody can (G7).',
        implode("\n  ", $unargued),
    ));
});

it('G7 — no manifest is nearest to two measured trees', function (): void {
    // What keeps nearest-above a rule rather than a coincidence.
    //
    // One manifest declares one pair of numbers. Two trees reaching the same
    // manifest therefore share one bar between them, and sharing a bar is the
    // averaging this whole page exists to undo — except quieter, because
    // nothing in either tree says it is being judged alongside the other. The
    // root's manifest is where it would happen: it is the manifest every tree
    // falls back to, so a second tree added outside `app-modules/` and
    // `bridge/` would land on it by default and silently halve what
    // `bootstrap/Composition`'s floor means.
    //
    // The cure is a manifest beside the new tree's own source, which is the
    // arrangement `bridge/composer.json` and `bridge/src` are already in.
    $held = [];

    foreach (MeasuredTree::all() as $tree) {
        $held[$tree->manifest][] = $tree->path;
    }

    $shared = [];

    foreach ($held as $manifest => $trees) {
        if (count($trees) > 1) {
            $shared[] = sprintf('%s is nearest to %s', $manifest, implode(' and ', $trees));
        }
    }

    sort($shared);

    expect($shared)->toBe([], sprintf(
        "These manifests declare one bar and hold more than one tree to it:\n  %s\n\n"
        . 'Two trees behind one pair of numbers is the average this page exists to '
        . "undo, and it is the quiet kind: neither tree says it is sharing.\n"
        . 'Give the new tree a manifest of its own, beside its source, the way '
        . '`bridge/composer.json` sits beside `bridge/src` (G7).',
        implode("\n  ", $shared),
    ));

    expect($held)->not->toBe([], 'no tree is measured, so no manifest was asked to hold one');
});

it('G7 — the floors may be raised and may not quietly net out', function (): void {
    // A ratchet on the declared numbers rather than on the measured ones.
    //
    // The obvious rule — the floor tracks actual coverage and may only rise —
    // is a gate that blocks its own cure: a tree gaining a well-tested class
    // raises its real coverage without anyone deciding to, and the build turns
    // red for an improvement. Declared floors move only when somebody edits a
    // manifest, so this can never be tripped by code getting better.
    //
    // A lowered floor is one line in a reviewed diff. This catches the case
    // where nobody reads it.
    //
    // Flush with what is declared, because a ratchet with slack in it is a
    // budget. At 2,200 against 2,600 declared, one tree could drop its
    // mutation floor from 100 to 0 and this would still pass — which is the
    // one move the number exists to catch, permitted by the number itself.
    //
    // A commit that raises a floor raises this in the same breath. Leaving it
    // behind is how the slack comes back, one improvement at a time.
    $total = 2600;

    $declared = array_sum(array_map(
        static fn(MeasuredTree $tree): int => ($tree->coverageFloor ?? 0) + ($tree->mutationFloor ?? 0),
        MeasuredTree::all(),
    ));

    expect($declared)->toBeGreaterThanOrEqual($total, sprintf(
        "The declared floors now total %d and the ratchet is %d.\n\n"
        . 'Floors may be raised freely. Lowering one means raising another or lowering '
        . 'this number, and lowering this number is the deliberate act it should be — the '
        . 'same shape as the `planned` ceiling, and for the same reason: a budget decays '
        . "quietly and a ratchet does not.\nIf a tree genuinely cannot hold its floor, "
        . 'say which and why in the commit that lowers it (G7).',
        $declared,
        $total,
    ));

    fwrite(STDOUT, sprintf("\n  declared floors total: %d (ratchet %d)\n", $declared, $total));
});
