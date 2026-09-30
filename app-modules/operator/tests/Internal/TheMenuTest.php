<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_unique;
use function array_values;
use function expect;
use function file_get_contents;
use function is_array;
use function it;
use function json_decode;

use const JSON_THROW_ON_ERROR;

use Modules\Operator\Internal\TheMenu;
use Modules\Operator\Internal\WhatIsNotHereYet;
use Modules\Operator\Internal\WhereInTheMenu;

use function sprintf;

/**
 * The names each platform's icon set knows, read from the package that draws them.
 *
 * @return list<string>
 */
function theIconsIn(string $set): array
{
    $read = json_decode((string) file_get_contents(sprintf('%s/../../../../vendor/nativephp/mobile-ui/resources/icons/%s.json', __DIR__, $set)), associative: true, flags: JSON_THROW_ON_ERROR);

    $symbols = is_array($read) && array_key_exists('symbols', $read) && is_array($read['symbols']) ? $read['symbols'] : [];

    return array_values(array_filter($symbols, is_string(...)));
}

it('lists the menu in the five groups, in the order the navigation page names them', function (): void {
    $drawn = array_map(
        static fn(WhereInTheMenu $group): array => [$group->value => array_map(static fn(TheMenu $item): string => $item->value, $group->holds())],
        WhereInTheMenu::cases(),
    );

    expect($drawn)->toBe([
        ['household' => ['requests', 'allowance', 'stuck_downloads', 'follow_a_download']],
        ['access' => ['invite_someone', 'front_door', 'watch_apps', 'passwords']],
        ['machine' => ['storage', 'backups', 'after_a_restart', 'already_installed', 'other_programs', 'about', 'uninstall']],
        ['settings' => ['general', 'quality', 'connections', 'bandwidth', 'outgoing_traffic', 'alerts', 'history', 'sources']],
        ['help' => ['get_help', 'glossary', 'services_explained']],
    ]);
});

it('gives every item an icon each platform has, as well as its label', function (): void {
    $material = theIconsIn('material-icons');
    $symbols = theIconsIn('sf-symbols');

    foreach (TheMenu::cases() as $item) {
        expect($material)->toContain($item->glyph())
            ->and($symbols)->toContain($item->iosGlyph())
            ->and($item->said())->toBe(sprintf('navigation.menu.%s', $item->value));
    }
});

it('opens a screen of one stack from every item, and no two items open the same one', function (): void {
    $opened = array_map(static fn(TheMenu $item): string => $item->screen()->name, TheMenu::cases());

    expect($opened)->toBe(array_values(array_unique($opened)));

    foreach (TheMenu::cases() as $item) {
        expect($item->screen()->alsoNeedsAService())->toBeFalse();
    }
});

it('gives What\'s new and the two settings an icon each platform has, and a label of their own', function (): void {
    $material = theIconsIn('material-icons');
    $symbols = theIconsIn('sf-symbols');

    foreach (WhatIsNotHereYet::cases() as $item) {
        expect($material)->toContain($item->glyph())
            ->and($symbols)->toContain($item->iosGlyph());
    }

    expect(WhatIsNotHereYet::WhatsNew->said())->toBe('navigation.menu.whats_new')
        ->and(WhatIsNotHereYet::AppSettings->goes())->toBe('/not-yet/app_settings');
});

it('names each group by a key of its own', function (): void {
    expect(WhereInTheMenu::Access->said())->toBe('navigation.groups.access');
});
