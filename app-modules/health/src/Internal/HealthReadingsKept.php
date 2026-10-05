<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\ForgetsOldReadings;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\NewestReading;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;

/**
 * The newest health reading of each stack, kept between launches.
 *
 * **Declared here, stored in `Internal\Store`.** What `health` decides about a
 * kept reading asks this, and the one class that answers it over the app's
 * database lives in this module's store, which the composition root binds to
 * it and nothing else in the module names.
 *
 * **It is handed nothing it could read.** `health` seals a reading before it
 * asks this to keep it, so every method here takes a {@see SealedPayload} and a
 * {@see SealedStack} and never a summary or a stack's identity: a store that
 * cannot be handed a plain value cannot write one to disk.
 *
 * **What a query needs stays readable beside the payload**: the stack's keyed
 * hash to find its row, the {@see Shape} the reading was written in, and when
 * it was read. Nothing else is.
 *
 * **One reading per stack.** Keeping a reading replaces the one kept before it.
 *
 * **Every answer is a value.** A store that cannot be reached keeps nothing,
 * finds nothing and forgets nothing, and says so: a reading that was not kept
 * costs the next opening its first frame, which a screen already draws for a
 * stack it has never heard from.
 */
interface HealthReadingsKept extends ForgetsEverythingKept, ForgetsOldReadings
{
    /** Keep this reading as the newest for this stack, replacing the one before it. */
    public function keep(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $readAt): Noted;

    /** The newest reading kept for this stack, or that there is none. */
    public function newest(SealedStack $stack): NewestReading;

    /** Let go of the reading kept for this stack. */
    public function forget(SealedStack $stack): Forgotten;
}
