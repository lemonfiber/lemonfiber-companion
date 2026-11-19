<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What setup settled on a stack, as its settings and its own screen draw it.
 */
final readonly class WhatSetupSettledAsShown
{
    /**
     * @param HowTheReadingWent $went           whether the stack answered, and what stopped it where not
     * @param bool              $finished       whether setup is finished
     * @param string            $dataRoot       where the library is kept, empty where no place is chosen
     * @param string            $downloadingSaid the key for the ways of downloading it is set up for
     * @param string            $runsAs         the user and group the services run as, `uid:gid`
     * @param string            $runsAsFromSaid the key for where those two come from
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $finished,
        public string $dataRoot,
        public string $downloadingSaid,
        public string $runsAs,
        public string $runsAsFromSaid,
    ) {}
}
