<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Whom a change to what may be asked for can be about: the whole household, and each member the stack listed. */
final readonly class WhoToChooseAsShown
{
    /**
     * @param list<string> $members     each member's name as their account is held, in the stack's order
     * @param bool         $membersRead whether the members could be read; where not, only the whole household is offered
     */
    public function __construct(
        public array $members,
        public bool $membersRead,
    ) {}
}
