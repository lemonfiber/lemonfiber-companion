<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AnUpgradeDescribed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheUpgrade;
use Modules\Kernel\Api\UpgradingTheLibrary;
use Modules\Kernel\Api\WhatTheUpgradeCameTo;
use Override;

/**
 * {@see UpgradingTheLibrary}, answered from what a test put in it.
 *
 * Describing answers with the description it was given and upgrading with
 * what carrying it out came to, and each is counted apart: a fake that
 * counted them together would let a screen pass that fetched a library when
 * it meant to ask what that would cost.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatUpgrades implements UpgradingTheLibrary
{
    private int $descriptions = 0;

    private int $upgrades = 0;

    private function __construct(
        private readonly WhatTheUpgradeCameTo $described,
        private readonly WhatTheUpgradeCameTo $carriedOut,
    ) {}

    /** A stack that describes the upgrade this way, and carries it out that way. */
    public static function describing(TheUpgrade $described, TheUpgrade $carriedOut): self
    {
        return new self(WhatTheUpgradeCameTo::said($described), WhatTheUpgradeCameTo::said($carriedOut));
    }

    /** A stack the operator could not reach, for the reason given, whatever is asked. */
    public static function met(Obstacle $why): self
    {
        return new self(WhatTheUpgradeCameTo::met($why), WhatTheUpgradeCameTo::met($why));
    }

    /** How many times what upgrading would come to was asked. */
    public function descriptions(): int
    {
        return $this->descriptions;
    }

    /** How many times it was carried out. */
    public function upgrades(): int
    {
        return $this->upgrades;
    }

    #[Override]
    public function whatItWouldComeTo(Stack $stack, Session $session): WhatTheUpgradeCameTo
    {
        $this->descriptions++;

        return $this->described;
    }

    #[Override]
    public function upgrade(Stack $stack, Session $session, AnUpgradeDescribed $agreed): WhatTheUpgradeCameTo
    {
        $this->upgrades++;

        return $this->carriedOut;
    }
}
