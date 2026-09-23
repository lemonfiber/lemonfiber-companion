<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Generated\Kind;
use Tests\Support\WhatTheReadersRead;

/**
 * Every action available from another surface is offered by the app,
 * except where a requirement says otherwise and why.
 *
 * The stack's surface is not a list somebody maintains here: it is the `Kind`
 * enum the SDK generates from `contract/web-api.contract.json`. Every kind is
 * something another surface can show or do, so the question is
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

/**
 * Kinds the app offers today.
 *
 * Held to what the readers actually open rather than kept by hand. This said
 * *none* for as long as it existed while nine readers landed under it, and
 * nothing noticed: the three lists were held to covering every kind between
 * them, and no list was ever held to being true.
 */
const OFFERED = [
    'Config', 'Doctor', 'Error', 'Held', 'History', 'Hosting', 'Household',
    'Job', 'Log', 'Outbound', 'Provenance', 'Repair', 'Status', 'Stuck',
    'Update',
];

/**
 * Kinds the app deliberately does not offer, each with the requirement saying
 * why.
 *
 * The escape clause is narrow on purpose — "except where a requirement
 * here states otherwise **and why**" — so an entry needs a requirement, not a
 * judgement. Anything that merely has not been built yet belongs below.
 */
const ELSEWHERE = [
    // On a launch with no stack configured the app says that setup
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
 * Early, and these have been seen. Moving one up to `OFFERED` is the work;
 * moving one to `ELSEWHERE` needs a requirement written first.
 */
const NOT_YET = [
    'Admission', 'Adoption', 'Alerts', 'Archives', 'Backup',
    'Bandwidth', 'Beside', 'Bundle', 'Catalogue', 'Clients',
    'Credentials', 'Dashboard', 'Forms', 'FrontDoor',
    'Glossary', 'Import',
    'Invitation', 'Lifecycle', 'Migration', 'Music',
    'Plugins', 'Preview', 'Pull', 'Quality', 'Removal',
    'Replacement', 'Reset', 'Restore', 'Seed', 'SelfUpdate',
    'Space', 'Start', 'Step', 'StopSeeding', 'Stored',
    'Substitution', 'Trace', 'Undo', 'Uninstall', 'Upgrade',
    'Version', 'Watch', 'Wiring', 'Word',
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

/**
 * The kinds something in this app actually opens.
 *
 * Asked of the readers rather than of the screens, and that is the honest
 * reach of it: a kind nothing reads cannot be on a screen, and one that is
 * read is one this app has taken a position on. Following it further — to the
 * screen that draws it — is the next thing this could be taught, and until it
 * is, `OFFERED` claims *read* rather than *drawn*.
 *
 * `WhatTheReadersRead` is what answers, so a reader reaching the wire by a
 * route this file has never heard of is still counted. That matters already:
 * a log window arrives held in its envelopes rather than through
 * `LogEnvelope::in`, and a rule scanning for the latter would call the one
 * screen built on it unoffered.
 *
 * @return list<string>
 */
function everyKindThisAppOpens(): array
{
    $kinds = [];

    foreach (WhatTheReadersRead::envelopes() as $envelope) {
        $kinds[] = str_replace('Envelope', '', $envelope);
    }

    sort($kinds);

    return $kinds;
}

it('N1-R2 — every kind said to be offered is one something here reads', function (): void {
    // The claim in the other direction, and the one no rule made. Three lists
    // were held to covering every kind between them and none was held to being
    // true, so `OFFERED` could say anything — including nothing, which is what
    // it said while nine readers landed under it.
    $claimed = array_values(array_diff(OFFERED, everyKindThisAppOpens()));

    expect($claimed)->toBe([], sprintf(
        "These are claimed as offered and nothing here reads them:\n  %s\n\n"
        . 'A kind in `OFFERED` that no reader opens is a surface this file says exists '
        . "and the app does not have.\n"
        . 'Move it to `NOT_YET`, or to `ELSEWHERE` with the requirement that excuses '
        . 'it (N1-R2).',
        implode("\n  ", $claimed),
    ));
});

it('N1-R2 — nothing read here is still waiting to be offered', function (): void {
    // The drift that actually happened, now caught the moment it starts. A
    // reader lands, nobody moves the kind up, and `NOT_YET` goes on saying
    // somebody has yet to build the thing they just built — which reads as a
    // to-do list and is a lie about the app.
    $built = array_values(array_intersect(
        [...NOT_YET, ...array_keys(ELSEWHERE)],
        everyKindThisAppOpens(),
    ));

    expect($built)->toBe([], sprintf(
        "Something here reads these and they are not in `OFFERED`:\n  %s\n\n"
        . '`NOT_YET` claims nobody has offered it and `ELSEWHERE` claims a requirement '
        . "says not to, and a reader opening it contradicts both.\n"
        . 'Move it up (N1-R2).',
        implode("\n  ", $built),
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
