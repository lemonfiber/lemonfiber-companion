<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One warning as the stack last raised it, and whether it may be accepted, flattened for a template. */
final readonly class TheWarningTurnedOutToBe
{
    /**
     * @param HowTheReadingWent     $went   whether the stack answered, or what stood in the way
     * @param AWarningAsRaised|null $raised the warning about the check, or null where the report raises none about a choice
     */
    public function __construct(public HowTheReadingWent $went, public ?AWarningAsRaised $raised = null) {}
}
