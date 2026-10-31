<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AServiceWaiting;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\WhatAServiceWaitsFor;
use Modules\Operator\Internal\Listed;
use Modules\Operator\Internal\ViewModels\ALineToSay;
use Modules\Operator\Internal\ViewModels\AServiceWaitingAsShown;

/**
 * Each service the stack holds back until the operator gives what it needs to start, as every screen that names one draws it.
 *
 * A setting it waits for is named with its choices where it lists them; a
 * secret is named and said to be entered at the stack's terminal; the service
 * it runs through is named as waiting itself. None of it carries a value.
 */
final readonly class HowAServiceWaitingReads
{
    /** The catalogue key for a setting it waits for that lists no choices. */
    private const string A_SETTING = 'plugins.waiting.setting';

    /** The catalogue key for a setting it waits for, with the values it accepts. */
    private const string A_CHOICE = 'plugins.waiting.choice';

    /** The catalogue key for a secret it waits for. */
    private const string A_SECRET = 'plugins.waiting.secret';

    /** The catalogue key for the service it runs through. */
    private const string A_SERVICE = 'plugins.waiting.service';

    /**
     * Each one, in the stack's order.
     *
     * @return list<AServiceWaitingAsShown>
     */
    public function these(TheServicesWaiting $waiting): array
    {
        $shown = [];

        foreach ($waiting as $one) {
            $shown[] = $this->one($one);
        }

        return $shown;
    }

    /** One of them. */
    public function one(AServiceWaiting $waiting): AServiceWaitingAsShown
    {
        $wants = [];

        foreach ($waiting as $want) {
            $wants[] = $this->want($want);
        }

        return new AServiceWaitingAsShown($waiting->service(), $waiting->service()->named(), $wants);
    }

    private function want(WhatAServiceWaitsFor $want): ALineToSay
    {
        return $want->either(
            setting: static fn(string $setting, array $choices): ALineToSay => $choices === []
                ? new ALineToSay(self::A_SETTING, ['setting' => $setting])
                : new ALineToSay(self::A_CHOICE, ['setting' => $setting, 'choices' => Listed::these(...$choices)]),
            secret: static fn(string $secret): ALineToSay => new ALineToSay(self::A_SECRET, ['secret' => $secret]),
            service: static fn(ServiceId $service): ALineToSay => new ALineToSay(self::A_SERVICE, ['service' => $service->named()]),
        );
    }
}
