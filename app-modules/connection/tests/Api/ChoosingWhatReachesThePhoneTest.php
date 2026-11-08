<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\ChoosingWhatReachesThePhone;
use Modules\Connection\Internal\NotifyingKept;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatMayReachThePhone;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\NotifyingKeptInMemory;

/** What reaches the phone, chosen over this seal and this store. */
function choosingOver(NotifyingKept $kept, ?Sealed $seal = null): ChoosingWhatReachesThePhone
{
    return new ChoosingWhatReachesThePhone($seal ?? ASealInMemory::working(), $kept, FrozenClock::at(Instant::atEpochSeconds(1_790_000_000)));
}

it('lets everything through from a stack the operator has chosen nothing for', function (): void {
    expect(choosingOver(NotifyingKeptInMemory::empty())->forTheStack(StackId::rememberedAs('the-loft')))->toEqual(WhatMayReachThePhone::everything());
});

it('keeps each choice for its stack alone, and answers what is in force after it', function (): void {
    $choosing = choosingOver(NotifyingKeptInMemory::empty());
    $loft = StackId::rememberedAs('the-loft');

    $afterOff = $choosing->switchOff($loft, KindOfNotification::Backup);
    $afterFloor = $choosing->tellProblemsFrom($loft, Severity::Error);

    expect($afterOff->isOn(KindOfNotification::Backup))->toBeFalse()
        ->and($afterFloor->isOn(KindOfNotification::Backup))->toBeFalse()
        ->and($afterFloor->floor())->toBe(Severity::Error)
        ->and($choosing->forTheStack($loft))->toEqual($afterFloor)
        ->and($choosing->forTheStack(StackId::rememberedAs('the-shed')))->toEqual(WhatMayReachThePhone::everything())
        ->and($choosing->switchOn($loft, KindOfNotification::Backup)->isOn(KindOfNotification::Backup))->toBeTrue()
        ->and($choosing->forTheStack($loft)->floor())->toBe(Severity::Error);
});

it('answers what was in force before where a choice could not be kept', function (): void {
    $loft = StackId::rememberedAs('the-loft');

    expect(choosingOver(NotifyingKeptInMemory::unreachable())->switchOff($loft, KindOfNotification::Problem)->isOn(KindOfNotification::Problem))->toBeTrue()
        ->and(choosingOver(NotifyingKeptInMemory::empty(), ASealInMemory::withNoSecureStorage())->tellProblemsFrom($loft, Severity::Critical)->floor())->toBe(Severity::Advisory);
});

it('reads a choice it cannot open, or one a later build wrote, as everything', function (): void {
    $loft = StackId::rememberedAs('the-loft');
    $kept = NotifyingKeptInMemory::empty();
    $seal = ASealInMemory::working();
    $kept->keep($seal->stack($loft), SealedPayload::of('not sealed by this seal'), Shape::One, Instant::atEpochSeconds(1_790_000_000));

    expect(choosingOver($kept, $seal)->forTheStack($loft))->toEqual(WhatMayReachThePhone::everything())
        ->and(choosingOver(NotifyingKeptInMemory::empty()->holdsOneALaterBuildWrote($seal->stack($loft)), $seal)->forTheStack($loft))->toEqual(WhatMayReachThePhone::everything());
});

it('forgets a removed stack\'s choices and no other stack\'s, and says whether it still keeps any', function (): void {
    $choosing = choosingOver(NotifyingKeptInMemory::empty());
    $loft = StackId::rememberedAs('the-loft');
    $shed = StackId::rememberedAs('the-shed');
    $choosing->switchOff($loft, KindOfNotification::Update);
    $choosing->switchOff($shed, KindOfNotification::Member);

    expect($choosing->keepsAnythingOf($loft))->toBeTrue()
        ->and($choosing->forgetTheStack($loft)->howMany())->toBe(1)
        ->and($choosing->keepsAnythingOf($loft))->toBeFalse()
        ->and($choosing->forTheStack($loft))->toEqual(WhatMayReachThePhone::everything())
        ->and($choosing->forTheStack($shed)->isOn(KindOfNotification::Member))->toBeFalse();
});

it('says it may still keep something where nothing can be sealed, or a row it cannot read is there', function (): void {
    $loft = StackId::rememberedAs('the-loft');
    $seal = ASealInMemory::working();

    expect(choosingOver(NotifyingKeptInMemory::empty(), ASealInMemory::withNoSecureStorage())->keepsAnythingOf($loft))->toBeTrue()
        ->and(choosingOver(NotifyingKeptInMemory::empty()->holdsOneALaterBuildWrote($seal->stack($loft)), $seal)->keepsAnythingOf($loft))->toBeTrue();
});
