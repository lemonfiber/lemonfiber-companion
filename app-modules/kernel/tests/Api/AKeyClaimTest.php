<?php

declare(strict_types=1);

use Modules\Kernel\Api\AKeyClaim;
use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KeySaysNothing;

it('holds the link as the stack gave it, and when it lapses', function (): void {
    $claim = AKeyClaim::of(AKeysName::of('Home Assistant'), 'lemonfiber://key?claim=c1', AMomentAsWritten::byTheStack('2026-10-13T21:10:00'));

    expect($claim->name()->shown())->toBe('Home Assistant')
        ->and($claim->carried())->toBe('lemonfiber://key?claim=c1')
        ->and($claim->lapses()->read(read: static fn(Instant $at): Instant => $at, unreadable: static fn(): Instant => Instant::atEpochSeconds(0))->epochSeconds())->toBe(1791925800);
});

it('refuses a claim with no link to collect it by', function (): void {
    expect(static fn(): AKeyClaim => AKeyClaim::of(AKeysName::of('Home Assistant'), '  ', AMomentAsWritten::byTheStack('2026-10-13T21:10:00')))
        ->toThrow(KeySaysNothing::class);
});
