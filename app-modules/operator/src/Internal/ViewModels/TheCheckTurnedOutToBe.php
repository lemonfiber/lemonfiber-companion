<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One finding the stack leaves unverified because checking it disturbs something, with what running it would disturb, flattened for a template. */
final readonly class TheCheckTurnedOutToBe
{
    /**
     * @param HowTheReadingWent    $went  whether the stack answered, or what stood in the way
     * @param TheCheckAsFound|null $found the finding about this check, or null where the stack did not answer or its report holds no such check
     */
    public function __construct(public HowTheReadingWent $went, public ?TheCheckAsFound $found = null) {}
}
