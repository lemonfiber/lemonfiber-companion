<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Everybody the media server holds an account for, flattened for the template.
 *
 * Empty with `went` come back is a household nobody has been asked into; a
 * household that could not be read says so through `went` instead.
 */
final readonly class WhoIsInTurnedOutToBe
{
    /**
     * @param HowTheReadingWent     $went    whether it came back, and what stood in the way where it did not
     * @param list<AMemberAsShown> $members everybody, in the stack's order
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $members,
    ) {}
}
