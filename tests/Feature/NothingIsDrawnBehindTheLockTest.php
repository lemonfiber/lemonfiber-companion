<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\ScreenRouter;
use Bootstrap\Composition\NativePHP\TheLockIsOnTheGlass;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhenTheLockAsks;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\Screens\Locked;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NavigationIntent;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\AScreenUnderTheLock;
use Tests\Support\WhatTheDeviceWouldDraw;

// What the lock screen draws, and how it goes on.
//
// Asked against the rendered tree rather than the template text, because these
// are claims about a frame.

/** A machine this device could be holding. Named for this file. */
function aStackBehindTheLock(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** Where a lock screen went, as a path, or nowhere. Named for this file. */
function whereTheLockWent(Locked $screen): string
{
    $intent = $screen->getNavigationIntent();

    return $intent instanceof NavigationIntent ? sprintf('%s %s', $intent->type, $intent->uri ?? '') : 'nowhere';
}

it('draws the reason and the way in, and nothing else', function (): void {
    // Exactly, not *contains*: a check that found the unlock copy present would
    // pass for a frame carrying the machine list underneath it.
    $drawn = WhatTheDeviceWouldDraw::by(new Locked(ADeviceThatKnowsYou::refusing()));

    expect($drawn->said())->toBe([__('device.unlock_reason'), __('device.unlock')])
        ->and($drawn->offers())->toBe([__('device.unlock')])
        ->and($drawn->said())->not->toContain(aStackBehindTheLock()->name()->shown());
});

it('draws no top bar, so there is no back arrow and no edge swipe', function (): void {
    $tree = (string) json_encode(WhatTheDeviceWouldDraw::tree(new Locked(ADeviceThatKnowsYou::refusing())));

    expect($tree)->not->toContain('top_bar')
        ->and($tree)->not->toContain('side_nav')
        ->and($tree)->not->toContain('bottom_nav');
});

it('stays where it is on the back button', function (): void {
    $screen = new Locked(ADeviceThatKnowsYou::willing());

    $screen->onBackPressed();

    expect(whereTheLockWent($screen))->toBe('nowhere');
});

it('goes on only when the device lets the operator in', function (): void {
    $refused = new Locked(ADeviceThatKnowsYou::refusing());
    $refused->tryToUnlock();

    $willing = new Locked(ADeviceThatKnowsYou::willing());
    $willing->tryToUnlock();

    expect(whereTheLockWent($refused))->toBe('nowhere')
        ->and(whereTheLockWent($willing))->toBe(sprintf('replace %s', AScreenWithoutAStack::TheList->value));
});

it('asks the device again on every tap, even where it would not ask by itself', function (): void {
    $device = ADeviceThatKnowsYou::refusing();
    $screen = new Locked($device);
    $screen->setData([Locked::AWAITS => true]);

    $screen->tryToUnlock();
    $screen->tryToUnlock();

    expect($device->asked())->toBe(2);
});

it('goes on when the device says its own prompt opened the lock, and not before', function (): void {
    $device = ADeviceThatKnowsYou::willing();
    $screen = new Locked($device);

    $screen->drawn();
    $screen->lockMoved();

    expect(whereTheLockWent($screen))->toBe('nowhere');

    $device->answers();
    $screen->lockMoved();

    expect(whereTheLockWent($screen))->toBe(sprintf('replace %s', AScreenWithoutAStack::TheList->value));
});

it('lets the device ask by itself where nothing is awaited', function (): void {
    $device = ADeviceThatKnowsYou::willing();

    new Locked($device)->drawn();

    expect($device->askedByItself())->toBe(1)
        ->and($device->asked())->toBe(0);
});

it('does not let the device ask by itself over an outcome still awaited', function (): void {
    $device = ADeviceThatKnowsYou::willing();
    $screen = new Locked($device);
    $screen->setData([Locked::AWAITS => true]);

    $screen->drawn();

    expect($device->drawnTimes())->toBe(1)
        ->and($device->askedByItself())->toBe(0);
});

it('goes on when it is come back to after the lock opened', function (): void {
    $screen = new Locked(ADeviceThatKnowsYou::unlocked());

    $screen->onResume();

    expect(whereTheLockWent($screen))->toBe(sprintf('replace %s', AScreenWithoutAStack::TheList->value));
});

it('stays when it is come back to and the lock still stands', function (): void {
    $screen = new Locked(ADeviceThatKnowsYou::refusing());

    $screen->onResume();

    expect(whereTheLockWent($screen))->toBe('nowhere');
});

it('is asked by itself as the device is told, with the prompt either way', function (): void {
    expect(WhenTheLockAsks::ByItself->byItself())->toBeTrue()
        ->and(WhenTheLockAsks::OnlyWhenTapped->byItself())->toBeFalse();
});

/**
 * A navigation stack holding these paths, every one of them built as the lock.
 *
 * @param list<string> $paths
 */
function aStackOfLocks(ADeviceThatKnowsYou $device, array $paths): Locked
{
    $top = new Locked($device);
    $router = new ScreenRouter(static fn(): Locked => $top);

    $entries = [];

    foreach ($paths as $path) {
        $entries[] = ['uri' => $path];
    }

    $router->preloadStack($entries);
    $top->setRouter($router);

    return $top;
}

it('steps back to the screen it was put over, whose state was kept under it', function (): void {
    $screen = aStackOfLocks(ADeviceThatKnowsYou::willing(), [AScreenWithoutAStack::TheList->value, AScreenWithoutAStack::Locked->value]);

    $screen->tryToUnlock();

    expect(whereTheLockWent($screen))->toBe('back ');
});

it('builds the screen it stood in for', function (): void {
    $screen = aStackOfLocks(ADeviceThatKnowsYou::willing(), [AScreenWithoutAStack::PairByTyping->value]);

    $screen->tryToUnlock();

    expect(whereTheLockWent($screen))->toBe(sprintf('replace %s', AScreenWithoutAStack::PairByTyping->value));
});

it('opens on the list where it is the only screen there is', function (): void {
    $screen = aStackOfLocks(ADeviceThatKnowsYou::willing(), [AScreenWithoutAStack::Locked->value]);

    $screen->tryToUnlock();

    expect(whereTheLockWent($screen))->toBe(sprintf('replace %s', AScreenWithoutAStack::TheList->value));
});

it('tells the device only the lock screen is on the glass', function (): void {
    $device = ADeviceThatKnowsYou::refusing();
    $observer = new TheLockIsOnTheGlass();

    // Held here, because the runloop holds the screen it marks only weakly.
    $locked = new Locked($device);
    $other = AScreenUnderTheLock::atRest();

    $before = NativeComponent::markActive($locked);
    $observer->tree([], AScreenWithoutAStack::Locked->value);
    NativeComponent::markActive($other);
    $observer->tree([], AScreenWithoutAStack::TheList->value);
    $observer->event([], null);
    $observer->nav([]);
    NativeComponent::restoreActive($before);

    expect($device->drawnTimes())->toBe(1);
});
