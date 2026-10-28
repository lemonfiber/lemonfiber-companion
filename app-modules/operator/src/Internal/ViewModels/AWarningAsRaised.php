<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\Remedies;

/** A warning about a choice, as the stack raised it, and whether it may still be accepted. */
final readonly class AWarningAsRaised
{
    /**
     * @param string      $title    what the check is called, in the stack's words
     * @param string      $meaning  what the stack says the choice costs
     * @param Remedies    $remedies what the stack says may be done instead, in its order
     * @param string|null $check    the check a yes accepts, or null where the warning is accepted already
     */
    public function __construct(
        public string $title,
        public string $meaning,
        public Remedies $remedies,
        public ?string $check,
    ) {}
}
