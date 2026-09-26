<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * The catalogue key for what handing something over came to, carried out of an `either()` arm.
 *
 * {@see \Modules\Kernel\Api\Handed::either()} answers with an object, so this
 * is what each arm hands back: the sheet reached, or the reason it was not.
 *
 * `Internal` for {@see WhatTheSharingDid}'s reason.
 */
final readonly class WhatTheSheetSaid
{
    public function __construct(public string $said) {}
}
