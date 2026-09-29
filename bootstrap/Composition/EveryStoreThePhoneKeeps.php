<?php

declare(strict_types=1);

namespace Bootstrap\Composition;

use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\Forgotten;

/**
 * Every store of what the phone keeps, asked to forget as one.
 *
 * The stores are the ones the composition root registers under one tag, so a
 * store added tomorrow is cleared with the rest the moment it is registered,
 * and nothing that clears everything has to know how many there are.
 */
final readonly class EveryStoreThePhoneKeeps implements ForgetsEverythingKept
{
    /** @var array<ForgetsEverythingKept> */
    private array $stores;

    public function __construct(ForgetsEverythingKept ...$stores)
    {
        $this->stores = $stores;
    }

    public function forgetEverything(): Forgotten
    {
        $forgotten = Forgotten::nothing();

        foreach ($this->stores as $store) {
            $forgotten = $forgotten->beside($store->forgetEverything());
        }

        return $forgotten;
    }
}
