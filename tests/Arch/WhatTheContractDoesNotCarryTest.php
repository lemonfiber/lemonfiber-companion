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

/** Every requirement this app is holding, and the field it is waiting for. */
const WHAT_THE_CONTRACT_DOES_NOT_CARRY = [
    [
        'requirement' => 'N2-R8',
        'asks' => 'the bound on what a start, stop or restart disturbs, or that the stack reported none',
        // Named, because the `lifecycle` envelope is where a bound would
        // arrive: it already carries what an operation touched — `plan`,
        // `switched`, `services` — and has no field for how long for.
        'envelope' => 'LifecycleEnvelope',
        'field' => 'bound',
        'raised' => 'B2-R16 already requires the stack to state it before it acts, and `disturbing_for()` '
            . 'says it for a doctor check. No lifecycle payload carries it, so the app states what a '
            . 'verb disturbs and cannot state how long for.',
    ],
    [
        'requirement' => 'N3-R4',
        'asks' => 'what a provider has left, before somebody in the house is told to ask for something',
        // No envelope named: nothing in the contract carries an allowance at
        // all, so there is no type this would be added to rather than a type it
        // is missing from.
        'envelope' => null,
        'field' => 'allowance',
        'raised' => 'C8 makes a provider out of allowance one of the things worth carrying in a pocket, '
            . 'and the wire says nothing about one. The app shows what a provider reported and cannot '
            . 'say what is left of it.',
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
        $looking = $named === null ? $envelopes : [$named => $envelopes[$named] ?? ''];
        $field = sprintf('/\b%s\??:/', preg_quote($gap['field'], '/'));

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
    $thin = [];

    foreach (WHAT_THE_CONTRACT_DOES_NOT_CARRY as $gap) {
        foreach (['asks', 'field', 'raised'] as $part) {
            if (trim($gap[$part]) === '') {
                $thin[] = sprintf('%s has no %s', $gap['requirement'], $part);
            }
        }
    }

    expect($thin)->toBe([], sprintf("These rows do not say enough to act on:\n  %s\n", implode("\n  ", $thin)));
});
