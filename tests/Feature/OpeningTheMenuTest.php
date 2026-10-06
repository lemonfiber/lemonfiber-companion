<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Whose;
use Modules\News\Api\HowMuchIsNew;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\WhatIsNewOnEveryStack;
use Modules\Operator\Internal\Screens\WhatTheWordsMean;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\WhoTheMenuIsFor;
use Modules\Wayfinding\Internal\TheMenu;
use Modules\Wayfinding\Internal\TheWhatsNewInTheMenu;
use Modules\Wayfinding\Internal\WhereInTheMenu;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;
use Tests\Support\WhatTheRouterHolds;

function theStackWhoseMenuIsOpened(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.77'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

/** A keychain holding a session for the attic, opened for whoever is named, or none. */
function theAtticSignedIntoBy(?Whose $whose): AKeychainInMemory
{
    $keychain = AKeychainInMemory::working();

    if ($whose instanceof Whose) {
        $keychain->keep(theStackWhoseMenuIsOpened()->id(), Session::of('a-session-not-a-secret'), $whose);
    }

    return $keychain;
}

/** A tab's screen: Updates, the root of what its tab draws, on a phone the operator is signed in from unless told otherwise. */
function aTabWithTheMenu(?AKeychainInMemory $keychain = null): HowCurrentThisStackIs
{
    $stack = theStackWhoseMenuIsOpened();
    $keychain ??= theAtticSignedIntoBy(Whose::theOperator());
    $screen = new HowCurrentThisStackIs(AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain), new AppsSettingsThatOpen(), AroundThePhone::listening(), WhatThePhoneKeeps::noUpkeepYet());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A screen the menu opens: the glossary, opened on top of a tab. */
function aScreenTheMenuOpens(): WhatTheWordsMean
{
    $stack = theStackWhoseMenuIsOpened();
    $screen = new WhatTheWordsMean(AStackThatExplainsItsWords::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), AKeychainInMemory::working(), AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('draws the operator the stack, the way to another stack and what is new, every group with every item under it, and the two settings, in the menu\'s order', function (): void {
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

it('draws only the stack, the way to another stack and the two settings where this phone holds no session for it', function (): void {
    $screen = aTabWithTheMenu(theAtticSignedIntoBy(null));
    $drawn = WhatTheDeviceWouldDraw::inTheMenu($screen, $screen->drawerOverride());
    $items = [__('navigation.menu.switch_stack'), __('navigation.menu.stack_settings'), __('navigation.menu.app_settings')];

    expect(array_values(array_diff($drawn->said(), ['chevron_right'])))->toBe(['The attic', ...$items])
        ->and($drawn->offers())->toBe($items);
});

it('draws a member\'s session only the stack, the way to another stack and the two settings, and none of the operator\'s', function (): void {
    // A member's own screens draw no menu, and the operator's are never built
    // for a member's session; the menu still says nothing of the operator's
    // should it be drawn for one.
    $screen = aTabWithTheMenu(theAtticSignedIntoBy(Whose::member('ada')));
    $drawn = WhatTheDeviceWouldDraw::inTheMenu($screen, $screen->drawerOverride());
    $items = [__('navigation.menu.switch_stack'), __('navigation.menu.stack_settings'), __('navigation.menu.app_settings')];

    expect(array_values(array_diff($drawn->said(), ['chevron_right'])))->toBe(['The attic', ...$items])
        ->and($drawn->offers())->toBe($items)
        ->and($drawn->said())->not->toContain(__('navigation.menu.whats_new'));
});

it('reads whose menu it is once, rather than on every frame', function (): void {
    $keychain = theAtticSignedIntoBy(Whose::theOperator());
    $screen = aTabWithTheMenu($keychain);
    $screen->drawerOverride();
    $keychain->forget(theStackWhoseMenuIsOpened()->id());

    expect(WhatTheDeviceWouldDraw::inTheMenu($screen, $screen->drawerOverride())->offers())->toContain(__('navigation.menu.whats_new'))
        ->and($screen->menuIsFor)->toBe(WhoTheMenuIsFor::TheOperator);
});

it('names the control that opens the menu in the operator\'s language', function (): void {
    expect(aTabWithTheMenu()->drawerOverride()->getLabel())->toBe(__('navigation.menu.open'))
        ->and(__('navigation.menu.open'))->toBe('Menu');
});

it('puts the menu control beside the back button on a screen opened on top of a tab, and not on a tab', function (): void {
    $tab = aTabWithTheMenu();
    $opened = aScreenTheMenuOpens();
    $atTheTab = AStacksScreen::Updates->forTheStack(theStackWhoseMenuIsOpened()->id());
    WhatTheRouterHolds::over($tab, $atTheTab);
    WhatTheRouterHolds::over($opened, AStacksScreen::Words->forTheStack(theStackWhoseMenuIsOpened()->id()), $atTheTab);

    expect($tab->drawerOverride()->isBesideBack())->toBeFalse()
        ->and($opened->drawerOverride()->isBesideBack())->toBeTrue();
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

it('opens What\'s new from the menu, on a screen of its own', function (): void {
    expect(data_get(NativeRouter::resolve(new TheWhatsNewInTheMenu(HowMuchIsNew::none())->goes()), 'class'))->toBe(WhatIsNewOnEveryStack::class);
});
