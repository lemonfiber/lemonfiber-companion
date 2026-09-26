<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Generated\Kind;
use Tests\Support\Tree;
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
    'Alerts', 'Archives', 'Bandwidth', 'Bundle', 'Clients', 'Config', 'Credentials', 'Doctor', 'Error', 'Forms',
    'FrontDoor', 'Glossary', 'Held', 'History', 'Hosting', 'Household', 'Invitation', 'Job', 'Log', 'Outbound',
    'Preview', 'Provenance', 'Repair', 'SelfUpdate', 'Space', 'Status', 'Stored', 'Stuck', 'Trace', 'Update',
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
    // First-run setup is the one thing the companion is required *not* to
    // carry out: it happens at the machine, and the app says so and why. So
    // the two kinds that are setup are not omissions. Each is read off what
    // the stack answers with rather than off its name: the setup endpoints
    // answer with `wizard`, and `setup` is produced by the command line's own
    // setup and by no endpoint.
    //
    // A walkthrough is not one of them. It is an ordinary action taking an
    // item — the first acquisition narrated end to end, on a stack already
    // set up — and its lines and its handover are for the app to show. It
    // waits below with everything else nobody has offered yet.
    'Setup' => 'N1-R4 — setup settles at the machine, and the app says so rather than offering it',
    'Wizard' => 'N1-R4 — the wizard is setup asking its questions, which happens at the machine',
];

/**
 * Kinds nobody has offered yet.
 *
 * Early, and these have been seen. Moving one up to `OFFERED` is the work;
 * moving one to `ELSEWHERE` needs a requirement written first.
 */
const NOT_YET = [
    'Admission', 'Adoption', 'Backup', 'Beside', 'Catalogue', 'Dashboard', 'Import', 'Lifecycle', 'Migration', 'Music', 'Plugins', 'Pull', 'Quality', 'Removal', 'Replacement', 'Reset', 'Restore',
    'Seed', 'Start', 'Step', 'StopSeeding', 'Substitution', 'Undo', 'Uninstall', 'Upgrade', 'Version',
    'Walkthrough', 'Watch', 'Wiring', 'Word',
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

/**
 * The kinds offered nowhere here that this app's code names, tests aside.
 *
 * Naming one is not reading it: `dx` names `AdmissionEnvelope` to build what a
 * stand-in stack answers, and follows none of its fields.
 *
 * @return list<string>
 */
function theUnofferedKindsTheCodeNames(): array
{
    $source = '';

    foreach ([...Tree::filesUnder(Tree::at('app-modules'), '.php'), ...Tree::filesUnder(Tree::at('bridge/src'), '.php')] as $file) {
        if (! str_contains($file, '/tests/')) {
            $source .= (string) file_get_contents($file);
        }
    }

    $named = [];

    foreach ([...NOT_YET, ...array_keys(ELSEWHERE)] as $kind) {
        if (preg_match(sprintf('/\b%sEnvelope\b/', $kind), $source) === 1) {
            $named[] = $kind;
        }
    }

    sort($named);

    return $named;
}

it('states on the parity page the counts these lists come to', function (): void {
    // The page says how far this app is from parity, and the three lists above
    // are what that is measured from. A page that says a number these lists do
    // not come to reads as current and is not.
    $page = (string) preg_replace('/\s+/', ' ', (string) file_get_contents(Tree::at('.docs/requirements/what-a-screen-owes.md')));
    $named = theUnofferedKindsTheCodeNames();
    $names = array_map(static fn(string $kind): string => sprintf('`%s`', $kind), $named);
    $last = array_pop($names);
    $listed = $names === [] ? (string) $last : sprintf('%s and %s', implode(', ', $names), $last);

    expect($page)
        ->toContain(sprintf('The SDK ships %d envelopes and this app follows %d.', count(Kind::cases()), count(OFFERED)))
        ->toContain(sprintf('Of the rest, %d are never named by the code in `app-modules` or `bridge`', count(NOT_YET) + count(ELSEWHERE) - count($named)))
        ->toContain(sprintf('and %d more — %s — are named without being followed.', count($named), $listed));
});
