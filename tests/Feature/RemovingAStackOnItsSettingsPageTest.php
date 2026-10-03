<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Operator\Internal\Screens\ThisStackOnThisPhone;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Native\Mobile\Edge\NativeRouter;
use Native\Mobile\Edge\NavigationIntent;
use Tests\Support\APhoneOnItsStackSettings;
use Tests\Support\WhatTheDeviceWouldDraw;

// Stack settings, and Remove from phone on it: asked on the page, and once
// done, the app starts over on the next stack or on a first run.

/** A stack on this phone. Named for this file. */
function aStackOnItsSettingsPage(string $seed, string $name): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($name),
        Address::of('https://192.168.1.47'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** Where a page sent the app, as how and where. Named for this file. */
function whereTheSettingsPageWent(ThisStackOnThisPhone $screen): string
{
    $intent = $screen->getNavigationIntent();

    return $intent instanceof NavigationIntent ? sprintf('%s %s', $intent->type, $intent->uri ?? '') : 'nowhere';
}

it('is served at the stack\'s own path, and titled Stack settings', function (): void {
    $home = aStackOnItsSettingsPage('a', 'The loft');
    $drawn = WhatTheDeviceWouldDraw::by(new APhoneOnItsStackSettings($home)->pageOf($home));

    expect(data_get(NativeRouter::resolve(AStacksScreen::OnThisPhone->forTheStack($home->id())), 'class'))->toBe(ThisStackOnThisPhone::class)
        ->and($drawn->said())->toContain(__('navigation.menu.stack_settings'))
        ->and($drawn->offers())->toContain(__('settings.remove_from_phone'))
        ->and($drawn->said())->not->toContain(__('settings.remove_confirm', ['name' => 'The loft']));
});

it('asks on the page, naming the stack, and keeps it when told to', function (): void {
    $home = aStackOnItsSettingsPage('a', 'The loft');
    $phone = new APhoneOnItsStackSettings($home);
    $page = $phone->pageOf($home);

    $page->askToRemove();
    $asking = WhatTheDeviceWouldDraw::by($page);
    $page->keepTheStack();

    expect($asking->said())->toContain(__('settings.remove_confirm', ['name' => 'The loft']))
        ->and($asking->offers())->toContain(__('settings.remove'))
        ->and($asking->offers())->toContain(__('settings.keep_it'))
        ->and($page->confirmingTheRemoval)->toBeFalse()
        ->and($phone->stacks->configured()->knows($home->id()))->toBeTrue()
        ->and(whereTheSettingsPageWent($page))->toBe('nowhere');
});

it('removes the stack, and starts over on the next stack in the order', function (): void {
    $first = aStackOnItsSettingsPage('a', 'The loft');
    $second = aStackOnItsSettingsPage('b', 'Zolder');
    $phone = new APhoneOnItsStackSettings($first, $second);
    $page = $phone->pageOf($first);

    $page->askToRemove();
    $page->removeTheStack();

    expect(whereTheSettingsPageWent($page))->toBe(sprintf('%s %s', NavigationIntent::RESET, AStacksScreen::Health->forTheStack($second->id())))
        ->and($phone->stacks->configured()->knows($first->id()))->toBeFalse()
        ->and($phone->keychain->keepsAnythingOf($first->id()))->toBeFalse()
        ->and($phone->stacks->configured()->knows($second->id()))->toBeTrue();
});

it('starts over on the first stack where the one removed was the last in the order', function (): void {
    $first = aStackOnItsSettingsPage('a', 'The loft');
    $second = aStackOnItsSettingsPage('b', 'Zolder');
    $page = new APhoneOnItsStackSettings($first, $second)->pageOf($second);

    $page->removeTheStack();

    expect(whereTheSettingsPageWent($page))->toBe(sprintf('%s %s', NavigationIntent::RESET, AStacksScreen::Health->forTheStack($first->id())));
});

it('starts over on a first run where the stack removed was the only one', function (): void {
    $only = aStackOnItsSettingsPage('a', 'The loft');
    $phone = new APhoneOnItsStackSettings($only);
    $page = $phone->pageOf($only);

    $page->removeTheStack();

    expect(whereTheSettingsPageWent($page))->toBe(sprintf('%s %s', NavigationIntent::RESET, AScreenWithoutAStack::TheList->value))
        ->and($phone->stacks->holdsAny())->toBeFalse();
});

it('says nothing was removed where the removal could not begin, and removes nothing', function (): void {
    $home = aStackOnItsSettingsPage('a', 'The loft');
    $phone = new APhoneOnItsStackSettings($home);
    $page = $phone->pageOf($home, journalWrites: false);

    $page->askToRemove();
    $page->removeTheStack();
    $drawn = WhatTheDeviceWouldDraw::by($page);

    expect($drawn->said())->toContain(__('settings.remove_refused', ['name' => 'The loft']))
        ->and($page->confirmingTheRemoval)->toBeFalse()
        ->and(whereTheSettingsPageWent($page))->toBe('nowhere')
        ->and($phone->stacks->configured()->knows($home->id()))->toBeTrue()
        ->and($phone->keychain->keepsAnythingOf($home->id()))->toBeTrue();
});

it('draws once more after its stack is gone, as it does while it hands over', function (): void {
    $home = aStackOnItsSettingsPage('a', 'The loft');
    $page = new APhoneOnItsStackSettings($home)->pageOf($home);
    $page->stack();

    $page->removeTheStack();

    expect((string) json_encode(WhatTheDeviceWouldDraw::tree($page)))->toContain('The loft');
});
