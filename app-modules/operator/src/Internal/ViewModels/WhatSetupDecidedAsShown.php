<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\HowADecisionIsChanged;

/** Every decision setup makes, as the operator is shown them. */
final readonly class WhatSetupDecidedAsShown
{
    /**
     * @param list<ADecisionAsShown> $decisions every decision, in the stack's order
     * @param list<string>           $refused   every name the stack lists as a credential, in capitals
     * @param bool                   $waits     whether a reading waits for a frame of its own, so nothing is offered yet
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $decisions = [],
        public ?AutostartAsShown $autostart = null,
        public ?WhatAProtocolOpenedAsShown $opened = null,
        public array $refused = [],
        public bool $waits = false,
    ) {}

    /** The row a change of this key is drawn under, where the screen offers it there. */
    public function offered(string $key): ?ADecisionAsShown
    {
        $found = null;

        foreach ([...$this->decisions, ...$this->opened->steps ?? []] as $row) {
            $found = $row->key === $key && $row->how === HowADecisionIsChanged::Here ? $row : $found;
        }

        return $found;
    }

    /**
     * Every command the screen names, which are the only words it hands on.
     *
     * @return list<string>
     */
    public function commands(): array
    {
        $commands = $this->autostart instanceof AutostartAsShown ? [$this->autostart->install, $this->autostart->remove] : [];

        foreach ([...$this->decisions, ...$this->opened->steps ?? []] as $row) {
            $commands = $row->command === '' ? $commands : [...$commands, $row->command];
        }

        return $commands;
    }
}
