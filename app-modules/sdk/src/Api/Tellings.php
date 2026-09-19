<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function count;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HouseholdEnvelope;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Sdk\Internal\Wire;

/**
 * The `household` envelope, as what it tells one member.
 *
 * The sibling of {@see Households} over the same payload, and the difference is
 * the subject rather than the fields. That one flattens the house into every
 * request anybody made, which is what an operator deciding on six requests
 * needs; this one keeps the member, because what a member is owed is a reading
 * *about them* and the flattening is exactly where it is lost.
 *
 * **It reads the answer it was given rather than picking out of it.** A session
 * belonging to a member is answered with that member's row, because the core
 * narrowed it — so the one row in the answer is the reading, and nothing here
 * chooses whose it is. A reader that searched a household for the right person
 * would be deciding who is looking, which is the answer the core exists to
 * give.
 *
 * **Sentences and never parts.** The wire carries a policy, a standing, two
 * counts and an instant beside these, and none of them is read here. What this
 * hands back is what the core wrote to the member, so nothing downstream has to
 * invent a wording for *within a limit* — and no surface ends up holding a
 * second copy of a household's rules.
 *
 * A static fold with no state, for {@see Households}' reason and one more: the
 * reading follows a static call from the envelope it was seated on, so a fold
 * written as instance methods is a fold the register of unread fields cannot
 * see through.
 */
final readonly class Tellings
{
    /**
     * The one member's sentences, or none where the answer is not one member's.
     *
     * @param Envelope<mixed> $envelope the `household` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): Sentences
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw HouseholdIsUnreadable::missing(WireField::Members);
        }

        $said = [];
        $position = 0;

        foreach (self::members($data) as $member) {
            if (! is_array($member)) {
                throw HouseholdIsUnreadable::member($position);
            }

            $said[] = self::owedIn($member);
            $position++;
        }

        // Exactly one row is one member's reading. Anything else is an answer
        // about a house rather than about a person — the operator's read of the
        // same endpoint — and there is nobody in it to be owed anything.
        //
        // Read before it is counted, deliberately. A house whose fourth member
        // carries something this app cannot show is a stack this app cannot
        // read, and discarding the rows before looking at them would let that
        // through as an ordinary answer about somebody else.
        return count($said) === 1 ? $said[0] : Sentences::none();
    }

    /**
     * The payload, as whatever actually arrived.
     *
     * Answered as `mixed` rather than as the shape the generated type declares,
     * for the reason {@see Problems} gives: the generated envelope asserts its
     * payload without checking it, which is right for generated code and leaves
     * this side reading whatever came off a socket. Taking the declared shape
     * here would make every guard below unreachable to the analyser and absent
     * from the build.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return HouseholdEnvelope::in($envelope)->data;
    }

    /**
     * Every member row in the answer, in the order the stack listed them.
     *
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private static function members(array $data): array
    {
        if (! array_key_exists(WireField::Members->value, $data)) {
            throw HouseholdIsUnreadable::missing(WireField::Members);
        }

        $members = $data[WireField::Members->value];

        if (! is_array($members)) {
            throw HouseholdIsUnreadable::missing(WireField::Members);
        }

        return $members;
    }

    /**
     * What one member row says they are owed.
     *
     * A row missing the field is refused rather than read as nothing owed: the
     * contract requires it of every member, so its absence is a stack this app
     * cannot read rather than a member with nothing waiting — and the two are
     * opposite sentences on a screen.
     *
     * A sentence that is not text is refused for the same reason. Dropping it
     * would leave a member told part of what they are owed, with no sign that
     * the rest was there.
     *
     * @param array<mixed> $member
     */
    private static function owedIn(array $member): Sentences
    {
        if (! array_key_exists(WireField::ToHandOver->value, $member)) {
            throw HouseholdIsUnreadable::missing(WireField::ToHandOver);
        }

        $said = $member[WireField::ToHandOver->value];

        if (! is_array($said)) {
            throw HouseholdIsUnreadable::missing(WireField::ToHandOver);
        }

        $sentences = [];

        foreach ($said as $sentence) {
            if (! is_string($sentence)) {
                throw HouseholdIsUnreadable::missing(WireField::ToHandOver);
            }

            $sentences[] = Sentence::of($sentence);
        }

        return Sentences::of(...$sentences);
    }
}
