<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\AServiceWaiting;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheChoices;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\TheSettingsRead;

/** One of what the stack listed, found by the name a tap or a route carries. */
final readonly class Among
{
    /** The setting of that name among these, or none. */
    public static function theSettings(TheSettingsRead $settings, string $named): ?ASetting
    {
        foreach ($settings as $setting) {
            if ($setting->name() === $named) {
                return $setting;
            }
        }

        return null;
    }

    /** The service held back among these, or none where it is not. */
    public static function theWaiting(TheServicesWaiting $waiting, ServiceId $service): ?AServiceWaiting
    {
        foreach ($waiting as $one) {
            if ($one->service()->isTheSameAs($service)) {
                return $one;
            }
        }

        return null;
    }

    /** Whether these list the value. */
    public static function theChoices(TheChoices $choices, string $value): bool
    {
        foreach ($choices as $choice) {
            if ($choice->value() === $value) {
                return true;
            }
        }

        return false;
    }
}
