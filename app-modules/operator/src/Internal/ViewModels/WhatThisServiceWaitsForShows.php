<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What a service the stack holds back waits for, flattened for a template. */
final readonly class WhatThisServiceWaitsForShows
{
    /**
     * @param HowTheReadingWent           $went     whether the settings came back, or what stood in the way
     * @param AServiceWaitingAsShown|null $waiting  the service and everything it waits for, or null where it is not held back
     * @param list<AStackSettingAsShown>  $settings each setting it waits for, then each moved alongside one of them, to change from here
     */
    public function __construct(public HowTheReadingWent $went, public ?AServiceWaitingAsShown $waiting = null, public array $settings = []) {}
}
