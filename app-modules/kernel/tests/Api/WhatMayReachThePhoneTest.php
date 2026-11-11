<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnAlertsSort;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\KindsOfNotification;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\WhatMayReachThePhone;

use function sprintf;

it('lets every kind and every problem through until the operator chooses', function (KindOfNotification $kind, Severity $severity): void {
    $everything = WhatMayReachThePhone::everything();

    expect($everything->lets(AnAlertsSort::of($kind, $severity)))->toBeTrue()
        ->and($everything->isOn($kind))->toBeTrue()
        ->and($everything->floor())->toBe(Severity::Advisory)
        ->and(iterator_to_array($everything->switchedOff(), preserve_keys: false))->toBe([]);
})->with(KindOfNotification::cases())->with(Severity::cases());

it('holds back a kind switched off, at every severity, and lets the others through', function (Severity $severity): void {
    $chosen = WhatMayReachThePhone::everything()->withOff(KindOfNotification::Backup);

    expect($chosen->lets(AnAlertsSort::of(KindOfNotification::Backup, $severity)))->toBeFalse()
        ->and($chosen->lets(AnAlertsSort::of(KindOfNotification::Update, $severity)))->toBeTrue()
        ->and($chosen->isOn(KindOfNotification::Backup))->toBeFalse()
        ->and($chosen->withOn(KindOfNotification::Backup)->lets(AnAlertsSort::of(KindOfNotification::Backup, $severity)))->toBeTrue();
})->with(Severity::cases());

it('tells a problem at the floor or worse, and holds back one below it', function (): void {
    $fromErrors = WhatMayReachThePhone::everything()->withFloor(Severity::Error);

    expect($fromErrors->lets(AnAlertsSort::of(KindOfNotification::Problem, Severity::Critical)))->toBeTrue()
        ->and($fromErrors->lets(AnAlertsSort::of(KindOfNotification::Problem, Severity::Error)))->toBeTrue()
        ->and($fromErrors->lets(AnAlertsSort::of(KindOfNotification::Problem, Severity::Warning)))->toBeFalse()
        ->and($fromErrors->lets(AnAlertsSort::of(KindOfNotification::Problem, Severity::Advisory)))->toBeFalse()
        ->and($fromErrors->floor())->toBe(Severity::Error);
});

it('weighs only problems against the floor', function (KindOfNotification $kind): void {
    expect(WhatMayReachThePhone::everything()->withFloor(Severity::Critical)->lets(AnAlertsSort::of($kind, Severity::Advisory)))->toBeTrue();
})->with([KindOfNotification::Completion, KindOfNotification::Update, KindOfNotification::Backup, KindOfNotification::Request, KindOfNotification::Member]);

it('holds back every problem while problems are switched off, whatever the floor', function (Severity $severity): void {
    expect(WhatMayReachThePhone::everything()->withFloor(Severity::Advisory)->withOff(KindOfNotification::Problem)->lets(AnAlertsSort::of(KindOfNotification::Problem, $severity)))->toBeFalse();
})->with(Severity::cases());

it('keeps the floor when a kind is switched, and the kinds when the floor moves', function (): void {
    $chosen = WhatMayReachThePhone::everything()->withFloor(Severity::Warning)->withOff(KindOfNotification::Member)->withOff(KindOfNotification::Backup);

    expect($chosen->floor())->toBe(Severity::Warning)
        ->and(iterator_to_array($chosen->switchedOff(), preserve_keys: false))->toBe([KindOfNotification::Backup, KindOfNotification::Member])
        ->and(iterator_to_array($chosen->withFloor(Severity::Critical)->switchedOff(), preserve_keys: false))->toBe([KindOfNotification::Backup, KindOfNotification::Member])
        ->and($chosen->withOn(KindOfNotification::Member)->floor())->toBe(Severity::Warning)
        ->and(iterator_to_array($chosen->withOn(KindOfNotification::Member)->switchedOff(), preserve_keys: false))->toBe([KindOfNotification::Backup]);
});

it('names each kind switched off once, in the order the kinds are declared', function (): void {
    expect(iterator_to_array(WhatMayReachThePhone::from(Severity::Error, KindOfNotification::Member, KindOfNotification::Problem, KindOfNotification::Member)->switchedOff(), preserve_keys: false))
        ->toBe([KindOfNotification::Problem, KindOfNotification::Member])
        ->and(iterator_to_array(WhatMayReachThePhone::everything()->withOff(KindOfNotification::Update)->withOff(KindOfNotification::Update)->switchedOff(), preserve_keys: false))
        ->toBe([KindOfNotification::Update]);
});

it('holds the kinds a stack raises once each, in the order the kinds are declared', function (): void {
    $raised = KindsOfNotification::these(KindOfNotification::Member, KindOfNotification::Problem, KindOfNotification::Member);

    expect(iterator_to_array($raised, preserve_keys: false))->toBe([KindOfNotification::Problem, KindOfNotification::Member])
        ->and($raised->include(KindOfNotification::Member))->toBeTrue()
        ->and($raised->include(KindOfNotification::Backup))->toBeFalse()
        ->and(iterator_to_array(KindsOfNotification::these(), preserve_keys: false))->toBe([]);
});

it('carries the kind and the severity it was sorted by', function (): void {
    $sort = AnAlertsSort::of(KindOfNotification::Request, Severity::Error);

    expect($sort->kind())->toBe(KindOfNotification::Request)
        ->and($sort->severity())->toBe(Severity::Error);
});

it('names each kind for the notification page by its wire word', function (KindOfNotification $kind): void {
    expect($kind->saidOnTheScreen())->toBe(sprintf('notifications.kind.%s', $kind->value));
})->with(KindOfNotification::cases());
