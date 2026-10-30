<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Which versions a stack runs and what its running release changed, flattened for a template.
 *
 * Every optional line is empty where there is nothing to say, and the template
 * branches on it rather than printing a blank.
 */
final readonly class TheVersionsTurnedOutToBe
{
    /** @param TheVersionsAsRead|null $read what the stack answered, or null where it could not be asked */
    public function __construct(public HowTheReadingWent $went, public ?TheVersionsAsRead $read = null) {}
}
