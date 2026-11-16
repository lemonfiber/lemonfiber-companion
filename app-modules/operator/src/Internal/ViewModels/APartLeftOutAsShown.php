<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * A part the plain Compose words do not bring up as lemonfiber does, as the stack's settings draw it.
 */
final readonly class APartLeftOutAsShown
{
    /**
     * @param string $part   the part, by its id
     * @param string $reason why, in the operator's words
     */
    public function __construct(public string $part, public string $reason) {}
}
