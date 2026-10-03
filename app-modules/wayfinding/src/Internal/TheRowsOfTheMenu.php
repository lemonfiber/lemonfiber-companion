<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Internal;

use Modules\Wayfinding\Api\WhoTheMenuIsFor;

/**
 * Which rows the menu draws, between the way to another stack and the two settings.
 *
 * Until a session says whose it is, nothing is added: the menu offers only
 * what is true for anyone, which is the list of stacks, what this phone keeps
 * of the stack, and the phone's settings. A member's adds what the stack owes
 * them. Only the operator's adds what is new and the stack's own screens,
 * because a member is never shown the machine's lifecycle, logs, credentials
 * or diagnostics.
 */
final readonly class TheRowsOfTheMenu
{
    /**
     * @param bool                             $whatIsNew whether what is new is drawn
     * @param list<WhatAMemberFindsInTheMenu>  $owed      what the stack owes the person holding the session
     * @param list<WhereInTheMenu>             $groups    the stack's own screens, in their groups
     */
    private function __construct(
        public bool $whatIsNew,
        public array $owed,
        public array $groups,
    ) {}

    public static function for(WhoTheMenuIsFor $for): self
    {
        return match ($for) {
            WhoTheMenuIsFor::Anyone => new self(whatIsNew: false, owed: [], groups: []),
            WhoTheMenuIsFor::AMember => new self(whatIsNew: false, owed: WhatAMemberFindsInTheMenu::cases(), groups: []),
            WhoTheMenuIsFor::TheOperator => new self(whatIsNew: true, owed: [], groups: WhereInTheMenu::cases()),
        };
    }
}
