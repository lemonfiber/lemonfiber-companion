<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Generated\Kind;

/**
 * `N1-R2` — every action available from another surface is offered by the app,
 * except where a requirement says otherwise and why.
 *
 * The stack's surface is not a list somebody maintains here: it is the `Kind`
 * enum the SDK generates from `contract/web-api.contract.json`. Every kind is
 * something another surface can show or do, so the question `N1-R2` asks is
 * answerable by reading it.
 *
 * **Three lists, and no count anywhere.** The obvious shape is a burndown — a
 * number of unoffered kinds that may only fall — and it has a failure this does
 * not: the number can rise on its own, because the stack can grow a kind. A
 * rule that reads a rise as a regression blames whoever is standing nearest,
 * and a rule that tolerates one cannot tell the two apart. Naming every kind
 * instead makes a new one *unclassified*, which fails saying which it is.
 *
 * So this does not measure progress. It refuses a surface nobody has looked at.
 */

/** Kinds the app offers today. */
const OFFERED = [];

/**
 * Kinds the app deliberately does not offer, each with the requirement saying
 * why.
 *
 * `N1-R2`'s escape clause is narrow on purpose — "except where a requirement
 * here states otherwise **and why**" — so an entry needs a requirement, not a
 * judgement. Anything that merely has not been built yet belongs below.
 */
const ELSEWHERE = [
    // N1-R35: on a launch with no stack configured the app says that setup
    // happens at the machine and offers pairing. Setup is the one thing the
    // companion is required *not* to carry out, so the three kinds that are
    // setup are not omissions.
    'Setup' => 'N1-R35 — setup happens at the machine, and the app says so',
    'Wizard' => 'N1-R35 — the wizard is the machine-side setup flow',
    'Walkthrough' => 'N1-R35 — the walkthrough is the machine-side setup flow',
];

/**
 * Kinds nobody has offered yet.
 *
 * **`Config` carries a second requirement, and this is where it will be read.**
 * `N1-R5` says the app must offer reconfiguration *in full* once connected, and
 * that is not gated here because it cannot honestly be gated yet. The settings
 * are not a list this side can know: `ConfigEnvelope` carries
 * `settings: list<array{key, secret, value}>`, which the stack reports at
 * runtime. There is nothing static to compare against.
 *
 * What it means when `Config` moves up: the screen renders the list the stack
 * sent, and the app holds no list of its own. An app that enumerates the
 * settings it knows about offers a subset the day the stack adds one, and
 * offers it silently — which is the whole of what `N1-R5` forbids.
 *
 * A rule guessing at that from the source text was considered and rejected. The
 * only shape available is "does anything here look like a list of setting
 * keys", which is prose-matching, and this codebase has already learned what
 * that costs: the `N1-R17` checker matched the comments explaining the rule,
 * and a rule that fires on its own documentation is a rule somebody deletes —
 * taking the real coverage with it.
 *
 * Not a debt marker and not a promise: it is the honest statement that the app
 * is early and these have been seen. Moving one up to `OFFERED` is the work;
 * moving one to `ELSEWHERE` needs a requirement written first.
 */
const NOT_YET = [
    'Admission', 'Adoption', 'Alerts', 'Archives', 'Backup',
    'Bandwidth', 'Beside', 'Bundle', 'Catalogue', 'Clients', 'Config',
    'Credentials', 'Dashboard', 'Doctor', 'Error', 'Forms',
    'FrontDoor', 'Glossary', 'History', 'Hosting', 'Household',
    'Import', 'Invitation', 'Job', 'Lifecycle', 'Log', 'Migration',
    'Music', 'Outbound', 'Preview', 'Provenance', 'Pull', 'Quality',
    'Removal', 'Repair', 'Replacement', 'Reset', 'Restore', 'Seed',
    'SelfUpdate', 'Space', 'Start', 'Status', 'Step', 'StopSeeding',
    'Stored', 'Stuck', 'Trace', 'Undo', 'Uninstall', 'Update',
    'Upgrade', 'Version', 'Watch', 'Word',
];

it('N1-R2 — every kind the stack offers has been looked at', function (): void {
    // A set difference rather than a membership test in a loop, and the
    // reason is worth writing down: the analyser can prove these lists cover
    // every case, so `in_array(...) === false` reads to it as always false and
    // it reports an error — an error that is present exactly while the code is
    // right, and that goes away when the stack grows a kind. A check that
    // passes only when it has nothing to say is not a check.
    $unclassified = array_values(array_diff(
        array_map(static fn(Kind $kind): string => $kind->name, Kind::cases()),
        [...OFFERED, ...array_keys(ELSEWHERE), ...NOT_YET],
    ));

    expect($unclassified)->toBe([], sprintf(
        "The stack offers these and nobody here has said anything about them:\n  %s\n\n"
        . 'N1-R2 says every action available from another surface is offered by the app, '
        . 'except where a requirement states otherwise and why. A kind that appears in '
        . "the SDK and in none of these lists is one nobody has answered for.\n"
        . 'Put it in OFFERED if a screen offers it, in ELSEWHERE with the requirement '
        . 'that excuses it, or in NOT_YET — which claims nothing except that somebody '
        . 'looked (N1-R2).',
        implode("\n  ", $unclassified),
    ));
});

it('N1-R2 — nothing is claimed for a kind the stack no longer offers', function (): void {
    // The other direction, and the one that rots quietly. A kind removed from
    // the contract leaves an entry here describing a surface that is gone —
    // which reads as current, and which would let `ELSEWHERE` go on excusing
    // something nobody could offer anyway.
    $stale = array_values(array_diff(
        [...OFFERED, ...array_keys(ELSEWHERE), ...NOT_YET],
        array_map(static fn(Kind $kind): string => $kind->name, Kind::cases()),
    ));

    expect($stale)->toBe([], sprintf(
        "These are classified here and the stack no longer offers them:\n  %s\n\n"
        . 'An entry for a kind that is gone describes a surface that does not exist, and '
        . 'reads as current. Remove it (N1-R2).',
        implode("\n  ", $stale),
    ));
});

it('N1-R2 — a kind is in exactly one list', function (): void {
    // Two lists claiming the same kind is two answers to one question, and the
    // one that gets read depends on which list somebody opened.
    $named = [...OFFERED, ...array_keys(ELSEWHERE), ...NOT_YET];
    $twice = array_values(array_diff_assoc($named, array_unique($named)));

    expect($twice)->toBe([], sprintf(
        "These are classified twice:\n  %s\n\n"
        . 'Two answers to one question, and which one is read depends on which list '
        . 'somebody opened first (N1-R2).',
        implode("\n  ", $twice),
    ));
});

it('N1-R2 — every excuse names a requirement', function (): void {
    // The escape clause is "except where a requirement here states otherwise
    // and why". An entry saying "not needed" is a judgement, not a
    // requirement, and judgements are what this clause exists to keep out.
    $unexplained = [];

    foreach (ELSEWHERE as $kind => $because) {
        if (preg_match('/\AN[0-9]+-R[0-9]+ — .+/', $because) !== 1) {
            $unexplained[] = sprintf('%s — %s', $kind, $because);
        }
    }

    expect($unexplained)->toBe([], sprintf(
        "These are excused without naming a requirement:\n  %s\n\n"
        . 'N1-R2 allows an exception only "where a requirement here states otherwise and '
        . 'why". A sentence that is not a requirement is a judgement, and a judgement is '
        . "what the clause exists to keep out.\nWrite the requirement first, then cite "
        . 'it here as `N1-R35 — the reason` (N1-R2).',
        implode("\n  ", $unexplained),
    ));
});
