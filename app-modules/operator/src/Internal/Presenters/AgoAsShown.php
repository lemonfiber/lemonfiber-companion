<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;

/**
 * How long ago a reading was taken, carried out of a fold as the key and the
 * count a template needs. A live reading has neither: an empty key and nought.
 * An undated one has the key saying the clock would not tell, and nought.
 */
final readonly class AgoAsShown
{
    private function __construct(public string $said, public int $count) {}

    /** No age: a reading taken now, or a moment nobody said or said in a form no clock reads. */
    public static function saysNothing(): self
    {
        return new self('', 0);
    }

    /** A change the stack's clock would not date, which says so rather than an age. */
    public static function undated(): self
    {
        return new self(HowTheRecordReads::CLOCK_UNREADABLE, 0);
    }

    /** How far ahead a moment is, counted from the frame's moment. */
    public static function until(Instant $then, Instant $now): self
    {
        $unit = HowLongAgo::since($now, $then);

        return new self($unit->comingOnTheScreen(), $unit->howManySince($now, $then));
    }

    /** How long before the frame's moment `$then` was. */
    public static function since(Instant $then, Instant $now): self
    {
        $ago = HowLongAgo::since($then, $now);

        return new self($ago->saidOnTheScreen(), $ago->howManySince($then, $now));
    }

    /** How long before the frame's moment a written moment was, or nothing where it names none. */
    public static function sinceWritten(AMomentAsWritten $moment, Instant $now): self
    {
        return $moment->read(
            read: static fn(Instant $then): self => self::since($then, $now),
            unreadable: self::saysNothing(...),
        );
    }
}
