<?php

declare(strict_types=1);

use Tests\Support\Tree;
use Tests\Support\WhatTheContractDeclares;

// N1-R17 — what the wire carries that this app does not read, and why.
//
// `WhatTheContractDoesNotCarryTest` watches one direction: a requirement this
// app cannot answer because the contract carries nothing to answer it with. The
// day lemonfiber adds the field, that suite goes red and says which requirement
// to go and answer.
//
// This is the other direction, and nothing was watching it. lemonfiber adds a
// field; no requirement asks this app to show it; no reader names it; every gate
// stays green; and the fact that somebody should have decided is recorded
// nowhere. The contract has moved under this app several times in a week, and
// each time the only thing that noticed was somebody reading a diff.
//
// So every field the contract declares on an envelope this app reads is either
// **named by a `WireField`** — this side has a word for it — or **listed below
// with a reason**. A field that is neither fails this suite, which is the
// moment somebody decides rather than the moment somebody notices.
//
// **It is a register of decisions, not of gaps.** Most rows here will never be
// read, and saying so is the point: *the household surface is blocked* and *no
// requirement asks for this* are different answers, and both are better than an
// unread field nobody weighed. A row is removed by reading the field, never by
// deleting the row because it is inconvenient.
//
// **`N2-R14` cuts the other way here.** That rule forbids inventing a value the
// contract does not carry. This one refuses the opposite habit — building a
// surface *because* the wire happens to carry a field. A field wants a
// requirement before it wants a screen, and the row is where that is said.

/**
 * Every field this app has read and decided not to read, and why.
 *
 * `envelope` and `field` name one field exactly. `because` is the decision, and
 * it is the only part that survives the person who made it.
 */
const WHAT_THIS_APP_DOES_NOT_READ = [
    [
        'envelope' => 'ErrorEnvelope',
        'field' => 'cause',
        'because' => 'What lay under a refusal, as whatever shape the check that raised it had. `N1-R10` '
            . 'has the app tell a refused credential from a stack that is not answering, and it decides '
            . 'that from the response rather than from a body — a stack that is asleep sends no envelope '
            . 'at all, so a reading that depended on one would work only where it was least needed.',
    ],
    [
        'envelope' => 'ErrorEnvelope',
        'field' => 'detail',
        'because' => 'The same reading, and the same answer. `N2-R3` has a finding carry its meaning in '
            . 'the words the core produced, and that arrives on the `doctor` envelope where a screen can '
            . 'show it; an error body reaches the operator as an obstacle, which is one of four sentences '
            . 'this app has written.',
    ],
    [
        'envelope' => 'HouseholdEnvelope',
        'field' => 'allows',
        'because' => 'The household surface is blocked rather than unstarted. `N3-R1` to `N3-R3` wait on the '
            . 'wire saying who is asking, which it does not, so nothing under `household` is read at all — '
            . 'see `app-modules/household/src/README.md` and the gap register beside this one.',
    ],
    [
        'envelope' => 'HouseholdEnvelope',
        'field' => 'available',
        'because' => 'The same block. Reading what a household offers before the app can tell one member '
            . 'from another would be building the surface `N3-R3` refuses to let anything rest on.',
    ],
    [
        'envelope' => 'HouseholdEnvelope',
        'field' => 'filtering',
        'because' => 'The same block.',
    ],
    [
        'envelope' => 'HouseholdEnvelope',
        'field' => 'policy',
        'because' => 'The same block. A request policy is a household decision, and the household surface '
            . 'cannot yet say whose decision it is.',
    ],
    [
        'envelope' => 'RepairEnvelope',
        'field' => 'acted',
        'because' => 'What became of a repair is read from its outcome, which says what happened rather '
            . 'than whether anything did. A boolean beside it is a second answer to one question, and '
            . '`N2-R4` has the app state what a repair did in the words the core produced.',
    ],
    [
        'envelope' => 'RepairEnvelope',
        'field' => 'beyond',
        'because' => 'Remedies for checks this repair did not attempt. `N2-R4` offers the repair the core '
            . 'offers for the finding in hand; a list of other checks belongs to a screen about the whole '
            . 'run, and no requirement asks for one.',
    ],
    [
        'envelope' => 'StatusEnvelope',
        'field' => 'undeclared',
        'because' => 'Containers the machine is running that this stack\'s own configuration does not '
            . 'declare. Genuinely operator-facing — something is running here that is not part of your '
            . 'stack — and no requirement in `N1` to `N4` asks the app to say so. Raise it against the '
            . 'spec before reading it, which is `N1-R17`.',
    ],
    [
        'envelope' => 'StatusEnvelope',
        'field' => 'unsupported',
        'because' => 'What this stack cannot do, and why. It is the stack describing its own limits '
            . 'rather than its condition, and `N2-R1` opens this app on a verdict. A screen mixing the '
            . 'two would report a limitation as something wrong.',
    ],
    [
        'envelope' => 'StuckEnvelope',
        'field' => 'unsupported',
        'because' => 'The same field on the reading `N2-R9` is about, and the same answer.',
    ],
    [
        'envelope' => 'UpdateEnvelope',
        'field' => 'backup',
        'because' => 'The snapshot taken before a run. `N2-R19` has the app say which way back the stack '
            . 'named, and the stack performs it — naming the snapshot would be this app describing a file '
            . 'it cannot reach. `app-modules/backups/src/README.md` holds the rest of this answer.',
    ],
    [
        'envelope' => 'UpdateEnvelope',
        'field' => 'confirmed',
        'because' => 'Whether the stack considers an update confirmed. `N2-R17` is about the operator '
            . 'confirming it *here*, before it runs, and reading the stack\'s own flag would let a '
            . 'confirmation somewhere else stand in for the one this app asked for.',
    ],
    [
        'envelope' => 'UpdateEnvelope',
        'field' => 'halted',
        'because' => 'When an update was stopped part way. `N2-R18` reports what became of each service, '
            . 'which is what an operator acts on; *when it stopped* is a different screen and no '
            . 'requirement asks for one.',
    ],
    [
        'envelope' => 'UpdateEnvelope',
        'field' => 'in_flight',
        'because' => 'The services an update is being applied to right now. Taking one hands back a job '
            . '(`Underway`), and what a job is doing is the job\'s reading rather than this one\'s — two '
            . 'places saying what is in flight can disagree, and the operator would believe whichever '
            . 'they were looking at.',
    ],
    [
        'envelope' => 'UpdateEnvelope',
        'field' => 'stack_edits',
        'because' => 'The diffs an update would make to the stack\'s own configuration. `N2-R12` refuses '
            . 'to let this app set or change a value; showing a diff of one is the near neighbour of that '
            . 'and wants a requirement of its own before it wants a screen.',
    ],
];

/**
 * The envelopes this app actually reads.
 *
 * Read off the readers rather than listed, so an envelope somebody starts
 * reading is one this rule covers without an edit — which is the difference
 * between a register and a list that goes stale the first time the app grows.
 *
 * @return list<string>
 */
function everyEnvelopeThisAppReads(): array
{
    $named = [];

    foreach (Tree::filesUnder(Tree::at('app-modules/sdk/src'), '.php') as $file) {
        preg_match_all('/(\w+Envelope)::in\b/', (string) file_get_contents($file), $found);

        foreach ($found[1] as $envelope) {
            $named[] = $envelope;
        }
    }

    sort($named);

    return array_values(array_unique($named));
}

/**
 * Every wire name this app has a word for.
 *
 * `WireField` is the one place a field's name on the wire is spelled (`D4`), so
 * a field with a case here is one this side can read. It does not prove a
 * reader uses it for this envelope, and it does not need to: what this rule
 * watches for is a field nobody has a word for at all.
 *
 * @return list<string>
 */
function everyWireNameThisAppKnows(): array
{
    preg_match_all(
        "/case \\w+ = '([^']+)';/",
        (string) file_get_contents(Tree::at('app-modules/sdk/src/Api/WireField.php')),
        $found,
    );

    return $found[1];
}

/**
 * The rows, as `envelope.field`, for comparing against the contract.
 *
 * @return list<string>
 */
function everyFieldThisAppHasDecidedAbout(): array
{
    $decided = [];

    foreach (WHAT_THIS_APP_DOES_NOT_READ as $row) {
        $decided[] = sprintf('%s.%s', $row['envelope'], $row['field']);
    }

    return $decided;
}

it('N1-R17 — every field on an envelope this app reads has been decided about', function (): void {
    // The half that matters. A field lemonfiber adds is a field somebody has to
    // weigh, and this is what makes that happen on the day it arrives rather
    // than on the day a screen turns out to be missing something.
    $known = everyWireNameThisAppKnows();
    $decided = everyFieldThisAppHasDecidedAbout();
    $undecided = [];
    $read = 0;

    foreach (everyEnvelopeThisAppReads() as $envelope) {
        foreach (array_keys(WhatTheContractDeclares::fieldsOf(WhatTheContractDeclares::shapeOf($envelope))) as $field) {
            $read++;

            if (in_array($field, $known, strict: true)) {
                continue;
            }

            if (in_array(sprintf('%s.%s', $envelope, $field), $decided, strict: true)) {
                continue;
            }

            $undecided[] = sprintf('%s carries `%s`, and nothing here has a word for it', $envelope, $field);
        }
    }

    // A rule that has stopped finding the envelopes reports no violations, and
    // no violations is exactly what compliance looks like.
    expect($read)->toBeGreaterThan(1, 'no field was read off the contract, so this rule read nothing');

    expect($undecided)->toBe([], sprintf(
        "The contract carries these and this app has not said anything about them:\n  %s\n\n"
        . 'Read the field — give it a `WireField` case and a reader — or add a row to '
        . "`WHAT_THIS_APP_DOES_NOT_READ` saying why not. A field wants a requirement before it wants a "
        . "screen (`N1-R17`), so *no requirement asks for this* is a complete answer and an unweighed "
        . "field is not.\n",
        implode("\n  ", $undecided),
    ));
});

it('N1-R17 — every row still names a field the contract has', function (): void {
    // The half that keeps the register honest about the present. A field
    // renamed or removed leaves a row explaining a decision about something
    // that is not there, and the rule above would then be excusing a field
    // nobody can find.
    $gone = [];

    foreach (WHAT_THIS_APP_DOES_NOT_READ as $row) {
        $shape = WhatTheContractDeclares::shapeOf($row['envelope']);

        if ($shape === '') {
            $gone[] = sprintf('%s is not an envelope the contract has', $row['envelope']);

            continue;
        }

        if (! array_key_exists($row['field'], WhatTheContractDeclares::fieldsOf($shape))) {
            $gone[] = sprintf('%s no longer carries `%s`', $row['envelope'], $row['field']);
        }
    }

    expect($gone)->toBe([], sprintf(
        "These rows describe a decision about something the contract no longer has:\n  %s\n\n"
        . "Delete the row. A register listing fields that are gone is one nobody trusts about the ones that are not.\n",
        implode("\n  ", $gone),
    ));
});

it('N1-R17 — every row says why', function (): void {
    // The reason is the only part that survives the person who wrote it, and a
    // row without one is a row the next reader has to re-decide from scratch —
    // which is the state this register exists to replace.
    $thin = [];

    foreach (WHAT_THIS_APP_DOES_NOT_READ as $row) {
        if (trim($row['because']) === '') {
            $thin[] = sprintf('%s.%s has no reason', $row['envelope'], $row['field']);
        }
    }

    expect($thin)->toBe([], sprintf("These rows do not say enough to act on:\n  %s\n", implode("\n  ", $thin)));
});
