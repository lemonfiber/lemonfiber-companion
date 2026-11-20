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
 * The way that last let a member in on each stack, kept sealed, one row a stack.
 */
interface WaysInKept extends ForgetsEverythingKept
{
    /** Keep this for the stack, in place of what was kept before. */
    public function keep(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $notedAt): Noted;

    /** What is kept for the stack, if anything. */
    public function found(SealedStack $stack): KeptSettings;

    /** Forget what is kept for the stack. */
    public function forget(SealedStack $stack): Forgotten;
}
