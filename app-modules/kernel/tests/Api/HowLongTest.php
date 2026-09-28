<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\HowLongIsBelowNothing;

use function sprintf;

it('says a span in the coarsest unit it fills, and how many of it, floored', function (): void {
    $said = static fn(int $seconds): string => sprintf('%s:%d', HowLong::ofSeconds($seconds)->unit()->value, HowLong::ofSeconds($seconds)->howMany());

    expect($said(0))->toBe('minutes:0')
        ->and($said(59))->toBe('minutes:0')
        ->and($said(150))->toBe('minutes:2')
        ->and($said(3_600))->toBe('hours:1')
        ->and($said(10_799))->toBe('hours:2')
        ->and($said(86_400))->toBe('days:1')
        ->and($said(200_000))->toBe('days:2');
});

it('keeps the seconds as the stack counted them', function (): void {
    expect(HowLong::ofSeconds(10_800)->inSeconds())->toBe(10_800)
        ->and(HowLong::ofSeconds(10_800)->unit())->toBe(HowLongAgo::Hours);
});

it('refuses less than no time, and says how much it was given', function (): void {
    expect(static fn(): HowLong => HowLong::ofSeconds(-1))
        ->toThrow(HowLongIsBelowNothing::class, 'as -1 seconds');
});
