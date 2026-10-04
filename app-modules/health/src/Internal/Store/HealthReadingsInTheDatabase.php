<?php

declare(strict_types=1);

namespace Modules\Health\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;

use function is_int;
use function is_string;

use Modules\Health\Internal\HealthReadingsKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\NewestReading;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use stdClass;

/**
 * {@see HealthReadingsKept}, answered by the app's own database.
 *
 * **One table, `health_readings`, and this is the one class that names it.**
 * It is created by this module's own migration, and no other module reads it:
 * one that wants a stack's health asks `health`.
 *
 * **One private method per query, over the query builder**, each named for
 * what it asks, and a row becomes a value in one place, {@see asTheNewest()}.
 * There is no model and no hand-written SQL.
 *
 * **It is handed nothing it could read.** A reading arrives sealed and a stack
 * as its keyed hash, and both are written as they came.
 *
 * **A database that will not answer keeps nothing and finds nothing**, and
 * says so as a value: a query refused here costs the next opening its first
 * frame, which a screen already draws for a stack it has never heard from.
 */
final readonly class HealthReadingsInTheDatabase implements HealthReadingsKept
{
    /** The table this module owns. */
    private const string TABLE = 'health_readings';

    public function __construct(private ConnectionInterface $database) {}

    public function keep(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $readAt): Noted
    {
        try {
            $this->replaceTheReadingOf($stack, $payload, $shape, $readAt);
        } catch (QueryException) {
            return Noted::notKept();
        }

        return Noted::downAt($readAt);
    }

    public function newest(SealedStack $stack): NewestReading
    {
        try {
            return $this->asTheNewest($this->theRowOf($stack));
        } catch (QueryException) {
            return NewestReading::none();
        }
    }

    public function forget(SealedStack $stack): Forgotten
    {
        try {
            return Forgotten::rows($this->deleteTheRowOf($stack));
        } catch (QueryException) {
            return Forgotten::nothing();
        }
    }

    public function forgetOlderThan(Instant $before): Forgotten
    {
        try {
            return Forgotten::rows($this->deleteTheRowsReadBefore($before));
        } catch (QueryException) {
            return Forgotten::nothing();
        }
    }

    public function forgetEverything(): Forgotten
    {
        try {
            return Forgotten::rows($this->deleteEveryRow());
        } catch (QueryException) {
            return Forgotten::nothing();
        }
    }

    /** One stack's reading, written over the one kept before it. */
    private function replaceTheReadingOf(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $readAt): void
    {
        $this->database->table(self::TABLE)->upsert(
            [[
                'stack_hash' => $stack->forTheStore(),
                'shape' => $shape->value,
                'read_at' => $readAt->epochSeconds(),
                'payload' => $payload->forTheStore(),
            ]],
            ['stack_hash'],
            ['shape', 'read_at', 'payload'],
        );
    }

    /** One stack's row, or nothing where none is kept. */
    private function theRowOf(SealedStack $stack): ?stdClass
    {
        return $this->database->table(self::TABLE)
            ->where('stack_hash', $stack->forTheStore())
            ->first(['shape', 'read_at', 'payload']);
    }

    private function deleteTheRowOf(SealedStack $stack): int
    {
        return $this->database->table(self::TABLE)
            ->where('stack_hash', $stack->forTheStore())
            ->delete();
    }

    private function deleteTheRowsReadBefore(Instant $before): int
    {
        return $this->database->table(self::TABLE)
            ->where('read_at', '<', $before->epochSeconds())
            ->delete();
    }

    private function deleteEveryRow(): int
    {
        return $this->database->table(self::TABLE)->delete();
    }

    /**
     * The one place a row becomes a value.
     *
     * A shape the enum has no case for is a row a later build wrote, and a
     * column holding something this class never writes is a row nobody here
     * wrote. Both are answered as a reading that cannot be read rather than
     * read: what to do with one is `health`'s to decide.
     */
    private function asTheNewest(?stdClass $row): NewestReading
    {
        if (! $row instanceof stdClass) {
            return NewestReading::none();
        }

        $shape = is_int($row->shape) ? Shape::tryFrom($row->shape) : null;

        if (! $shape instanceof Shape || ! is_int($row->read_at) || ! is_string($row->payload)) {
            return NewestReading::thatThisBuildCannotRead();
        }

        return NewestReading::found(SealedPayload::of($row->payload), $shape, Instant::atEpochSeconds($row->read_at));
    }
}
