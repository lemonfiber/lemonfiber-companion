<?php

declare(strict_types=1);

use Tests\Support\Tree;

// N2-R14 — the requirements this app cannot answer, and the field each waits on.
//
// `N2-R14` says that where the contract does not carry something a requirement
// asks the app to state, the app must not substitute a value of its own: the
// gap is raised against the contract and the requirement is answered there.
// `NoSubstitutedWireValueRule` enforces the first half in the readers. This is
// the second half, and it is a different kind of check — not *did somebody
// invent a value* but *is this still missing*.
//
// A list of gaps kept in prose would be a list nobody reads twice. What makes
// this one worth having is that every entry is checked against the generated
// types, in the direction that matters: the day lemonfiber carries the field,
// this suite goes **red** and says which requirement to go and answer. A gap
// register that only recorded gaps would quietly outlive them, and the
// requirement would stay unanswered with a note explaining why that was once
// reasonable.
//
// An entry is removed by answering its requirement, never by deleting the row.
//
// **Name an envelope only where the field could not land anywhere else.** A row
// that names one is searched in that one alone, so a guess about *where* an
// answer will arrive becomes a condition for noticing that it has. The `N2-R8`
// row below named `LifecycleEnvelope` on good reasoning — that is where what an
// operation touched already arrives — and the bound landed on the status
// reading instead, because it is worth having before the verb runs rather than
// after. Had the row kept the name, the gap would have closed and this suite
// would have gone on passing, which is the one failure a register cannot
// survive: it would have been a note explaining why a requirement was
// reasonably unanswered, attached to a requirement that was answerable.
//
// Leaving the envelope null costs a wider search and the odd false positive.
// A false positive sends somebody to read a row; a false negative is the
// register quietly becoming decoration.

/**
 * Every requirement this app is holding, and what it is waiting for.
 *
 * A row watches one of two things. `field` is a name that is missing from a
 * payload and would be added to it. `shape` is a whole payload that says
 * nothing about the subject at all, recorded as it stands — there is no field
 * name to watch for, because what is missing is not a field.
 */
const WHAT_THE_CONTRACT_DOES_NOT_CARRY = [
    [
        'requirement' => 'N1-R47',
        'asks' => 'to be handed pairing material by a stack somebody is already admitted to',
        // Unnamed for the reason the row above is, and because there is no
        // candidate anyway: no payload on this wire is pairing material, so
        // this is a type the contract does not have rather than a field one of
        // its types is missing.
        'envelope' => null,
        // `fingerprint` would be the truer name and cannot be used: the
        // contract already spends that word on a credential's likeness, for
        // telling two copies of a secret apart in a report, which has nothing
        // to do with the certificate a stack presents. A row watching it would
        // fire on `CredentialsEnvelope` today and go on firing.
        //
        // `expires` is the next best and is sound rather than merely free. The
        // app refuses material without it — {@see Pairing} throws
        // `PairingIsNotReadable::withoutIts()` — so material this app can use
        // necessarily carries it, and a payload that arrives without one has
        // not closed this gap whatever else it says.
        'field' => 'expires',
        'shape' => null,
        'raised' => 'This is the one requirement of `N1` that neither repository was tracking. The app '
            . 'cites every other one and lemonfiber\'s tracker cites no `N1` at all, so a requirement '
            . 'that is the stack\'s half of the app\'s own pairing had nobody holding it. The app is '
            . 'built to consume what it describes as what a stack hands somebody out of band, and '
            . 'nothing produces it: the address is served, the certificate is presented, and the '
            . 'material pairing the two so a phone can know the machine before it trusts the '
            . 'connection does not exist. Until it does, pairing depends on somebody assembling by '
            . 'hand what `N1-R47` says a surface must be able to produce on demand.',
    ],
    [
        'requirement' => 'N3-R4',
        'asks' => 'what a provider has left, before somebody in the house is told to ask for something',
        // No envelope named: nothing in the contract carries an allowance at
        // all, so there is no type this would be added to rather than a type it
        // is missing from.
        'envelope' => null,
        'field' => 'allowance',
        'shape' => null,
        'raised' => 'C8 makes a provider out of allowance one of the things worth carrying in a pocket, '
            . 'and the wire says nothing about one. The app shows what a provider reported and cannot '
            . 'say what is left of it.',
    ],
    [
        'requirement' => 'N3-R5',
        'asks' => 'when a spent allowance resets, told to the member before they ask',
        // The sibling of the row above and a separate row, because it waits on
        // a second field rather than on the same one: knowing an allowance is
        // spent is not knowing when it comes back, and *spent, and nothing about
        // when* is the answer that leaves somebody asking again every hour.
        'envelope' => null,
        'field' => 'resets_at',
        'shape' => null,
        'raised' => 'The wire carries no allowance and so carries no reset for one. A time worked out '
            . 'in the app would be `N2-R14` exactly — a guess at something the provider knows and '
            . 'the app does not, wrong in the cases somebody is actually waiting on.',
    ],
    [
        'requirement' => 'N3-R3',
        'asks' => 'the core to refuse a control a member is not entitled to, rather than the app omitting it',
        // A shape rather than a field, because what is missing is not a field.
        // The surface mints one token for the run and exchanges one password
        // for one session, and the admission says what the session is and not
        // who holds it — so every caller carrying it is the operator, and there
        // is no entitlement for the core to refuse against.
        'envelope' => 'AdmissionEnvelope',
        'field' => null,
        'shape' => 'array{token: string, until: string}',
        'raised' => 'The whole household surface waits on this rather than one requirement of it: '
            . '`N3-R1` has the app a person is given decided by the identity that signed in and '
            . "`N3-R2` has what a member may do be the core's answer. The wire carries what a "
            . 'member **may do** — `household.members[].access` has `administrator`, `disabled`, '
            . '`libraries`, `restriction` — and does not carry **who is asking**. That distinction '
            . 'is the row: an app holding the access list could read `administrator` and hide the '
            . "controls, and hiding them is exactly what `N3-R3` refuses to let anything rest on.\n\n"
            . 'Three more wait on it and are named here so the day this closes names all six. '
            . '`N3-R6` has a member\'s own requests carry their state in household terms, and '
            . '*their own* is the part with nothing behind it — the app reads every member\'s '
            . 'requests for the operator already (`N2-R11`), and cannot tell whose is whose. '
            . '`N3-R9` is the one of the six that **is** answered, ahead of the module existing, '
            . 'by refusing a member-facing type that holds an operator\'s — and it is named here '
            . 'so nobody reads its absence from this list as an oversight. `N3-R10` is answered in '
            . 'half: a member is not shown the fault, which is a rule about types and is kept, and '
            . 'is not yet told that it did not work and that the operator has been told, which '
            . 'needs somebody to tell.',
    ],
    [
        'requirement' => 'N3-R3',
        'asks' => 'the same thing, watched wherever an answer lands rather than only where one is missing',
        // The row above is pinned to `AdmissionEnvelope`, which catches the
        // answer arriving as a field on the envelope that exists and misses it
        // arriving as an envelope of its own. A second row rather than a
        // widened first one, the way `N3-R5` sits beside `N3-R4`: a row watches
        // one thing, and a row that watched two could half-fire.
        'envelope' => null,
        'field' => 'member',
        'shape' => null,
        'raised' => 'A surface that learned who was asking would say so by name, and the name is the '
            . 'thing to watch for rather than the envelope it lands on. Until one does, no payload '
            . 'on this wire carries a subject at all — the admission body is one password and the '
            . 'run token belongs to whoever started the process.',
    ],
];

/**
 * Every generated envelope, as the file it is written in.
 *
 * Read from the package rather than from a list here, so an envelope added to
 * the contract is one this rule sees without an edit.
 *
 * @return array<string, string>
 */
function everyGeneratedEnvelope(): array
{
    $found = [];

    foreach (Tree::filesUnder(Tree::at('vendor/lemonfiber/sdk-php/src/Generated'), '.php') as $path) {
        $found[basename($path, '.php')] = (string) file_get_contents($path);
    }

    return $found;
}

/**
 * The payload shape one envelope declares, as text.
 *
 * The `@phpstan-type Data` line and nothing else, so a field name that appears
 * in a docblock explaining the envelope is not read as a field it carries —
 * which is the mistake `K1` made about its own markers, in a different file.
 */
function thePayloadShapeOf(string $said): string
{
    preg_match('/@phpstan-type Data (.*)/', $said, $shape);

    return $shape[1] ?? '';
}

it('N2-R14 — every gap names an envelope the contract still has', function (): void {
    // The half that keeps the register honest about the present. An envelope
    // renamed in the contract would leave a row describing a type nobody
    // speaks, and the row below would then be checking nothing at all.
    $envelopes = everyGeneratedEnvelope();
    $gone = [];

    foreach (WHAT_THE_CONTRACT_DOES_NOT_CARRY as $gap) {
        $named = $gap['envelope'];

        if ($named === null) {
            continue;
        }

        if (! array_key_exists($named, $envelopes)) {
            $gone[] = sprintf('%s waits on %s, which the contract no longer has', $gap['requirement'], $named);
        }
    }

    expect($gone)->toBe([], sprintf(
        "These wait on an envelope that is not in the contract any more:\n  %s\n\n"
        . "Find where the field went and answer the requirement, or name the envelope it moved to.\n",
        implode("\n  ", $gone),
    ));
});

it('N2-R14 — every gap is still a gap', function (): void {
    // The half that matters. This rule exists to stop being true: the day
    // lemonfiber carries one of these, the row here is what says so, and it
    // says so by failing rather than by being read.
    $envelopes = everyGeneratedEnvelope();
    $closed = [];

    foreach (WHAT_THE_CONTRACT_DOES_NOT_CARRY as $gap) {
        $named = $gap['envelope'];
        $shape = $gap['shape'];

        // A row watching a whole payload has nothing to search for: what would
        // close its gap is the payload saying anything it does not say now.
        if ($shape !== null) {
            // A shape row names its envelope by construction — the rule below
            // refuses a row that watches neither or both, and an unnamed one
            // would be watching nothing.
            $said = trim(thePayloadShapeOf($named === null ? '' : $envelopes[$named] ?? ''));

            if ($said !== $shape) {
                $closed[] = sprintf(
                    '%s waits on %s saying more than `%s`, and it now says `%s` — %s',
                    $gap['requirement'],
                    $named,
                    $shape,
                    $said,
                    $gap['asks'],
                );
            }

            continue;
        }

        $looking = $named === null ? $envelopes : [$named => $envelopes[$named] ?? ''];
        $field = sprintf('/\b%s\??:/', preg_quote((string) $gap['field'], '/'));

        foreach ($looking as $envelope => $said) {
            if (preg_match($field, thePayloadShapeOf($said)) === 1) {
                $closed[] = sprintf(
                    '%s waits on `%s`, and %s now carries it — %s',
                    $gap['requirement'],
                    $gap['field'],
                    $envelope,
                    $gap['asks'],
                );
            }
        }
    }

    expect($closed)->toBe([], sprintf(
        "The contract now carries what these were waiting for:\n  %s\n\n"
        . 'Answer the requirement and delete its row. A gap that has closed and a register that '
        . "still lists it is how a requirement stays unanswered with a reason attached.\n",
        implode("\n  ", $closed),
    ));
});

it('N2-R14 — every gap says what it asks for and where it was raised', function (): void {
    // A row with an empty reason is a row nobody can act on, and the reason is
    // the only part that survives the person who wrote it. The requirement's
    // own name is not checked here: it is what every message above is written
    // around, so a row without one fails those rules first and by name.
    //
    // A row must also watch exactly one thing. Both would be two rules about
    // one gap that can disagree, and neither would be a row that passes by
    // having nothing to check — the failure this whole file exists to refuse.
    $thin = [];

    foreach (WHAT_THE_CONTRACT_DOES_NOT_CARRY as $gap) {
        foreach (['asks', 'raised'] as $part) {
            if (trim($gap[$part]) === '') {
                $thin[] = sprintf('%s has no %s', $gap['requirement'], $part);
            }
        }
    }

    foreach (WHAT_THE_CONTRACT_DOES_NOT_CARRY as $gap) {
        if (($gap['field'] === null) === ($gap['shape'] === null)) {
            $thin[] = sprintf('%s watches neither a field nor a shape, or both', $gap['requirement']);
        }
    }

    expect($thin)->toBe([], sprintf("These rows do not say enough to act on:\n  %s\n", implode("\n  ", $thin)));
});
