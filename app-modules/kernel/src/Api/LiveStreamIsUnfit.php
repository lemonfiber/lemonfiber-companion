<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

final class LiveStreamIsUnfit extends InvalidArgumentException
{
    public static function unnamed(): self
    {
        return new self('A live stream arrived with nothing to renew or stop it by.');
    }

    public static function servedNowhere(): self
    {
        return new self('A live stream arrived with neither the tuner\'s stream nor a transcoded playlist.');
    }
}
