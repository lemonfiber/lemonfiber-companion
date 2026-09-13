<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

/**
 * How far a pairing code the operator is typing has got.
 *
 * Four states rather than "valid or not", because the screen owes a different
 * sentence for each and three of them are not errors at all. A field somebody
 * has not finished filling in is not a mistake; a code that expired is a code
 * that was typed perfectly and is no longer any use; a code that does not parse
 * is the transcription error {@see \Modules\Kernel\Api\HowItWasRead} exists to
 * tell apart from a bad camera angle. Collapsing them gives an operator
 * "invalid code" three times for three different situations, twice while they
 * are still typing.
 *
 * An enum rather than a set of booleans on the view model, which `D4` asks for
 * and which pays here in particular: the four are exclusive, and a pair of
 * booleans is a shape in which *unreadable and expired* is a state somebody can
 * construct.
 */
enum WhereTheCodeGot: string
{
    /**
     * Nothing has been typed yet, or not enough to judge.
     *
     * The state a screen opens in. It is deliberately not `Unreadable`: an
     * empty field *is* unreadable in the literal sense, and telling somebody
     * their code is wrong before they have typed one is the first thing this
     * enum exists to prevent.
     */
    case Waiting = 'waiting';

    /** It is not pairing material, or not material this app can read. */
    case Unreadable = 'unreadable';

    /**
     * It parsed, and it is past its moment (`N1-R49`).
     *
     * Told apart from `Unreadable` because the remedy is the opposite one.
     * Checking the characters is wasted effort on a code that was typed
     * correctly, and the only way forward is a new code from the stack.
     */
    case Expired = 'expired';

    /**
     * It parsed, it is still good, and the operator has a fingerprint to check.
     *
     * `N1-R50`'s step. Reaching this state is not pairing — it is the app
     * having something to show, and the operator still has to say it matches.
     */
    case Comparing = 'comparing';

    /**
     * What is said under the field, as a key the template resolves.
     *
     * A key rather than a sentence, because `L1` puts the words in the
     * catalogue and `A4` keeps the translator out of a class that did not ask
     * for one — the template is where `__()` is called, and the template is
     * also the half `tests/Templates` can read.
     *
     * On the enum rather than on the screen for the reason `D4` gives about
     * closed sets: there are four states and there are four sentences, and a
     * `match` with no default arm is what makes a fifth state a failure here
     * rather than a field with nothing under it.
     *
     * The pairing worth reading twice is `Unreadable` against `Expired`. An
     * expired code was typed perfectly, and sending its operator to check the
     * characters sends them looking for a mistake that is not there (`N1-R49`).
     */
    public function saidUnderTheField(): string
    {
        return match ($this) {
            self::Waiting => 'connection.type_the_code_hint',
            self::Unreadable => 'connection.typed_code_is_unreadable_action',
            self::Expired => 'connection.pairing_expired_action',
            self::Comparing => 'connection.code_reads_as_a_stack',
        };
    }
}
