<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Sdk\Api\UpkeepIsUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * What taking an update would change, service by service.
 *
 * Its own reader rather than more of {@see \Modules\Sdk\Api\Standings}, for the
 * reason {@see Endings} is one: that answers what became of the last update and
 * this answers what the next one would do, and the two are kept apart on the
 * screen as well as here.
 *
 * Two questions of one list, and both walk it rather than one handing the other
 * a richer row: what an update would move, and which of that nothing puts back.
 * A screen asks them separately and a reader that answered both at once would
 * be a shape neither caller wanted.
 *
 * **A refused change is left out of both.** The stack has already said it will
 * not make that one, so naming it would have somebody agree to a service that
 * was never going to move — and warn them about undoing an evening it takes no
 * part in.
 *
 * **Every field is required and none is defaulted.** Each has a reassuring
 * direction to guess in: a change nobody could read as refused would become one
 * that happens, and one nobody could read as permanent would become one that
 * can be undone. Both are the answer somebody would stop worrying on.
 */
final readonly class Changes
{
    /**
     * The services taking an update would change.
     *
     * @param array<mixed> $data
     */
    public static function in(array $data): Services
    {
        $services = [];
        $position = 0;

        foreach (self::listed($data) as $said) {
            if (! is_array($said)) {
                throw UpkeepIsUnreadable::change($position);
            }

            if (! self::refused($said, $position)) {
                $services[] = ServiceId::called(self::named($said, $position));
            }

            ++$position;
        }

        return Services::these(...$services);
    }

    /**
     * The services it would change in a way nothing puts back.
     *
     * The walk is written out again rather than shared with the reading above,
     * and that is deliberate twice over. A generator handing rows to both would
     * throw its refusal on iteration rather than when it was called, which is a
     * different moment from the one the adapter catches at; and the register
     * that proves every wire field is read follows a reader to the subscript
     * that takes it, which it cannot do through a yield. A field read behind
     * indirection this cannot follow is a field that reads as unread.
     *
     * @param array<mixed> $data
     */
    public static function permanentIn(array $data): Services
    {
        $services = [];
        $position = 0;

        foreach (self::listed($data) as $said) {
            if (! is_array($said)) {
                throw UpkeepIsUnreadable::change($position);
            }

            if (! self::refused($said, $position) && self::permanent($said, $position)) {
                $services[] = ServiceId::called(self::named($said, $position));
            }

            ++$position;
        }

        return Services::these(...$services);
    }

    /**
     * The changes the payload listed.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function listed(array $data): array
    {
        if (! array_key_exists(WireField::Changes->value, $data)) {
            throw UpkeepIsUnreadable::missing(WireField::Changes);
        }

        $listed = $data[WireField::Changes->value];

        if (! is_array($listed)) {
            throw UpkeepIsUnreadable::missing(WireField::Changes);
        }

        return $listed;
    }

    /**
     * Whether the stack has already said it will not make this change.
     *
     * @param array<mixed> $said
     */
    private static function refused(array $said, int $position): bool
    {
        return self::yesOrNo($said, WireField::Refused, $position);
    }

    /**
     * Whether this change cannot be put back.
     *
     * @param array<mixed> $said
     */
    private static function permanent(array $said, int $position): bool
    {
        return self::yesOrNo($said, WireField::Irreversible, $position);
    }

    /**
     * One of the change's two yes-or-no fields, refused where it is neither.
     *
     * @param array<mixed> $said
     */
    private static function yesOrNo(array $said, WireField $field, int $position): bool
    {
        if (! array_key_exists($field->value, $said)) {
            throw UpkeepIsUnreadable::change($position);
        }

        $answer = $said[$field->value];

        if (! is_bool($answer)) {
            throw UpkeepIsUnreadable::change($position);
        }

        return $answer;
    }

    /**
     * Which service one change is about.
     *
     * @param array<mixed> $said
     */
    private static function named(array $said, int $position): string
    {
        if (! array_key_exists(WireField::Service->value, $said)) {
            throw UpkeepIsUnreadable::change($position);
        }

        $service = $said[WireField::Service->value];

        if (! is_string($service)) {
            throw UpkeepIsUnreadable::change($position);
        }

        return $service;
    }
}
