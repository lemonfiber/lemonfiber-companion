<?php

declare(strict_types=1);

namespace Modules\StoreKit\Api;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;

use function is_int;
use function is_string;

use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\NewestReading;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use stdClass;

/**
 * The newest sealed reading of each stack, in a table its owner names.
 *
 * **The owner's table, never this module's.** A capability's store hands its
 * own table in, created by its own migration with four columns: the stack's
 * keyed hash, the shape the reading was written in, when it was read, and the
 * payload as the owner sealed it. This module owns no table and names none.
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
 * frame, which a screen already draws for a stack it has never read.
 */
final readonly class ATableOfReadings
{
    public function __construct(private ConnectionInterface $database, private string $table) {}

    /** Keep this reading as the newest for this stack, over whatever was kept for it before. */
    public function keep(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $readAt): Noted
    {
        try {
            $this->writeOverTheRowOf($stack, $payload, $shape, $readAt);
        } catch (QueryException) {
            return Noted::notKept();
        }

        return Noted::downAt($readAt);
    }

    /** The newest reading kept for this stack, nothing, or a row this build cannot read. */
    public function newest(SealedStack $stack): NewestReading
    {
        try {
            return $this->asTheNewest($this->theRowOf($stack));
        } catch (QueryException) {
            return NewestReading::none();
        }
    }

    /** Let go of the reading kept for this stack. */
    public function forget(SealedStack $stack): Forgotten
    {
        try {
            return Forgotten::rows($this->deleteTheRowOf($stack));
        } catch (QueryException) {
            return Forgotten::nothing();
        }
    }

    /** Let go of every reading read before this moment, and of none read at it. */
    public function forgetOlderThan(Instant $before): Forgotten
    {
        try {
            return Forgotten::rows($this->deleteWhatWasReadBefore($before));
        } catch (QueryException) {
            return Forgotten::nothing();
        }
    }

    /** Let go of every reading in the table. */
    public function forgetEverything(): Forgotten
    {
        try {
            return Forgotten::rows($this->deleteEveryRow());
        } catch (QueryException) {
            return Forgotten::nothing();
        }
    }

    /** One stack's reading, written over whatever was kept for it before. */
    private function writeOverTheRowOf(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $readAt): void
    {
        $this->database->table($this->table)->upsert(
            [['stack_hash' => $stack->forTheStore(), 'shape' => $shape->value, 'read_at' => $readAt->epochSeconds(), 'payload' => $payload->forTheStore()]],
            ['stack_hash'],
            ['shape', 'read_at', 'payload'],
        );
    }

    /** One stack's row, or nothing where none is kept. */
    private function theRowOf(SealedStack $stack): ?stdClass
    {
        return $this->database->table($this->table)->where('stack_hash', $stack->forTheStore())->first(['shape', 'read_at', 'payload']);
    }

    private function deleteTheRowOf(SealedStack $stack): int
    {
        return $this->database->table($this->table)->where('stack_hash', $stack->forTheStore())->delete();
    }

    private function deleteWhatWasReadBefore(Instant $before): int
    {
        return $this->database->table($this->table)->where('read_at', '<', $before->epochSeconds())->delete();
    }

    private function deleteEveryRow(): int
    {
        return $this->database->table($this->table)->delete();
    }

    /**
     * The one place a row becomes a value.
     *
     * A shape the enum has no case for is a row a later build wrote, and a
     * column holding something this class never writes is a row nobody here
     * wrote. Both are answered as a reading that cannot be read: what to do
     * with one is the owner's to decide.
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
