<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Tests\Api;

use function array_map;
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
