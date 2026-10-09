<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Tests\Api;

use function array_map;
use function array_unique;
use function expect;
use function it;

use Modules\Kernel\Api\WhichTab;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheTabs;

it('holds four tabs, Health, Services, Updates and Repairs, in that order', function (): void {
    expect(array_map(static fn(TheTabs $tab): AStacksScreen => $tab->screen(), TheTabs::cases()))
        ->toBe([AStacksScreen::Health, AStacksScreen::Services, AStacksScreen::Updates, AStacksScreen::Repairs]);
});

it('keeps each tab as the word the phone keeps for it, and reads that word back as the tab', function (): void {
    foreach (TheTabs::cases() as $tab) {
        expect(TheTabs::kept($tab->word()))->toBe($tab);
    }

    expect(TheTabs::kept(WhichTab::Repairs))->toBe(TheTabs::Repairs);
});

it('labels each tab from the navigation catalogue', function (): void {
    expect(array_map(static fn(TheTabs $tab): string => $tab->said(), TheTabs::cases()))
        ->toBe(['navigation.health', 'navigation.services', 'navigation.updates', 'navigation.repairs']);
});

it('draws each tab with an icon of its own on each platform', function (): void {
    $android = array_map(static fn(TheTabs $tab): string => $tab->glyph(), TheTabs::cases());
    $ios = array_map(static fn(TheTabs $tab): string => $tab->iosGlyph(), TheTabs::cases());

    expect($android)->toBe(['home', 'apps', 'download', 'build'])
        ->and($ios)->toBe(['house', 'square.grid.2x2', 'arrow.down.circle', 'wrench.and.screwdriver'])
        ->and(array_unique($android))->toHaveCount(4)
        ->and(array_unique($ios))->toHaveCount(4);
});
