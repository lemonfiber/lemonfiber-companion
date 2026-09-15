<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\WhatItSaysUnderneath;

/**
 * One technical detail carried out of an `either()` arm.
 *
 * {@see WhatItSaysUnderneath::either()} answers with an object so that a caller
 * cannot fold its two arms into a string and lose the difference between *the
 * core said nothing* and *the core said nothing useful*.
 */
final readonly class WhatTheCoreAddedUnderneath
{
    public function __construct(public string $said) {}
}
