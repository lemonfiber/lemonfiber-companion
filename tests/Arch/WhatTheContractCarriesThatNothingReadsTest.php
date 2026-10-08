<?php

declare(strict_types=1);

use Modules\Dx\Internal\WhatTheContractDeclares;
use Tests\Support\Tree;
use Tests\Support\WhatTheReadersRead;
use Tests\Support\WhatThisAppDoesNotRead;
use Tests\Support\WhereAShapeHoldsItself;

// What the wire carries that this app does not read, and why.
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
// So every path the contract declares on an envelope this app reads is either
// **read by a reader** — followed to the subscript that takes it — or **listed
// in `WhatThisAppDoesNotRead` with a reason**. A path that is neither fails this suite, which is the
// moment somebody decides rather than the moment somebody notices.
//
// **A name is not a place, and the difference is the whole of this rule.**
// Asking whether this app has a `WireField` case for a field answers yes for
// every path that field's name appears at: one case for `state` covers
// `error.state`, `status.services[].state`, `doctor.findings[].verdict.state`
// and `update.changelog.state` alike. Twenty-nine names do that across
// ninety-nine of the two hundred and twenty-three paths here.
//
// The last of those four is the argument. `update.changelog.state` says whether
// the release record matches the running build, and the top-level `state` beside
// it says whether any service would move. Reading the first for the second
// offers updates nobody can take, and a register keyed on names cannot see
// which one a reader takes, because a case written for the `status` envelope
// answers for both. So the question is *does anything read this path*, and
// `Tests\Support\WhatTheReadersRead` answers it by following the reader.
//
// **It is a register of decisions, not of gaps.** Most rows here will never be
// read, and saying so is the point: *the household surface is blocked* and *no
// requirement asks for this* are different answers, and both are better than an
// unread field nobody weighed. A row is removed by reading the field, never by
// deleting the row because it is inconvenient.
//
// **The substitution rule cuts the other way here.** It forbids inventing a value the
// contract does not carry. This one refuses the opposite habit — building a
// surface *because* the wire happens to carry a field. A field wants a
// requirement before it wants a screen, and the row is where that is said.

/**
 * The envelopes this app actually reads.
 *
 * Read off the readers rather than listed, so an envelope somebody starts
 * reading is one this rule covers without an edit — which is the difference
 * between a register and a list that goes stale the first time the app grows.
 *
 * Kept beside `WhatTheReadersRead::envelopes()` rather than replaced by it, and
 * the two are held against each other below. This one finds an envelope by the
 * call that opens it and knows nothing about what happens next; that one finds
 * it by following a payload to a subscript. An envelope the first sees and the
 * second does not is a reader the following stopped partway through, which is
 * the one failure that would make every rule here quietly narrower.
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
 * Every path every envelope declares, as `Envelope.path`.
 *
 * Nested and not only top-level, because the fields a decision is most often
 * owed about are nested: `update.changelog.state` is two levels down, and a
 * register that read the top of a payload reported that envelope clean.
 *
 * @return list<string>
 */
function everyPathTheContractDeclares(): array
{
    $declared = [];

    foreach (WhatTheReadersRead::envelopes() as $envelope) {
        foreach (WhatTheContractDeclares::everyPathIn(WhatTheContractDeclares::shapeOf($envelope)) as $path) {
            $declared[] = sprintf('%s.%s', $envelope, $path);
        }
    }

    return $declared;
}

/**
 * Whether a row's path covers this one.
 *
 * A row covers its own path and everything beneath it, because that is what a
 * decision not to read something means: `changelog.running.groups` is one
 * decision about the release notes and the eight paths inside them, and eight
 * rows saying so would be one decision written out eight times.
 *
 * It is not a way to excuse a payload wholesale, and the rule below prints what
 * each row covers so that a row which has quietly grown over something somebody
 * should have read is visible at a glance rather than worked out.
 */
function thePathIsCoveredBy(string $path, string $row): bool
{
    // Both ways into a subtree: a field (`.`) and an entry (`[]`). A check that
    // knew one of them would cover a subtree everywhere except through the
    // other, which is the shape of gap this whole file exists to refuse.
    return $path === $row
        || str_starts_with($path, sprintf('%s.', $row))
        || str_starts_with($path, sprintf('%s[', $row));
}

/**
 * What a path hangs off, which is where a decision about it belongs.
 *
 * A field under something nothing reads is not a decision of its own: the app
 * does not read `update.changelog.running.groups`, so whether it reads the
 * `title` of each group is not a question anybody has to answer. Reporting the
 * frontier — the first path down each branch that nothing reads — is what keeps
 * this register a list of decisions instead of a transcription of the contract.
 *
 * The entry marker comes off with the last segment, because a reader steps into
 * a list and then reads a field of it: `changelog.releases[].version` hangs off
 * `changelog.releases`, which is the subscript that found the list.
 */
function theHolderOf(string $path): string
{
    $at = strrpos($path, '.');

    return $at === false ? $path : rtrim(substr($path, 0, $at), '[]');
}

/** The envelope a path is on, which is the payload every path here hangs off. */
function theEnvelopeIn(string $path): string
{
    $at = strpos($path, '.');

    return $at === false ? $path : substr($path, 0, $at);
}

it('every path on an envelope this app reads has been decided about', function (): void {
    // The half that matters. A field lemonfiber adds is a field somebody has to
    // weigh, and this is what makes that happen on the day it arrives rather
    // than on the day a screen turns out to be missing something.
    $read = WhatTheReadersRead::paths();
    $declared = everyPathTheContractDeclares();
    $undecided = [];

    foreach ($declared as $path) {
        if (in_array($path, $read, strict: true)) {
            continue;
        }

        foreach (WhatThisAppDoesNotRead::rows() as $row) {
            if (thePathIsCoveredBy($path, $row['path'])) {
                continue 2;
            }
        }

        // The holder of a top-level field is the envelope, whose payload is
        // read by definition — which is what makes every top-level field a
        // decision and leaves the deeper ones to the branch they hang off.
        $holder = theHolderOf($path);

        if ($holder !== theEnvelopeIn($path) && ! in_array($holder, $read, strict: true)) {
            continue;
        }

        $undecided[] = $path;
    }

    // A rule that has stopped finding the envelopes reports no violations, and
    // no violations is exactly what compliance looks like. Both halves are
    // asserted: a contract that declared nothing, and a following that read
    // nothing, are two ways to the same green tick.
    expect(count($declared))->toBeGreaterThan(1, 'no path was read off the contract, so this rule read nothing');
    expect(count($read))->toBeGreaterThan(1, 'no path was read off a reader, so this rule read nothing');

    expect($undecided)->toBe([], sprintf(
        "The contract carries these and this app has not said anything about them:\n  %s\n\n"
        . 'Read the path — give it a `WireField` case and a reader — or add a row to '
        . "`WhatThisAppDoesNotRead`, in the class for its envelope, saying why not. A field wants a requirement before it wants a "
        . "screen (`N1-R17`), so *no requirement asks for this* is a complete answer and an unweighed "
        . "field is not.\n",
        implode("\n  ", $undecided),
    ));
});

it('the reading follows a reader rather than recognising a name', function (): void {
    // What the rule above rests on, asserted on a pair that proves it. Both of
    // these are called `detail`, one is read and one is not, and a reading that
    // answered from `WireField` would call both of them read. The verdict's own
    // detail is what the check found, drawn at the foot of a finding; the one
    // on the single remedy an unverified verdict offers is not drawn.
    $read = WhatTheReadersRead::paths();

    expect($read)->toContain('DoctorEnvelope.findings[].verdict.detail')
        ->and($read)->not->toContain('DoctorEnvelope.findings[].verdict.remedy.detail');

    // And a second pair one level further in, where both paths are nested and
    // the app reads three of the five verbs the stack describes. A reading that
    // had quietly stopped following calls would answer `false` to both of
    // these, which the first expectation alone would not notice.
    expect($read)->toContain('StatusEnvelope.disturbs.starting.bound')
        ->and($read)->not->toContain('StatusEnvelope.disturbs.switching.bound');
});

it('every reach into a payload is one the reading placed', function (): void {
    // The failure this rule cannot survive quietly. A subscript the following
    // cannot seat reads a field the register is then told nothing reads, and
    // the answer to that is a row explaining why a field that *is* read is not
    // — a false decision, written down, that outlives everybody who could have
    // spotted it.
    //
    // So an unplaceable reach is fatal rather than silently unread. It names
    // the reader and the line, because what it means is that a reader here is
    // written in a shape the following does not model, and the fix is in one of
    // the two of them.
    expect(WhatTheReadersRead::unseated())->toBe([], sprintf(
        "These reach for a field off something this reading could not place:\n  %s\n\n"
        . 'Every one of them reads a field that the register will be told nothing reads. Either '
        . 'the reader seats its payload somewhere `WhatTheReadersRead` does not follow — a call it '
        . "cannot resolve, a value it cannot type — or the following is short of a shape.\n",
        implode("\n  ", WhatTheReadersRead::unseated()),
    ));
});

it('every envelope a reader opens is one the reading was seated on', function (): void {
    // The other half of the same guarantee, and the one that catches a whole
    // reader dropping out rather than one line of it. An envelope opened by
    // `XEnvelope::in` and missing from the following is an envelope whose every
    // field would read as unread, and the register's answer to that is fifty
    // rows nobody should ever have written.
    $seated = WhatTheReadersRead::envelopes();
    $opened = everyEnvelopeThisAppReads();
    $lost = [];

    foreach ($opened as $envelope) {
        // A payload the contract declares as a bare value, such as a start's
        // one line, has no field for a reach to seat on and no path the
        // register could be told is unread. Reading it is taking the value.
        if (in_array(WhatTheContractDeclares::shapeOf($envelope), ['string', 'int', 'bool'], strict: true)) {
            continue;
        }

        if (! in_array($envelope, $seated, strict: true)) {
            $lost[] = $envelope;
        }
    }

    expect($opened)->not->toBe([], 'no envelope was found being opened, so this rule read nothing');

    expect($lost)->toBe([], sprintf(
        "A reader opens these and the reading never seated a payload on them:\n  %s\n",
        implode("\n  ", $lost),
    ));
});

it('every row still names a path the contract has', function (): void {
    // The half that keeps the register honest about the present. A field
    // renamed or removed leaves a row explaining a decision about something
    // that is not there, and the rule above would then be excusing a field
    // nobody can find.
    $declared = everyPathTheContractDeclares();
    $gone = [];

    foreach (WhatThisAppDoesNotRead::rows() as $row) {
        if (! in_array($row['path'], $declared, strict: true)) {
            $gone[] = sprintf('%s is not a path the contract has', $row['path']);
        }
    }

    expect($gone)->toBe([], sprintf(
        "These rows describe a decision about something the contract no longer has:\n  %s\n\n"
        . "Delete the row. A register listing fields that are gone is one nobody trusts about the ones that are not.\n",
        implode("\n  ", $gone),
    ));
});

it('every row names a path nothing reads', function (): void {
    // The direction a name-based reading could not ask about at all, and the
    // one that had two rows wrong: a row saying nothing under `members` is read
    // while the app reads a member's name and every request under them, and a
    // row saying the entry for the release in use is not read while being up to date is
    // answered off it.
    //
    // A row like that is worse than a missing one. It reads as a decision
    // somebody took, it is false, and the next reader weighing whether to build
    // a screen believes it.
    $read = WhatTheReadersRead::paths();
    $overtaken = [];

    foreach (WhatThisAppDoesNotRead::rows() as $row) {
        if (in_array($row['path'], $read, strict: true)) {
            $overtaken[] = $row['path'];
        }
    }

    expect($overtaken)->toBe([], sprintf(
        "These rows say a path is not read and something reads it:\n  %s\n\n"
        . 'Delete the row, or move it down to the paths beneath that are genuinely unread. A row '
        . "covering a subtree it sits at the top of excuses the fields under it on a reason that is not true.\n",
        implode("\n  ", $overtaken),
    ));
});

it('every row says why, and says how much it covers', function (): void {
    // The reason is the only part that survives the person who wrote it, and a
    // row without one is a row the next reader has to re-decide from scratch.
    //
    // The count is printed rather than capped. A row covering a subtree is
    // sometimes exactly right — the release notes are one decision about nine
    // paths — and sometimes a row that has quietly grown over something
    // somebody should have read. A number a reader can see tells those apart;
    // a cap would refuse the first along with the second.
    $declared = everyPathTheContractDeclares();
    $thin = [];
    $covers = [];

    foreach (WhatThisAppDoesNotRead::rows() as $row) {
        if (trim($row['because']) === '') {
            $thin[] = sprintf('%s has no reason', $row['path']);
        }

        $under = 0;

        foreach ($declared as $path) {
            if (thePathIsCoveredBy($path, $row['path'])) {
                $under++;
            }
        }

        $covers[] = sprintf('%s covers %d', $row['path'], $under);
    }

    expect($thin)->toBe([], sprintf("These rows do not say enough to act on:\n  %s\n", implode("\n  ", $thin)))
        ->and($covers)->not->toBe([], 'no row was read, so this rule read nothing');
});

it('follows a shape that holds itself once around, and stops at the second time', function (): void {
    expect(WhereAShapeHoldsItself::repeatsItsTail('ConfigEnvelope.settings[].origin'))->toBeFalse()
        ->and(WhereAShapeHoldsItself::repeatsItsTail('ConfigEnvelope.settings[].origin.replaced.from'))->toBeFalse()
        ->and(WhereAShapeHoldsItself::repeatsItsTail('ConfigEnvelope.settings[].origin.replaced.from.replaced.from'))->toBeTrue()
        ->and(WhereAShapeHoldsItself::repeatsItsTail('A.b.b'))->toBeTrue()
        ->and(WhereAShapeHoldsItself::isEnteredAgain(['within' => '', 'paths' => ['$row' => ['A.x', 'A.x.y.x.y']], 'holds' => [], 'wires' => []]))->toBeTrue()
        ->and(WhereAShapeHoldsItself::isEnteredAgain(['within' => '', 'paths' => ['$row' => ['A.x.y']], 'holds' => [], 'wires' => []]))->toBeFalse();
});
