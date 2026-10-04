<?php

declare(strict_types=1);

namespace Modules\StoreKit\Api;

use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\NewestReading;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;

/**
 * A capability's store of readings, answered from the table its owner names.
 *
 * Every store of readings answers the same five questions over the same
 * sealed bookkeeping, so they are answered once, here, by
 * {@see ATableOfReadings}. A store using this says which table is its own and
 * nothing else: the port it implements is still its owner's, and the class is
 * still the one under that owner's `src/Internal/Store` the composition root
 * binds to it.
 */
trait AnswersFromItsTable
{
    public function keep(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $readAt): Noted
    {
        return $this->table()->keep($stack, $payload, $shape, $readAt);
    }

    public function newest(SealedStack $stack): NewestReading
    {
        return $this->table()->newest($stack);
    }

    public function forget(SealedStack $stack): Forgotten
    {
        return $this->table()->forget($stack);
    }

    public function forgetOlderThan(Instant $before): Forgotten
    {
        return $this->table()->forgetOlderThan($before);
    }

    public function forgetEverything(): Forgotten
    {
        return $this->table()->forgetEverything();
    }

    /** The table this store's owner keeps its readings in. */
    abstract private function table(): ATableOfReadings;
}
