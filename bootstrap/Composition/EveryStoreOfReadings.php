<?php

declare(strict_types=1);

namespace Bootstrap\Composition;

use Modules\Kernel\Api\ForgetsOldReadings;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;

/**
 * Every store of readings, asked to let go of the old as one.
 *
 * The stores are the ones the composition root registers under one tag, so a
 * kind of reading kept tomorrow is let go of for its age with the rest the
 * moment its store is registered, and nothing that decides how long readings
 * are kept has to know how many kinds there are.
 */
final readonly class EveryStoreOfReadings implements ForgetsOldReadings
{
    /** @var array<ForgetsOldReadings> */
    private array $stores;

    public function __construct(ForgetsOldReadings ...$stores)
    {
        $this->stores = $stores;
    }

    public function forgetOlderThan(Instant $before): Forgotten
    {
        $forgotten = Forgotten::nothing();

        foreach ($this->stores as $store) {
            $forgotten = $forgotten->beside($store->forgetOlderThan($before));
        }

        return $forgotten;
    }
}
