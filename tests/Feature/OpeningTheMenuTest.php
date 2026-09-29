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
use Modules\Operator\Internal\Screens\WhatTheWordsMean;
use Modules\Operator\Internal\TheMenu;
use Modules\Operator\Internal\WhereInTheMenu;
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

it('draws the stack, then every group with every item under it, in the menu\'s order', function (): void {
    $screen = aTabWithTheMenu();
    $drawn = WhatTheDeviceWouldDraw::inTheMenu($screen, $screen->drawerOverride());

    $expected = ['The attic'];
    $items = [];

    foreach (WhereInTheMenu::cases() as $group) {
        $expected[] = __($group->said());

        foreach ($group->holds() as $item) {
            $expected[] = __($item->said());
            $items[] = __($item->said());
        }
    }

    expect(array_values(array_diff($drawn->said(), ['chevron_right'])))->toBe($expected)
        ->and($drawn->offers())->toBe($items)
        ->and($items)->toHaveCount(count(TheMenu::cases()));
});

it('names the control that opens the menu in the operator\'s language', function (): void {
    expect(aTabWithTheMenu()->drawerOverride()->getLabel())->toBe(__('navigation.menu.open'))
        ->and(__('navigation.menu.open'))->toBe('Menu');
});

it('puts the menu control beside the back button on a screen opened on top of a tab, and not on a tab', function (): void {
    expect(aTabWithTheMenu()->drawerOverride()->isBesideBack())->toBeFalse()
        ->and(aScreenTheMenuOpens()->drawerOverride()->isBesideBack())->toBeTrue();
});
