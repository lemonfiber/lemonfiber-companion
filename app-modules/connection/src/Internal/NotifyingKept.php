<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;

/**
 * Where what the operator lets reach the phone from each stack is kept.
 *
 * One row a stack, sealed before it arrives here, so the store holds a keyed
 * hash and a payload nothing here can open. It is cleared with every other store
 * where the seal's key is made afresh.
 */
interface NotifyingKept extends ForgetsEverythingKept
{
    /** Keep this for the stack, in place of what was kept before. */
    public function keep(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $notedAt): Noted;

    /** What is kept for the stack, or none where nothing is or this build cannot read it. */
    public function found(SealedStack $stack): KeptSettings;

    /** Forget what is kept for the stack. */
    public function forget(SealedStack $stack): Forgotten;
}
