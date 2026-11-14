<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One stack's section of the notification page, flattened.
 *
 * Where the stack was read, a switch for each kind it raises and, where it
 * raises problems, the floor. Where it was not, what stood in the way, and
 * what the operator chose that still applies, with no switch: a kind offered
 * then may be one the stack no longer raises.
 */
final readonly class WhatAStackMayTellAsShown
{
    /**
     * @param string                           $stack         the stack, as a choice on it hands it back
     * @param string                           $name          what the stack is called
     * @param HowTheReadingWent                $went          whether its kinds were read, and what stopped it where not
     * @param list<string>                     $raised        each kind it raises, as the wire names it, held so a choice redraws without asking again
     * @param list<AKindOfNotificationAsShown> $kinds         a switch for each kind it raises
     * @param ASettingAsShown|null             $floor         the lowest severity of problem told, where it raises problems
     * @param bool                             $problemsAreOn whether problems may reach the phone, without which the floor is drawn and not usable
     * @param list<string>                     $keptOff       the key for each kind switched off, said where the stack could not be read
     * @param string                           $floorSaid     the key for the floor in force, said where the stack could not be read
     */
    public function __construct(
        public string $stack,
        public string $name,
        public HowTheReadingWent $went,
        public array $raised,
        public array $kinds,
        public ?ASettingAsShown $floor,
        public bool $problemsAreOn,
        public array $keptOff,
        public string $floorSaid,
    ) {}
}
