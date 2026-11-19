<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\WhenToTryAgain;
use Modules\Operator\Internal\Presenters\HowTheWaitReads;

it('says the wait in whole minutes rounded up, and in seconds below one minute', function (int $seconds, string $said, int $count): void {
    $wait = new HowTheWaitReads()->of(WhenToTryAgain::after(HowLong::ofSeconds($seconds)));

    expect([$wait->said, $wait->count])->toBe([$said, $count]);
})->with([
    'one second' => [1, 'connection.try_again_in.seconds', 1],
    'just under a minute' => [59, 'connection.try_again_in.seconds', 59],
    'a minute' => [60, 'connection.try_again_in.minutes', 1],
    'just over a minute' => [61, 'connection.try_again_in.minutes', 2],
    'two minutes' => [120, 'connection.try_again_in.minutes', 2],
    'just under an hour' => [3_599, 'connection.try_again_in.minutes', 60],
]);

it('says no wait where the stack named none, or named none to wait', function (WhenToTryAgain $when): void {
    $wait = new HowTheWaitReads()->of($when);

    expect([$wait->said, $wait->count])->toBe(['', 0]);
})->with([
    'unsaid' => [WhenToTryAgain::unsaid()],
    'nothing to wait' => [WhenToTryAgain::after(HowLong::ofSeconds(0))],
]);
