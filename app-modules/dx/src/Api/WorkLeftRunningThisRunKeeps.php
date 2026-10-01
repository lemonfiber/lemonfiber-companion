<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Modules\Dx\Adapters\TheStoreThisRunKeeps;
use Modules\Kernel\Api\WorkLeftRunning;
use Modules\Vault\Api\PlatformStacks;
use Modules\Vault\Api\PlatformWorkLeftRunning;

/**
 * The handle of work left running on each stack, kept for one run.
 *
 * The fourth port sitting on the device's store, here for the reason
 * {@see StandingsThisRunKeeps} is: with stand-ins on, what gets written under it
 * is the handle of a walk on a machine that does not exist, filed under a stack
 * id nothing else will ever use. Left to the real keychain that is a row an
 * operator's device carries for good.
 *
 * The adapter is the shipped one, so the key it files a handle under and the
 * shape it writes are the real ones; only the store underneath is replaced.
 *
 * @implements StandsIn<WorkLeftRunning>
 */
final readonly class WorkLeftRunningThisRunKeeps implements StandsIn
{
    public function __construct(private TheStoreThisRunKeeps $store) {}

    public function insteadOf(): string
    {
        return WorkLeftRunning::class;
    }

    public function which(): WorkLeftRunning
    {
        return new PlatformWorkLeftRunning($this->store, new PlatformStacks($this->store));
    }
}
