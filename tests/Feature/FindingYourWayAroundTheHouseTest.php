<?php

declare(strict_types=1);

use Modules\Dx\Api\AStandInStack;
use Modules\Dx\Providers\DxServiceProvider;
use Modules\Household\Internal\Screens\LookingForATitle;
use Modules\Household\Internal\WhereTheTabsAreDrawn;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AMemberScreenOpenedOverATab;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatTheRouterHolds;

// A member finds their way by four tabs, Home, Search, Requests and Profile,
// and no side menu. A tab's screen carries the bar, marks its own tab and no
// other, and offers no way back; a screen opened over a tab hides the bar and
// offers the platform's way back.

/**
 * Each tab's screen, built from the container against a stand-in that answers.
 *
 * @return array<string, array{TheHouseholdsTabs, NativeComponent}>
 */
function everyMembersTab(): array
{
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);

    $stack = AStandInStack::Answering->asAStack()->id()->stored();
    $screens = [];

    foreach (TheHouseholdsTabs::cases() as $tab) {
        $screen = app()->make(WhereTheTabsAreDrawn::classOf($tab));

        if ($screen instanceof NativeComponent) {
            $screen->setParams(['stack' => $stack]);
            $screens[$tab->value] = [$tab, $screen];
        }
    }

    return $screens;
}

/**
 * The nodes of one type in a drawn tree, in the order they are drawn.
 *
 * @param array<mixed> $node
 *
 * @return list<array<mixed>>
 */
function theMembersNodesOfType(array $node, string $type): array
{
    $found = ($node['type'] ?? null) === $type ? [$node] : [];
    $children = is_array($node['children'] ?? null) ? $node['children'] : [];

    foreach ($children as $child) {
        if (is_array($child)) {
            $found = [...$found, ...theMembersNodesOfType($child, $type)];
        }
    }

    return $found;
}

/** Whether a member's screen offers the way back, held over this many screens. */
function aMemberScreenOffersAWayBackOver(NativeComponent $screen, int $beneathIt): bool
{
    $home = TheHouseholdsTabs::Home->screen()->forTheStack(AStandInStack::Answering->asAStack()->id());
    WhatTheRouterHolds::over($screen, $home, ...array_fill(0, $beneathIt, $home));

    return method_exists($screen, 'hasAWayBack') && $screen->hasAWayBack() === true;
}

it('draws the four tabs on every tab\'s screen, in their order, marking its own and no other', function (): void {
    $tabs = everyMembersTab();

    expect($tabs)->toHaveCount(4);

    foreach ($tabs as [$tab, $screen]) {
        $items = theMembersNodesOfType(WhatTheDeviceWouldDraw::tree($screen), 'bottom_nav_item');
        $ids = array_map(static fn(array $item): mixed => data_get($item, 'props.id'), $items);
        $lit = array_values(array_filter($items, static fn(array $item): bool => data_get($item, 'props.active') === true));

        expect($ids)->toBe(['home', 'search', 'requests', 'profile'], $tab->value)
            ->and(array_map(static fn(array $item): mixed => data_get($item, 'props.id'), $lit))->toBe([$tab->value], $tab->value);
    }
});

it('titles each tab\'s screen with its tab\'s label, and puts no list of houses in the top bar', function (): void {
    foreach (everyMembersTab() as [$tab, $screen]) {
        // The platform's bar is folded into the root the device draws: its
        // title and whether it carries the way back.
        $tree = WhatTheDeviceWouldDraw::tree($screen);

        expect(data_get($tree, 'props.nav_title'))->toBe(__($tab->said()), $tab->value)
            ->and(data_get($tree, 'props.nav_back'))->toBeFalse($tab->value)
            ->and(theMembersNodesOfType($tree, 'top_bar_title'))->toBe([], $tab->value);
    }
});

it('draws no side menu on any of a member\'s screens', function (): void {
    foreach (everyMembersTab() as [$tab, $screen]) {
        expect(method_exists($screen, 'drawerOverride'))->toBeFalse($tab->value);
    }
});

it('shows the bar and offers no way back on a tab, whatever the router holds beneath it', function (): void {
    foreach (everyMembersTab() as [$tab, $screen]) {
        expect($screen->tabBarOptions())->toBeNull($tab->value);

        foreach ([0, 1, 2] as $beneathIt) {
            expect(aMemberScreenOffersAWayBackOver($screen, $beneathIt))->toBeFalse(sprintf('%s over %d', $tab->value, $beneathIt));
        }
    }
});

it('hides the bar and marks no tab on a member\'s screen no tab draws, and offers the way back only over another', function (): void {
    $screen = new AMemberScreenOpenedOverATab(AroundThePhone::holding(StacksInMemory::holding(AStandInStack::Answering->asAStack())));
    $screen->setParams(['stack' => AStandInStack::Answering->asAStack()->id()->stored()]);

    expect($screen->tabBarOptions())->toBeInstanceOf(TabBarOptions::class)
        ->and($screen->itsTab())->toBeNull()
        ->and(aMemberScreenOffersAWayBackOver($screen, 0))->toBeFalse()
        ->and(aMemberScreenOffersAWayBackOver($screen, 1))->toBeTrue();
});

it('says searching from the phone is coming, in household words, and offers nothing but the tabs', function (): void {
    [, $search] = everyMembersTab()[TheHouseholdsTabs::Search->value];
    $drawn = WhatTheDeviceWouldDraw::by($search);

    expect($search)->toBeInstanceOf(LookingForATitle::class)
        ->and($drawn->said())->toContain(__('household.search_is_coming'), __('household.search_is_coming_action'))
        ->and($drawn->offers())->toBe([]);
});
