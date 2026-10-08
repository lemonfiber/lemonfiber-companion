<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where the plugins screen has got to, flattened for the template.
 *
 * One model for every state the screen can be in, each saying only its own:
 * what is installed, a source being typed, the stack at work, an install's
 * account, the stack refusing with its reason, or the work ended with no
 * outcome.
 */
final readonly class WhatExtendsItTurnedOutToBe
{
    /**
     * @param HowTheReadingWent          $went       whether the stack was reached, and what stood in the way where it was not
     * @param bool                       $typing     whether a source is being typed, before anything is asked
     * @param bool                       $isWorking  whether the stack is at work on a rehearsal or an install
     * @param bool                       $installing whether the work asked about is the install rather than its rehearsal
     * @param bool                       $hasEnded   whether the stack has no outcome for the work any more
     * @param ARefusalAsShown|null       $refused    the stack's refusal, in its words, where it refused
     * @param list<APluginAsShown>       $installed  every plugin the record holds, where the listing came back
     * @param APluginInstallAsShown|null $install    an install's account, where the answer is about one
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $typing,
        public bool $isWorking,
        public bool $installing,
        public bool $hasEnded,
        public ?ARefusalAsShown $refused,
        public array $installed,
        public ?APluginInstallAsShown $install,
    ) {}
}
