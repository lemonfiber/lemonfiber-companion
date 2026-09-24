<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Modules\Kernel\Api\HowARequestStands;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Sdk\Api\Fields\HouseholdField;
use Modules\Sdk\Api\HouseholdIsUnreadable;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * One row of a member's request list, read into what a screen can show.
 *
 * Lifted out of {@see \Modules\Sdk\Api\Households} rather than written
 * there. That reader answers *what did this household say*; these six methods
 * answer *what is this one request*, and they were the half of the class that
 * grew every time the contract said something more about a row. The gate that
 * noticed is the cognitive-complexity bound, and it was right: a class holding
 * two subjects gets harder to read at the rate the busier one changes.
 *
 * Every refusal here names the member and the row's position, which is what
 * makes a refusal answerable — *a request could not be read* is a sentence
 * nobody can act on, and the same sentence with a name and a place is one
 * somebody can go and look at.
 */
final readonly class WhatWasAskedFor
{
    /**
     * One request, refused or not, which are two different values.
     *
     * The branch is on the standing rather than on whether a `refused` key
     * happens to be there, because the two are tied together: a decline
     * *is* a reason, so a row saying `declined` and carrying none is a stack
     * that broke the rule and is refused here rather than shown as a word an
     * operator cannot explain to the person who asked.
     *
     * The other direction is left alone on purpose. A row that is not declined
     * and carries a refusal anyway is a stack that changed its mind, and the
     * standing is the fact this app renders — showing somebody why a thing they
     * are still waiting for was refused would be worse than dropping it.
     *
     * @param array<mixed> $row
     */
    public static function request(array $row, string $by, int $position): Wanted
    {
        $standing = self::standing($row, $by, $position);
        $number = self::number($row, $by, $position);
        $title = self::title($row, $by, $position);

        if (! $standing->wasDeclined()) {
            return Wanted::of($number, $by, $title, self::size($row), $standing);
        }

        return Wanted::turnedDown(
            $number,
            $by,
            $title,
            self::size($row),
            self::whyItWasRefused($row, $by, $position),
        );
    }
    /**
     * One field of a request, whatever it holds, or `null` where it is absent.
     *
     * `C9` refuses `??` on a subscript because it reads as a default when it is
     * really an admission that nobody knows whether the key is there. Here
     * nobody does — the contract marks three of these optional — so the absence
     * is answered once, in one place, and every caller below decides for itself
     * what an absent field means. Two of them refuse and one of them answers
     * {@see Size::unknown()}, which is the distinction a shared `??` would have
     * flattened.
     *
     * @param array<mixed> $row
     */
    private static function under(array $row, NamesAWireField $field): mixed
    {
        // Written as a guard rather than as a ternary because the two gates
        // disagree about the ternary: rector rewrites
        // `array_key_exists(...) ? $row[...] : null` to `??`, and `C9` refuses
        // `??` on a subscript. Both are right about what they see — the point
        // of `C9` is that a coalesce reads as a default when it is really an
        // admission, and the point of this method is to make that admission
        // once, in the open, where the callers can each answer it differently.
        if (! array_key_exists($field->value, $row)) {
            return null;
        }

        return $row[$field->value];
    }

    /** @param array<mixed> $row */
    private static function number(array $row, string $by, int $position): int
    {
        $said = self::under($row, WireField::Id);

        if (! is_int($said)) {
            throw HouseholdIsUnreadable::request($by, $position);
        }

        return $said;
    }

    /** @param array<mixed> $row */
    private static function title(array $row, string $by, int $position): string
    {
        $said = self::under($row, WireField::Title);

        // The contract permits this to be absent, and a row with no title is a
        // row nobody can decide on — so it is refused here rather than shown
        // blank or quietly left out. `Wanted` would refuse it a line later; this
        // refuses it with the member and the position still in hand.
        if (! is_string($said)) {
            throw HouseholdIsUnreadable::request($by, $position);
        }

        return $said;
    }

    /**
     * What they were told, and when, where the stack said when.
     *
     * A declined row with no readable reason is refused rather than given one,
     * which is the rule exactly: the app must not substitute a value the
     * contract did not carry, and the substitute available here — *no reason
     * given* — is a sentence this application would have written on a stack's
     * behalf and shown to the person who asked.
     *
     * @param array<mixed> $row
     */
    private static function whyItWasRefused(array $row, string $by, int $position): TurnedDown
    {
        $refused = self::under($row, WireField::Refused);

        if (! is_array($refused)) {
            throw HouseholdIsUnreadable::refusal($by, $position);
        }

        // Through `under()` rather than a coalesce on the subscript: `C9` refuses
        // the shape, and substituting what it usually means is refused, which is this
        // app filling a gap the contract left.
        $reason = self::under($refused, WireField::Reason);

        if (! is_string($reason) || trim($reason) === '') {
            throw HouseholdIsUnreadable::refusal($by, $position);
        }

        $at = self::under($refused, WireField::At);

        // A refusal with no moment is ordinary — the stack records one where it
        // has one — so this is the one absence here with an answer rather than
        // a refusal, which is `Size::unknown()`'s argument on the row above.
        if (! is_string($at) || trim($at) === '') {
            return TurnedDown::because($reason);
        }

        return TurnedDown::at($at, $reason);
    }

    /**
     * Where the request stands, or that the stack did not put it into words.
     *
     * **Absent is an answer here**, and it is the one absence on this row that
     * had been a refusal. The contract leaves `state` out where the request
     * service reported a status lemonfiber has no word for, rather than
     * guessing it into the nearest one — so a household with a service this
     * build does not fully speak sends this, and it is ordinary.
     *
     * Refusing it cost the whole reading: one request nobody had a word for
     * made every member, every other request, and every decision waiting on
     * them unreadable. A screen that shows none of what is waiting, because of
     * one row it could not label, has served the operator worse than one that
     * labels that row *unnamed* and shows the rest.
     *
     * A standing that arrives **spelled out** and unrecognised stays refused,
     * and the two are not the same. The contract's union and {@see Waiting} are
     * generated from one source, so a word that reaches here and is not a case
     * means they have drifted — a fault about this app rather than a fact about
     * the household.
     *
     * @param array<mixed> $row
     */
    private static function standing(array $row, string $by, int $position): HowARequestStands
    {
        $said = self::under($row, WireField::State);

        if ($said === null) {
            return HowARequestStands::unnamed();
        }

        if (! is_string($said)) {
            throw HouseholdIsUnreadable::request($by, $position);
        }

        return HowARequestStands::said(
            Waiting::tryFrom($said) ?? throw HouseholdIsUnreadable::standing($said),
        );
    }

    /**
     * How big it is thought to be, or that nobody has sized it.
     *
     * An absent estimate is {@see Size::unknown()} rather than a refusal, which
     * is the one optional field here that has a real answer: a request nothing
     * has sized yet is an ordinary state of a queue, and what is wanted is *we do
     * not know* shown rather than guessed at.
     *
     * @param array<mixed> $row
     */
    private static function size(array $row): Size
    {
        $estimate = self::under($row, HouseholdField::Estimate);

        if (! is_array($estimate)) {
            return Size::unknown();
        }

        $bytes = self::under($estimate, WireField::Bytes);
        $measured = self::under($estimate, HouseholdField::Measured);

        if (! is_int($bytes) || ! is_bool($measured)) {
            return Size::unknown();
        }

        return $measured ? Size::measured($bytes) : Size::guessedAt($bytes);
    }
}
