<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheUpgrade;
use Modules\Operator\Internal\ViewModels\AnUpgradeAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheUpgradeTurnedOutToBe;

/**
 * What asking a stack about upgrading the library produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out. Kind by kind, as the stack listed them, each
 * with what the stack counted it would take or why it could not count, and no
 * figure for the whole: a sum worked out here would be a number the stack
 * never said.
 */
final readonly class HowAnUpgradeReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheUpgradeTurnedOutToBe
    {
        return new TheUpgradeTurnedOutToBe(went: HowTheReadingWent::theSessionEnded(), headingSaid: '', kinds: []);
    }

    /** The stack answered, and this is the upgrade, described or carried out. */
    public function this(TheUpgrade $upgrade): TheUpgradeTurnedOutToBe
    {
        $kinds = [];

        foreach ($upgrade as $kind) {
            $kinds[] = $kind->total()->either(
                bytes: static fn(int $bytes): AnUpgradeAsShown => new AnUpgradeAsShown(
                    kind: $kind->kind(),
                    preset: $kind->preset(),
                    totalSaid: 'quality.upgrade.takes',
                    figure: HowBig::of($bytes)->figure,
                    unit: HowBig::of($bytes)->said,
                    why: '',
                    askingSaid: $kind->asking()->saidOnTheScreen(),
                    detail: $kind->asking()->detail(),
                ),
                unknown: static fn(string $why): AnUpgradeAsShown => new AnUpgradeAsShown(
                    kind: $kind->kind(),
                    preset: $kind->preset(),
                    totalSaid: 'quality.upgrade.takes_unknown',
                    figure: 0,
                    unit: '',
                    why: $why,
                    askingSaid: $kind->asking()->saidOnTheScreen(),
                    detail: $kind->asking()->detail(),
                ),
            );
        }

        return new TheUpgradeTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            headingSaid: $upgrade->wasCarriedOut() ? 'quality.upgrade.carried_out' : 'quality.upgrade.described',
            kinds: $kinds,
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheUpgradeTurnedOutToBe
    {
        return new TheUpgradeTurnedOutToBe(went: HowTheReadingWent::somethingStopped($why), headingSaid: '', kinds: []);
    }
}
