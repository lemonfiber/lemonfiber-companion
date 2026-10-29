<?php

declare(strict_types=1);

use Bootstrap\Composition\CompositionRoot;
use Bootstrap\Composition\NativePHP\TheLockIsOnTheGlass;
use Bootstrap\Composition\NativePHP\TheOperatorIsHere;
use Bootstrap\Composition\NativePHP\WhenTheLockMoves;
use Bootstrap\Composition\NativePHP\WhenThePlayerMoves;
use Illuminate\Log\LogManager;
use Lemonfiber\Native\Events\TheLockMoved;
use Lemonfiber\Native\Events\ThePlayerMoved;
use Modules\Connection\Api\TurningNotificationsOn;
use Modules\Device\Api\PlatformNotifier;
use Modules\Device\Internal\WhatWasToldLately;
use Modules\Household\Internal\Playing\WhatIsPlaying;
use Modules\Kernel\Api\AJoinLink;
use Modules\News\Internal\WhatEachStackLastNamed;
use Modules\Sdk\Api\ClientsThatAskTheDevice;
use Modules\Sdk\Api\Listeners;
use Modules\Sdk\Internal\WhatEachStackOffers;
use Native\Mobile\Edge\TreeObservers;
use Psr\Log\NullLogger;

pest()->group('holds:bootstrap/Composition');

it('has the platform draw its own sheets and bars for the dark themes, and open a join link at the app\'s scheme', function (): void {
    expect(config('nativephp.appearance'))->toBe('dark')
        ->and(config('nativephp.android.status_bar_style'))->toBe('light')
        ->and(config('nativephp.deeplink_scheme'))->toBe(AJoinLink::scheme());
});

it('leaves out of a build each text the checkout names once, then what only a checkout holds', function (): void {
    config()->set('nativephp.cleanup_exclude_files', ['storage', 'storage', 7, 'coverage', 'tests']);

    new CompositionRoot(app())->register();

    expect(config('nativephp.cleanup_exclude_files'))->toBe(['storage', 'coverage', 'tests', '.phpstan-cache', '.branch-backups']);
});

it('leaves out of a build what only a checkout holds where the checkout names no list', function (): void {
    config()->set('nativephp.cleanup_exclude_files', 'storage');

    new CompositionRoot(app())->register();

    expect(config('nativephp.cleanup_exclude_files'))->toBe(['.phpstan-cache', '.branch-backups', 'coverage']);
});

it('holds one of each memory a screen hears and the next draws, for the life of the process', function (string $memory): void {
    expect(app($memory))->toBe(app($memory));
})->with([
    'what was told lately' => WhatWasToldLately::class,
    'what each stack last named' => WhatEachStackLastNamed::class,
    'what is playing' => WhatIsPlaying::class,
    'what each stack offers' => WhatEachStackOffers::class,
]);

it('hands the switch that turns notifications on the notifier the phone answers', function (): void {
    expect(new ReflectionProperty(TurningNotificationsOn::class, 'notifier')->getValue(app(TurningNotificationsOn::class)))->toBeInstanceOf(PlatformNotifier::class);
});

it('notes a silent reach in a debug build\'s log, and in no log in any other build', function (bool $debug): void {
    $log = $debug ? LogManager::class : NullLogger::class;
    config()->set('app.debug', $debug);

    expect(new ReflectionProperty(ClientsThatAskTheDevice::class, 'notes')->getValue(app(ClientsThatAskTheDevice::class)))->toBeInstanceOf($log)
        ->and(new ReflectionProperty(Listeners::class, 'notes')->getValue(app(Listeners::class)))->toBeInstanceOf($log);
})->with([
    'a debug build' => [true],
    'a release' => [false],
]);

it('watches each screen come to the front for the lock and then for where the operator is, once booted', function (): void {
    TreeObservers::reset();

    new CompositionRoot(app())->boot();

    $observers = new ReflectionClass(TreeObservers::class)->getStaticPropertyValue('observers');
    assert(is_array($observers));

    expect(array_map(get_debug_type(...), $observers))->toBe([TheLockIsOnTheGlass::class, TheOperatorIsHere::class]);
});

it('hears the lock and the player move once booted', function (): void {
    $heard = app('events')->getRawListeners();

    expect($heard[TheLockMoved::class] ?? [])->toContain(WhenTheLockMoves::class)
        ->and($heard[ThePlayerMoved::class] ?? [])->toContain(WhenThePlayerMoves::class);
});
