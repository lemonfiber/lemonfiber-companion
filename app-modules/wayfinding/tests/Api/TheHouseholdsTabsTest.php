<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Tests\Api;

use function array_map;
use function array_unique;
use function expect;
use function it;

use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheHouseholdsTabs;

it('holds four tabs, Home, Search, Requests and Profile, in that order', function (): void {
    expect(array_map(static fn(TheHouseholdsTabs $tab): AStacksScreen => $tab->screen(), TheHouseholdsTabs::cases()))
        ->toBe([AStacksScreen::Shelf, AStacksScreen::Search, AStacksScreen::Owed, AStacksScreen::Profile]);
});

it('labels each tab from the household catalogue', function (): void {
    expect(array_map(static fn(TheHouseholdsTabs $tab): string => $tab->said(), TheHouseholdsTabs::cases()))
        ->toBe(['household.tabs.home', 'household.tabs.search', 'household.tabs.requests', 'household.tabs.profile']);
});

it('draws each tab with an icon of its own on each platform', function (): void {
    $android = array_map(static fn(TheHouseholdsTabs $tab): string => $tab->glyph(), TheHouseholdsTabs::cases());
    $ios = array_map(static fn(TheHouseholdsTabs $tab): string => $tab->iosGlyph(), TheHouseholdsTabs::cases());

    expect($android)->toBe(['home', 'search', 'playlist_add', 'person'])
        ->and($ios)->toBe(['house', 'magnifyingglass', 'text.badge.plus', 'person.crop.circle'])
        ->and(array_unique($android))->toHaveCount(4)
        ->and(array_unique($ios))->toHaveCount(4);
});
