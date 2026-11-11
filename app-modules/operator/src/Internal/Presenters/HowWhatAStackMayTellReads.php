<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function array_reverse;
use function in_array;

use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\KindsOfNotification;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\WhatMayReachThePhone;
use Modules\Operator\Internal\ViewModels\AChoiceOfASettingAsShown;
use Modules\Operator\Internal\ViewModels\AKindOfNotificationAsShown;
use Modules\Operator\Internal\ViewModels\ASettingAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatAStackMayTellAsShown;

use function sprintf;

/**
 * One stack's section of the notification page, as screen fields.
 *
 * `F2` — data in, view model out. The floor is offered slightest first, the
 * way a reader climbs it, though severity is declared worst first.
 */
final readonly class HowWhatAStackMayTellReads
{
    /** The stack said which kinds it raises, and this is what the operator chose for it. */
    public function told(string $stack, string $name, KindsOfNotification $raised, WhatMayReachThePhone $chosen): WhatAStackMayTellAsShown
    {
        $words = [];
        $kinds = [];

        foreach ($raised as $kind) {
            $words[] = $kind->value;
            $kinds[] = new AKindOfNotificationAsShown(kind: $kind->value, said: $kind->saidOnTheScreen(), isOn: $chosen->isOn($kind));
        }

        return new WhatAStackMayTellAsShown(
            stack: $stack,
            name: $name,
            went: HowTheReadingWent::itCameBack(),
            raised: $words,
            kinds: $kinds,
            floor: $raised->include(KindOfNotification::Problem) ? $this->floor($chosen->floor()) : null,
            problemsAreOn: $chosen->isOn(KindOfNotification::Problem),
            keptOff: [],
            floorSaid: $this->severitySaid($chosen->floor()),
        );
    }

    /** The stack could not be read, and this is what the operator chose for it that still applies. */
    public function unread(string $stack, string $name, HowTheReadingWent $went, WhatMayReachThePhone $chosen): WhatAStackMayTellAsShown
    {
        $off = [];

        foreach ($chosen->switchedOff() as $kind) {
            $off[] = $kind->saidOnTheScreen();
        }

        return new WhatAStackMayTellAsShown(
            stack: $stack,
            name: $name,
            went: $went,
            raised: [],
            kinds: [],
            floor: null,
            problemsAreOn: $chosen->isOn(KindOfNotification::Problem),
            keptOff: $off,
            floorSaid: $this->severitySaid($chosen->floor()),
        );
    }

    /** The same section, drawn again for what the operator chose since, without asking the stack again. */
    public function again(WhatAStackMayTellAsShown $was, WhatMayReachThePhone $chosen): WhatAStackMayTellAsShown
    {
        if (! $was->went->cameBack()) {
            return $this->unread($was->stack, $was->name, $was->went, $chosen);
        }

        $raised = [];

        foreach (KindOfNotification::cases() as $kind) {
            if (in_array($kind->value, $was->raised, strict: true)) {
                $raised[] = $kind;
            }
        }

        return $this->told($was->stack, $was->name, KindsOfNotification::these(...$raised), $chosen);
    }

    /** The key for what a severity is called as a floor. */
    private function severitySaid(Severity $severity): string
    {
        return sprintf('notifications.floor.%s', $severity->value);
    }

    private function floor(Severity $inForce): ASettingAsShown
    {
        $offered = [];

        foreach (array_reverse(Severity::cases()) as $severity) {
            $offered[] = new AChoiceOfASettingAsShown(said: $this->severitySaid($severity), word: $severity->value, chosen: $severity === $inForce);
        }

        return new ASettingAsShown(said: $this->severitySaid($inForce), offered: $offered);
    }
}
