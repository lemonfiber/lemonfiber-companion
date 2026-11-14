<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What leaves the machine to use each hosted back-end its alerts go to, kept apart from what lemonfiber itself sends. */
final readonly class WhatHostedBackEndsSend
{
    /**
     * @param HowTheReadingWent  $went  whether where alerts go was read, and what stopped it where not
     * @param list<AHostAsShown> $hosts each hosted back-end's host and what is sent to use it
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $hosts,
    ) {}
}
