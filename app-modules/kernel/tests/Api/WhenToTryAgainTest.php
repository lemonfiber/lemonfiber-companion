<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\WhenToTryAgain;

it('names no wait, and holds none, where the stack said none', function (): void {
    $when = WhenToTryAgain::unsaid();

    expect($when->isSaid())->toBeFalse()
        ->and($when->wait()->inSeconds())->toBe(0);
});

it('holds the wait the stack named', function (): void {
    $when = WhenToTryAgain::after(HowLong::ofSeconds(90));

    expect($when->isSaid())->toBeTrue()
        ->and($when->wait()->inSeconds())->toBe(90);
});
