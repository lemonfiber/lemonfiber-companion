<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\NotInThisVersionYet;
use Modules\Operator\Internal\Screens\WhatTheWordsMean;
use Modules\Operator\Internal\TheMenu;
use Modules\Operator\Internal\WhatIsNotHereYet;
use Modules\Operator\Internal\WhereInTheMenu;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

function theStackWhoseMenuIsOpened(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.77'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

/** A tab's screen: Updates, the root of what its tab draws. */
function aTabWithTheMenu(): HowCurrentThisStackIs
{
    $stack = theStackWhoseMenuIsOpened();
    $screen = new HowCurrentThisStackIs(AStackThatKeepsCurrent::met(Obstacle::DeviceHasNoNetwork), AKeychainInMemory::working(), AroundThePhone::holding(StacksInMemory::holding($stack)));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A screen the menu opens: the glossary, opened on top of a tab. */
function aScreenTheMenuOpens(): WhatTheWordsMean
{
    $stack = theStackWhoseMenuIsOpened();
    $screen = new WhatTheWordsMean(AStackThatExplainsItsWords::met(Obstacle::DeviceHasNoNetwork), AKeychainInMemory::working(), AroundThePhone::holding(StacksInMemory::holding($stack)));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('draws the stack, the way to another stack and what is new, every group with every item under it, and the two settings, in the menu\'s order', function (): void {
    $screen = aTabWithTheMenu();
    $drawn = WhatTheDeviceWouldDraw::inTheMenu($screen, $screen->drawerOverride());

    $expected = ['The attic', __('navigation.menu.switch_stack'), __('navigation.menu.whats_new')];
    $items = [__('navigation.menu.switch_stack'), __('navigation.menu.whats_new')];

    foreach (WhereInTheMenu::cases() as $group) {
        $expected[] = __($group->said());

        foreach ($group->holds() as $item) {
            $expected[] = __($item->said());
            $items[] = __($item->said());
        }
    }

    $expected = [...$expected, __('navigation.menu.stack_settings'), __('navigation.menu.app_settings')];
    $items = [...$items, __('navigation.menu.stack_settings'), __('navigation.menu.app_settings')];

    expect(array_values(array_diff($drawn->said(), ['chevron_right'])))->toBe($expected)
        ->and($drawn->offers())->toBe($items)
        ->and($items)->toHaveCount(count(TheMenu::cases()) + 4);
});

it('names the control that opens the menu in the operator\'s language', function (): void {
    expect(aTabWithTheMenu()->drawerOverride()->getLabel())->toBe(__('navigation.menu.open'))
        ->and(__('navigation.menu.open'))->toBe('Menu');
});

it('puts the menu control beside the back button on a screen opened on top of a tab, and not on a tab', function (): void {
    expect(aTabWithTheMenu()->drawerOverride()->isBesideBack())->toBeFalse()
        ->and(aScreenTheMenuOpens()->drawerOverride()->isBesideBack())->toBeTrue();
});

it('draws the bar on a tab with that tab marked, and hides it on a screen the menu opens', function (): void {
    $tab = aTabWithTheMenu();
    $opened = aScreenTheMenuOpens();

    expect($tab->tabBarOptions())->toBeNull()
        ->and($tab->itsTab()?->value)->toBe('updates')
        ->and($opened->tabBarOptions()?->hidden)->toBeTrue()
        ->and($opened->itsTab())->toBeNull();
});


/**
 * Every node of a frame, the frame's own first.
 *
 * @param array<mixed> $node
 *
 * @return list<array<mixed>>
 */
function everyNodeOf(array $node): array
{
    $found = [$node];
    $children = array_key_exists('children', $node) && is_array($node['children']) ? $node['children'] : [];

    foreach ($children as $child) {
        $found = [...$found, ...(is_array($child) ? everyNodeOf($child) : [])];
    }

    return $found;
}

it('marks one tab in the bar, the one the screen is under', function (): void {
    $tabs = array_values(array_filter(
        everyNodeOf(WhatTheDeviceWouldDraw::tree(aTabWithTheMenu())),
        static fn(array $node): bool => array_key_exists('type', $node) && $node['type'] === 'bottom_nav_item',
    ));

    expect(array_column(array_column($tabs, 'props'), 'active', 'id'))
        ->toBe(['health' => false, 'services' => false, 'updates' => true, 'repairs' => false]);
});

it('keeps What\'s new and the two settings, and opening one says it is not in this version yet', function (): void {
    foreach (WhatIsNotHereYet::cases() as $item) {
        $screen = new NotInThisVersionYet();
        $screen->setParams(['what' => data_get(NativeRouter::resolve($item->goes()), 'params.what')]);

        expect($screen->which())->toBe($item)
            ->and(data_get(NativeRouter::resolve($item->goes()), 'class'))->toBe(NotInThisVersionYet::class)
            ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('device.not_in_this_version'))
            ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([__('connection.back_to_your_stacks')]);
    }
});

it('titles a placeholder it does not know as What\'s new', function (): void {
    $screen = new NotInThisVersionYet();
    $screen->setParams(['what' => 'the-weather']);

    expect($screen->which())->toBe(WhatIsNotHereYet::WhatsNew);
});
