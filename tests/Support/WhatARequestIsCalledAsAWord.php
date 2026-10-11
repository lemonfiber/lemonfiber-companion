<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\KindOfRequest;
use Modules\Kernel\Api\Wanted;

use function sprintf;

/** What a request is called, as one word a test can compare: its title, or the kind of thing it is where nothing named it yet. */
final readonly class WhatARequestIsCalledAsAWord
{
    public static function of(Wanted $wanted): string
    {
        return $wanted->called()->either(
            named: static fn(string $title): TheWordCarriedOut => new TheWordCarriedOut($title),
            notYet: static fn(KindOfRequest $kind): TheWordCarriedOut => new TheWordCarriedOut(sprintf('a %s not named yet', $kind->value)),
        )->said;
    }
}
