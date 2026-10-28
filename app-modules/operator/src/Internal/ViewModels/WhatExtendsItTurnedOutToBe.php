<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where the plugins screen has got to, flattened for the template.
 *
 * One model for every state the screen can be in, each saying only its own:
 * what is installed, a source being typed, the stack at work, an install's,
 * an update's, a removal's, a proof's or a recipe run's account, the stack refusing with its reason and
 * any service left unapproved to take its shape, or the work ended with no
 * outcome.
 */
final readonly class WhatExtendsItTurnedOutToBe
{
    /**
     * @param WhereTheWorkStands         $stands    where the work stands, not asked while a source is typed, and whether the stack could be asked at all
     * @param string|null                $working   the catalogue key for what the stack is at, where it is at work
     * @param AnActRefused|null          $refused   what the stack would not do, where it refused
     * @param list<APluginAsShown>       $installed every plugin the record holds, where the listing came back
     * @param APluginInstallAsShown|null $install   an install's account, where the answer is about one
     * @param APluginUpdateAsShown|null  $update    an update's account, where the answer is about one
     * @param APluginRemovalAsShown|null $removal   a removal's account, where the answer is about one
     * @param APluginReproofAsShown|null $proof     what proving a plugin again would ask or came to, where the answer is about that
     * @param ARecipeRunAsShown|null     $run       what running a demand recipe would do or came to, where the answer is about that
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?string $working = null,
        public ?AnActRefused $refused = null,
        public array $installed = [],
        public ?APluginInstallAsShown $install = null,
        public ?APluginUpdateAsShown $update = null,
        public ?APluginRemovalAsShown $removal = null,
        public ?APluginReproofAsShown $proof = null,
        public ?ARecipeRunAsShown $run = null,
    ) {}
}
