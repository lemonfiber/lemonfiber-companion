<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\BehindTheLock;
use Bootstrap\Composition\NativePHP\WhenTheLockMoves;
use Lemonfiber\Native\Events\TheLockMoved;
use Modules\Connection\Api\TheLock;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\Screens\Locked;
use Modules\Vault\Api\PlatformStacks;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeRouter;
use Native\Mobile\Edge\NavigationIntent;
use Tests\Support\ALockScreen;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\AScreenUnderTheLock;
use Tests\Support\Fakes\StacksInMemory;

// Every way a screen reaches the glass goes through the navigation stack, and
// every screen it builds is built behind the lock. And every way the device can
// wake the app about its lock can put the lock up, and none can take it down.

/** A machine the store holds. Named for this file. */
function aPairingTheLockGuards(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The shed'),
        Address::of('https://192.168.1.43'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/**
 * Which class the gate asked the container for, for each screen asked.
 *
 * @param list<string> $asked
 *
 * @return list<string>
 */
function whatTheGateBuilt(ADeviceThatKnowsYou $device, Stacks $stacks, array $asked): array
{
    $built = [];
    $gate = new BehindTheLock(new TheLock($device, $stacks), static function (string $class) use (&$built): string {
        $built[] = $class;

        return $class;
    });

    foreach ($asked as $screen) {
        $gate->screen($screen);
    }

    return $built;
}

/** @return list<string> every screen the router serves */
function everyScreenTheRouterServes(): array
{
    $screens = [];

    foreach (NativeRouter::registeredRoutes() as $route) {
        if (is_array($route) && is_string($route['class'] ?? null)) {
            $screens[] = $route['class'];
        }
    }

    return array_values(array_unique($screens));
}

/** Where a screen was sent, as a word. Named for this file. */
function whereTheLockSentIt(NativeComponent $screen): string
{
    $intent = $screen->getNavigationIntent();

    return $intent instanceof NavigationIntent ? sprintf('%s %s', $intent->type, $intent->uri ?? '') : 'nowhere';
}

it('builds the lock in place of every screen the router serves while the lock stands', function (): void {
    $every = everyScreenTheRouterServes();

    expect($every)->not->toBeEmpty()
        ->and(array_unique(whatTheGateBuilt(ADeviceThatKnowsYou::refusing(), StacksInMemory::holding(aPairingTheLockGuards()), $every)))
        ->toBe([Locked::class]);
});

it('builds the screen asked for once the lock is open', function (): void {
    $every = everyScreenTheRouterServes();

    expect(whatTheGateBuilt(ADeviceThatKnowsYou::unlocked(), StacksInMemory::holding(aPairingTheLockGuards()), $every))
        ->toBe($every);
});

it('builds the first run without a prompt where the store holds nothing', function (): void {
    $device = ADeviceThatKnowsYou::refusing();

    expect(whatTheGateBuilt($device, StacksInMemory::working(), [AScreenUnderTheLock::class]))
        ->toBe([AScreenUnderTheLock::class])
        ->and($device->asked())->toBe(0);
});

it('builds the lock where the store will not open', function (): void {
    expect(whatTheGateBuilt(ADeviceThatKnowsYou::refusing(), new PlatformStacks(APlatformStore::refusing()), [AScreenUnderTheLock::class]))
        ->toBe([Locked::class]);
});

it('puts the lock over the screen on view when the device stands it again', function (): void {
    $screen = AScreenUnderTheLock::atRest();

    new WhenTheLockMoves(new TheLock(ADeviceThatKnowsYou::refusing(), StacksInMemory::holding(aPairingTheLockGuards())))->over($screen);

    expect(whereTheLockSentIt($screen))->toBe(sprintf('navigate %s', AScreenWithoutAStack::Locked->value))
        ->and($screen->getNavigationIntent()?->data)->toBe([Locked::AWAITS => false]);
});

it('tells the lock when the screen under it awaits an outcome', function (): void {
    $screen = AScreenUnderTheLock::awaitingAnOutcome();

    new WhenTheLockMoves(new TheLock(ADeviceThatKnowsYou::refusing(), StacksInMemory::holding(aPairingTheLockGuards())))->over($screen);

    expect($screen->getNavigationIntent()?->data)->toBe([Locked::AWAITS => true]);
});

it('leaves the screen on view alone when the lock is open, however often it is woken', function (): void {
    // A forged or replayed wake-up over an open lock changes nothing.
    $screen = AScreenUnderTheLock::atRest();
    $moves = new WhenTheLockMoves(new TheLock(ADeviceThatKnowsYou::unlocked(), StacksInMemory::holding(aPairingTheLockGuards())));

    $moves->over($screen);
    $moves->over($screen);

    expect(whereTheLockSentIt($screen))->toBe('nowhere');
});

it('cannot take the lock down by waking the lock screen', function (): void {
    // A forged or replayed wake-up over the lock screen reads the device, and
    // the device says the lock stands.
    $device = ADeviceThatKnowsYou::refusing();
    $screen = ALockScreen::over($device);
    $moves = new WhenTheLockMoves(new TheLock($device, StacksInMemory::holding(aPairingTheLockGuards())));

    $moves->over($screen);
    $moves->over($screen);

    expect(whereTheLockSentIt($screen))->toBe('nowhere')
        ->and($device->asked())->toBe(0);
});

it('lets the lock screen go on once the device opened the lock', function (): void {
    $screen = ALockScreen::over(ADeviceThatKnowsYou::unlocked());

    new WhenTheLockMoves(new TheLock(ADeviceThatKnowsYou::unlocked(), StacksInMemory::working()))->over($screen);

    expect(whereTheLockSentIt($screen))->toBe(sprintf('replace %s', AScreenWithoutAStack::TheList->value));
});

it('does nothing where no screen is driving the runloop', function (): void {
    $device = ADeviceThatKnowsYou::refusing();

    new WhenTheLockMoves(new TheLock($device, StacksInMemory::holding(aPairingTheLockGuards())))->over(null);

    expect($device->asked())->toBe(0);
});

it('hears the device through the dispatcher, with an event that carries nothing', function (): void {
    expect(new ReflectionClass(TheLockMoved::class)->getConstructor())->toBeNull()
        ->and(app('events')->hasListeners(TheLockMoved::class))->toBeTrue();

    event(new TheLockMoved());
});
