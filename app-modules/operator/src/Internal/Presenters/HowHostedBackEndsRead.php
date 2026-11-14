<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhereAlertsGo;
use Modules\Operator\Internal\ViewModels\AHostAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatHostedBackEndsSend;

/** The hosted back-ends among where a machine's alerts go, for What leaves. */
final readonly class HowHostedBackEndsRead
{
    public static function signedOut(): WhatHostedBackEndsSend
    {
        return new WhatHostedBackEndsSend(HowTheReadingWent::theSessionEnded(), []);
    }

    public static function met(Obstacle $why): WhatHostedBackEndsSend
    {
        return new WhatHostedBackEndsSend(HowTheReadingWent::somethingStopped($why), []);
    }

    public static function in(WhereAlertsGo $read): WhatHostedBackEndsSend
    {
        $hosts = [];

        foreach ($read->backEnds as $backEnd) {
            $hosts = [...$hosts, ...$backEnd->hosted()->either(
                notHosted: static fn(): WhatHostedBackEndsSend => new WhatHostedBackEndsSend(HowTheReadingWent::itCameBack(), []),
                hosted: static fn(string $host, array $leaves): WhatHostedBackEndsSend => new WhatHostedBackEndsSend(HowTheReadingWent::itCameBack(), [new AHostAsShown($host, $leaves)]),
            )->hosts];
        }

        return new WhatHostedBackEndsSend(HowTheReadingWent::itCameBack(), $hosts);
    }
}
