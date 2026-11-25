<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedStack;

/**
 * Which stacks may be missing alerts from what a wake heard, one mark a stack, named by its keyed hash.
 *
 * A mark holds nothing but the moment it was made, and a stack marked again
 * keeps one mark.
 */
interface MissingAlertsKept extends ForgetsEverythingKept
{
    /** Mark this stack, answering when the mark was made, or that it was not kept. */
    public function mark(SealedStack $stack, Instant $notedAt): Noted;

    /** When this stack was marked, or that no mark is kept for it, as where the store cannot be reached. */
    public function whenMarked(SealedStack $stack): Noted;

    /** Forget this stack's mark, answering how many there were. */
    public function forget(SealedStack $stack): Forgotten;
}
