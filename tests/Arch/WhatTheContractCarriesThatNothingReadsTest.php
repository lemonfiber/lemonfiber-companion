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
        'path' => 'DoctorEnvelope.findings[].said',
        'because' => 'A summary line beside the meaning. `N2-R3` has a finding carry its code, its meaning '
            . 'and its remedy, and a second sentence saying roughly the meaning again is the core being '
            . 'chatty rather than a fact a screen is short of.',
    ],
    [
        'path' => 'DoctorEnvelope.findings[].verdict.note',
        'because' => 'A note on a check that passed. `N2-R3` is about what a finding must carry when '
            . 'something is wrong, and nothing is.',
    ],
    [
        'path' => 'DoctorEnvelope.findings[].verdict.cause',
        'because' => 'The whole error again, nested under the finding it explains. The app attributes a '
            . 'finding to what caused it from `caused_by`, which names the check — a name a screen can show '
            . 'two rows up. Reading the nested copy would give one finding two accounts of itself that can '
            . 'disagree, and `G4-R3` asks for the cause reported rather than each symptom, not for both.',
    ],
    [
        'path' => 'DoctorEnvelope.findings[].verdict.remedies[].detail',
        'because' => 'A remedy is an action an operator takes. `N2-R3` has it carried in the words the core '
            . 'produced, and the action is those words — a second line under each one turns a list of things '
            . 'to try into a page to read.',
    ],
    [
        'path' => 'DoctorEnvelope.findings[].verdict.remedy.detail',
        'because' => 'The same field on the single remedy an unverified verdict offers, and the same answer.',
    ],
    [
        'path' => 'ErrorEnvelope.remedies[].detail',
        'because' => 'The same again on a refusal. An error body reaches the operator as an obstacle, which '
            . 'is one of four sentences this app has written and not a page of the core\'s.',
    ],
    [
        'path' => 'HouseholdEnvelope.members',
        'because' => 'The household surface is blocked rather than unstarted, so nothing under a member is '
            . 'read — access, what they are asking for, what they have claimed, when they were last seen. '
            . 'One decision about the whole subtree rather than thirty rows saying it separately. `N3-R1` '
            . 'to `N3-R3` wait on the wire saying who is asking, which it does not; see '
            . '`app-modules/household/src/README.md` and the gap register beside this one.',
    ],
    [
        'path' => 'StatusEnvelope.disturbs.stopping_after_downloads',
        'because' => 'What stopping after the downloads finish would take away. `N2-R7` offers start, stop '
            . 'and restart, so this app has no verb this bound belongs to — and `N2-R8` states the bound on '
            . 'a disruptive action the operator is about to confirm, not on every verb the stack has.',
    ],
    [
        'path' => 'StatusEnvelope.disturbs.switching',
        'because' => 'The same, for switching. A verb this surface does not offer.',
    ],
    [
        'path' => 'StatusEnvelope.services[].describes',
        'because' => 'What a service is for, in the stack\'s words. Genuinely operator-facing — somebody '
            . 'reading a list of nineteen names would be better off knowing which is the one that fetches '
            . 'series — and no requirement in `N1` to `N4` asks for it. Raise it against the spec before '
            . 'reading it, which is `N1-R17`.',
    ],
    [
        'path' => 'UpdateEnvelope.applied[].detail',
        'because' => 'What went wrong for one service, in the core\'s words. `N2-R18` has the app report '
            . 'how the update ended for each service and tell the four endings apart, which the row does. '
            . 'A detail line under each is `G4-R4`\'s shape and `G4-R4` is about an error; this is an '
            . 'outcome. Worth raising rather than assuming.',
    ],
    [
        'path' => 'UpdateEnvelope.applied[].from',
        'because' => 'The version a service came off. `N2-R18` asks what became of it, not what it was — '
            . 'and the app already says what the stack is on now.',
    ],
    [
        'path' => 'UpdateEnvelope.applied[].to',
        'because' => 'The version it was going to, and the same answer.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.releases[].delivers',
        'because' => 'What a release delivers, in prose. `N2-R16` has the app distinguish a release the '
            . 'household would notice from one it would not, which is the decision; the prose is a '
            . 'changelog screen and no requirement asks for one.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.releases[].patches',
        'because' => 'The same, for what a release fixes.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.releases[].released_on',
        'because' => 'When a release was published. `N2-R13` has a reading carry its age wherever it is '
            . 'shown, which is about how old the *app\'s* answer is rather than how old a release is.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.requirements',
        'because' => 'Which requirement each release shipped, with a link. That is the spec talking about '
            . 'itself, and an operator deciding whether tonight is the night is not reading requirement '
            . 'identifiers.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.running',
        'because' => 'The full changelog entry for the release in use — its tag, its groups, what it '
            . 'carried. The app reads the version and whether it was withdrawn, which is what `N2-R15` and '
            . '`N2-R16` ask for; the rest is a changelog screen nobody has asked for.',
    ],
    [
        'path' => 'UpdateEnvelope.changes[].because',
        'because' => 'Why the stack would make this change. `N2-R17` has the confirmation name the services '
            . 'an update would change, and a reason per service is a paragraph where a list belongs.',
    ],
    [
        'path' => 'UpdateEnvelope.changes[].current',
        'because' => 'The version a service is on. Said once for the stack (`N2-R15`) rather than per '
            . 'service, because a confirmation is about the evening rather than about nineteen numbers.',
    ],
    [
        'path' => 'UpdateEnvelope.changes[].target',
        'because' => 'The version it would go to, and the same answer.',
    ],
    [
        'path' => 'UpdateEnvelope.changes[].jump',
        'because' => 'How large the version jump is — major, minor, patch, or untellable. `N2-R16` already '
            . 'has the stack answer the question an operator is actually asking, which is whether the '
            . 'household will notice; a semantic-version magnitude is a different claim and a weaker one.',
    ],
    [
        'path' => 'UpdateEnvelope.changes[].irreversible',
        'because' => 'Whether a change cannot be put back. This one has a requirement in flight rather than '
            . 'no requirement — `N2-R22` is proposed in spec#375, on the argument that `N2-R4` already '
            . 'demands exactly this of a repair and an update is the larger operation. The row goes when '
            . 'that lands and the app reads it.',
    ],
    [
        'path' => 'UpdateEnvelope.stack_edits',
        'because' => 'The diffs an update would make to the stack\'s own configuration, and the paths they '
            . 'touch. `N2-R12` refuses to let this app set or change a value; showing a diff of one is the '
            . 'near neighbour of that and wants a requirement of its own before it wants a screen.',
    ],
    [
        'path' => 'RepairEnvelope.beyond',
        'because' => 'Remedies for checks this repair did not attempt. `N2-R4` offers the repair the core '
            . 'offers for the finding in hand; a list of other checks belongs to a screen about the whole '
            . 'run, and no requirement asks for one.',
    ],
    [
        'path' => 'ErrorEnvelope.cause',
        'because' => 'What lay under a refusal, as whatever shape the check that raised it had. `N1-R10` '
            . 'has the app tell a refused credential from a stack that is not answering, and it decides '
            . 'that from the response rather than from a body — a stack that is asleep sends no envelope '
            . 'at all, so a reading that depended on one would work only where it was least needed.',
    ],
    [
        'path' => 'ErrorEnvelope.detail',
        'because' => 'The same reading, and the same answer. `N2-R3` has a finding carry its meaning in '
            . 'the words the core produced, and that arrives on the `doctor` envelope where a screen can '
            . 'show it; an error body reaches the operator as an obstacle, which is one of four sentences '
            . 'this app has written.',
    ],
    [
        'path' => 'HouseholdEnvelope.allows',
        'because' => 'The household surface is blocked rather than unstarted. `N3-R1` to `N3-R3` wait on the '
            . 'wire saying who is asking, which it does not, so nothing under `household` is read at all — '
            . 'see `app-modules/household/src/README.md` and the gap register beside this one.',
    ],
    [
        'path' => 'HouseholdEnvelope.available',
        'because' => 'The same block. Reading what a household offers before the app can tell one member '
            . 'from another would be building the surface `N3-R3` refuses to let anything rest on.',
    ],
    [
        'path' => 'HouseholdEnvelope.filtering',
        'because' => 'The same block.',
    ],
    [
        'path' => 'HouseholdEnvelope.policy',
        'because' => 'The same block. A request policy is a household decision, and the household surface '
            . 'cannot yet say whose decision it is.',
    ],
    [
        'path' => 'RepairEnvelope.acted',
        'because' => 'What became of a repair is read from its outcome, which says what happened rather '
            . 'than whether anything did. A boolean beside it is a second answer to one question, and '
            . '`N2-R4` has the app state what a repair did in the words the core produced.',
    ],
    [
        'path' => 'StatusEnvelope.unsupported',
        'because' => 'What this stack cannot do, and why. It is the stack describing its own limits '
            . 'rather than its condition, and `N2-R1` opens this app on a verdict. A screen mixing the '
            . 'two would report a limitation as something wrong.',
    ],
    [
        'path' => 'StuckEnvelope.unsupported',
        'because' => 'The same field on the reading `N2-R9` is about, and the same answer.',
    ],
    [
        'path' => 'UpdateEnvelope.backup',
        'because' => 'The snapshot taken before a run. `N2-R19` has the app say which way back the stack '
            . 'named, and the stack performs it — naming the snapshot would be this app describing a file '
            . 'it cannot reach. `app-modules/backups/src/README.md` holds the rest of this answer.',
    ],
    [
        'path' => 'UpdateEnvelope.confirmed',
        'because' => 'Whether the stack considers an update confirmed. `N2-R17` is about the operator '
            . 'confirming it *here*, before it runs, and reading the stack\'s own flag would let a '
            . 'confirmation somewhere else stand in for the one this app asked for.',
    ],
    [
        'path' => 'UpdateEnvelope.halted',
        'because' => 'When an update was stopped part way. `N2-R18` reports what became of each service, '
            . 'which is what an operator acts on; *when it stopped* is a different screen and no '
            . 'requirement asks for one.',
    ],
    [
        'path' => 'UpdateEnvelope.in_flight',
        'because' => 'The services an update is being applied to right now. Taking one hands back a job '
            . '(`Underway`), and what a job is doing is the job\'s reading rather than this one\'s — two '
            . 'places saying what is in flight can disagree, and the operator would believe whichever '
            . 'they were looking at.',
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
 * Every field an envelope declares, at every depth, as a path.
 *
 * Nested and not only top-level, because the field that proved this rule worth
 * having was nested: `G4-R4` asks for the technical detail under a verdict to
 * be available, and `detail` sits two levels down inside a list. A register
 * that read the top of a payload would have reported that envelope clean.
 *
 * `[]` marks a list and `{}` a map, so a path says where a field lives rather
 * than only what it is called — `changes[].irreversible` is one flag per
 * service and reads as one.
 *
 * @return list<string>
 */
function everyPathAnEnvelopeDeclares(string $envelope): array
{
    return array_values(array_unique(
        walkTheContract(WhatTheContractDeclares::shapeOf($envelope), $envelope),
    ));
}

/**
 * One type, walked into whatever it holds.
 *
 * Alternatives are walked rather than chosen between, because a tagged union
 * is how the contract writes a shape with arms — every arm's fields are fields
 * the app might meet, and picking one would leave the others unwatched.
 *
 * @return list<string>
 */
function walkTheContract(string $type, string $path): array
{
    $found = [];

    foreach (WhatTheContractDeclares::alternatives($type) as $alternative) {
        if (str_starts_with($alternative, 'array{')) {
            foreach (WhatTheContractDeclares::fieldsOf($alternative) as $name => [$optional, $held]) {
                $under = sprintf('%s.%s', $path, $name);
                $found = [...$found, $under, ...walkTheContract($held, $under)];
            }

            continue;
        }

        if (str_starts_with($alternative, 'list<')) {
            $found = [...$found, ...walkTheContract(
                WhatTheContractDeclares::inside($alternative, 'list<'),
                sprintf('%s[]', $path),
            )];

            continue;
        }

        if (str_starts_with($alternative, 'array<')) {
            $parts = WhatTheContractDeclares::split(WhatTheContractDeclares::inside($alternative, 'array<'), ',');
            $found = [...$found, ...walkTheContract($parts[1] ?? '', sprintf('%s{}', $path))];
        }
    }

    return $found;
}

/**
 * Whether a row's path covers this field.
 *
 * A row covers its own path and everything beneath it, because that is what a
 * decision not to read something actually means: the household surface is
 * blocked, so nothing under `members` is read, and thirty-eight rows saying so
 * separately would be one decision written out thirty-eight times.
 *
 * It is not a way to excuse a payload wholesale. A row naming a whole envelope
 * covers every field in it, and the rule below prints what each row covers —
 * so a row that has quietly grown to cover a subtree somebody should have read
 * is one a reader can see at a glance rather than one they have to work out.
 */
function thePathIsCoveredBy(string $path, string $row): bool
{
    // All three markers, because a subtree can be entered three ways: a field
    // (`.`), a list (`[]`) or a map (`{}`). A check that knew two of them would
    // cover a subtree everywhere except through the third, which is the shape
    // of gap this whole file exists to refuse.
    return $path === $row
        || str_starts_with($path, sprintf('%s.', $row))
        || str_starts_with($path, sprintf('%s[', $row))
        || str_starts_with($path, sprintf('%s{', $row));
}

/** The last segment of a path, which is the name the app would have a word for. */
function theLeafOf(string $path): string
{
    $at = strrpos($path, '.');

    return $at === false ? $path : substr($path, $at + 1);
}

it('N1-R17 — every field on an envelope this app reads has been decided about', function (): void {
    // The half that matters. A field lemonfiber adds is a field somebody has to
    // weigh, and this is what makes that happen on the day it arrives rather
    // than on the day a screen turns out to be missing something.
    $known = everyWireNameThisAppKnows();
    $undecided = [];
    $read = 0;

    foreach (everyEnvelopeThisAppReads() as $envelope) {
        foreach (everyPathAnEnvelopeDeclares($envelope) as $path) {
            $read++;
            // Every path starts with an envelope name and a dot, so there is
            // always one to find — read through a helper anyway, because a
            // `false` here would be silently added to and produce a name
            // nothing matches.
            $name = theLeafOf($path);

            if (in_array($name, $known, strict: true)) {
                continue;
            }

            foreach (WHAT_THIS_APP_DOES_NOT_READ as $row) {
                if (thePathIsCoveredBy($path, $row['path'])) {
                    continue 2;
                }
            }

            $undecided[] = sprintf('%s, and nothing here has a word for it', $path);
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

it('N1-R17 — every row still names a path the contract has', function (): void {
    // The half that keeps the register honest about the present. A field
    // renamed or removed leaves a row explaining a decision about something
    // that is not there, and the rule above would then be excusing a field
    // nobody can find.
    $everywhere = [];

    foreach (everyEnvelopeThisAppReads() as $envelope) {
        $everywhere = [...$everywhere, ...everyPathAnEnvelopeDeclares($envelope)];
    }

    $gone = [];

    foreach (WHAT_THIS_APP_DOES_NOT_READ as $row) {
        if (! in_array($row['path'], $everywhere, strict: true)) {
            $gone[] = sprintf('%s is not a path the contract has', $row['path']);
        }
    }

    expect($gone)->toBe([], sprintf(
        "These rows describe a decision about something the contract no longer has:\n  %s\n\n"
        . "Delete the row. A register listing fields that are gone is one nobody trusts about the ones that are not.\n",
        implode("\n  ", $gone),
    ));
});

it('N1-R17 — every row says why, and says how much it covers', function (): void {
    // The reason is the only part that survives the person who wrote it, and a
    // row without one is a row the next reader has to re-decide from scratch.
    //
    // The count is printed rather than capped. A row covering a subtree is
    // sometimes exactly right — the household surface is blocked, and that is
    // one decision about thirty-eight fields — and sometimes a row that has
    // quietly grown over something somebody should have read. A number a reader
    // can see tells those apart; a cap would refuse the first along with the
    // second.
    $thin = [];
    $covers = [];
    $everywhere = [];

    foreach (everyEnvelopeThisAppReads() as $envelope) {
        $everywhere = [...$everywhere, ...everyPathAnEnvelopeDeclares($envelope)];
    }

    foreach (WHAT_THIS_APP_DOES_NOT_READ as $row) {
        if (trim($row['because']) === '') {
            $thin[] = sprintf('%s has no reason', $row['path']);
        }

        $under = 0;

        foreach ($everywhere as $path) {
            if (thePathIsCoveredBy($path, $row['path'])) {
                $under++;
            }
        }

        $covers[] = sprintf('%s covers %d', $row['path'], $under);
    }

    expect($thin)->toBe([], sprintf("These rows do not say enough to act on:\n  %s\n", implode("\n  ", $thin)))
        ->and($covers)->not->toBe([], 'no row was read, so this rule read nothing');
});
