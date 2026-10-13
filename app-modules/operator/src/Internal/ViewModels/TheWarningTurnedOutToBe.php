<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\Remedies;

/** One warning as the stack last raised it, and whether it may be accepted, flattened for a template. */
final readonly class TheWarningTurnedOutToBe
{
    /**
     * @param HowTheReadingWent $went     whether the stack answered, or what stood in the way
     * @param string            $title    what the check is called, in the stack's words, or empty where the report holds no such check
     * @param string            $meaning  what the stack says the choice costs, or empty
     * @param Remedies          $remedies what the stack says may be done instead, in its order
     * @param string            $check    the check a yes accepts, empty where nothing may be accepted
     * @param bool              $accepted whether the warning is already accepted
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $title,
        public string $meaning,
        public Remedies $remedies,
        public string $check,
        public bool $accepted,
    ) {}

    /** Whether the report holds a warning about this check at all. */
    public function isRaised(): bool
    {
        return $this->check !== '' || $this->accepted;
    }
}
