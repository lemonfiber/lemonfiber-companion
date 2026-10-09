<?php

declare(strict_types=1);

namespace Modules\Watching\Internal;

use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\NewestReading;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;

/**
 * The languages a member chose on this phone, one choice per stack, kept between launches.
 *
 * **Declared here, stored in `Internal\Store`**, which the composition root
 * binds to it and nothing else in the module names.
 *
 * **It is handed nothing it could read.** The choice is sealed before it is
 * kept, with whose it is inside the seal, so every method takes a
 * {@see SealedPayload} and a {@see SealedStack} and never a member or a stack.
 *
 * **Kept until it is changed or the stack leaves the phone.** A choice is not
 * a reading and grows no older, so nothing here forgets by age.
 *
 * **Every answer is a value.** A store that cannot be reached keeps nothing and
 * finds nothing, and a title then plays as it comes.
 */
interface LanguagesKept extends ForgetsEverythingKept
{
    /** Keep this choice for this stack, replacing the one before it. */
    public function keep(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $readAt): Noted;

    /** The choice kept for this stack, or that there is none. */
    public function newest(SealedStack $stack): NewestReading;

    /** Let go of the choice kept for this stack. */
    public function forget(SealedStack $stack): Forgotten;
}
