<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where the plugins screen has got to, flattened for the template.
 *
 * One model for every state the screen can be in, each saying only its own:
 * what is installed, a source being typed, the stack at work, an install's,
 * an update's or a removal's account, the stack refusing with its reason and
 * any service left unapproved to take its shape, or the work ended with no
 * outcome.
 */
final readonly class WhatExtendsItTurnedOutToBe
{
    /**
     * @param HowTheReadingWent          $went        whether the stack was reached, and what stood in the way where it was not
     * @param bool                       $typing      whether a source is being typed, before anything is asked
     * @param bool                       $isWorking   whether the stack is at work on a rehearsal or an act
     * @param bool                       $afterTheYes whether the work asked about is the act agreed to rather than its rehearsal
     * @param string                     $workingSaid the catalogue key for what the stack is at, or empty
     * @param bool                       $hasEnded    whether the stack has no outcome for the work any more
     * @param string                     $refusedSaid the catalogue key for what the stack would not do, or empty
     * @param ARefusalAsShown|null       $refused     the stack's refusal, in its words, where it refused
     * @param list<AShapeTakenAsShown>   $unapproved  every service left unapproved to take its privileged shape, where the act agreed to was refused
     * @param list<APluginAsShown>       $installed   every plugin the record holds, where the listing came back
     * @param APluginInstallAsShown|null $install     an install's account, where the answer is about one
     * @param APluginUpdateAsShown|null  $update      an update's account, where the answer is about one
     * @param APluginRemovalAsShown|null $removal     a removal's account, where the answer is about one
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $typing,
        public bool $isWorking,
        public bool $afterTheYes,
        public string $workingSaid,
        public bool $hasEnded,
        public string $refusedSaid,
        public ?ARefusalAsShown $refused,
        public array $unapproved,
        public array $installed,
        public ?APluginInstallAsShown $install,
        public ?APluginUpdateAsShown $update,
        public ?APluginRemovalAsShown $removal,
    ) {}
}
