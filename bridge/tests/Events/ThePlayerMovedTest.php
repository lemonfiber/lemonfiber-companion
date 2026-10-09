<?php

declare(strict_types=1);

use Lemonfiber\Native\Events\ThePlayerMoved;
use Native\Mobile\Events\Concerns\BroadcastsGlobally;

it('carries nothing, so a forged one can only make the app ask again', function (): void {
    $moved = new ReflectionClass(ThePlayerMoved::class);

    expect($moved->getConstructor())->toBeNull()
        ->and($moved->getProperties())->toBe([])
        ->and(new ThePlayerMoved())->toBeInstanceOf(BroadcastsGlobally::class);
});

it('is named as both native halves send it', function (): void {
    // The name is the wire: each half sends the class's full name, and a
    // renamed class is an event nobody hears.
    foreach (['bridge/resources/android/PlayerSession.kt', 'bridge/resources/ios/PlayerFunctions.swift'] as $half) {
        expect((string) file_get_contents(sprintf('%s/%s', dirname(__DIR__, 3), $half)))
            ->toContain(str_replace('\\', '\\\\', ThePlayerMoved::class));
    }
});
