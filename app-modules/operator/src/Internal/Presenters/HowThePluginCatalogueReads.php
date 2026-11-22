<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ACatalogueEntry;
use Modules\Kernel\Api\ASettingThatReaches;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ThePluginCatalogue;
use Modules\Operator\Internal\ViewModels\ACatalogueEntryAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\ThePluginCatalogueAsShown;

/**
 * The stack's plugin catalogue, as the fields a screen draws.
 *
 * `F2`: data in, view model out. Which version is newer is the stack's
 * verdict, carried as it came; nothing here compares two versions.
 */
final readonly class HowThePluginCatalogueReads
{
    /** Not read yet: on a frame of its own still to come, or while the screen follows an act. */
    public function reading(): ThePluginCatalogueAsShown
    {
        return $this->shown(listed: false, said: '');
    }

    /** What the stack answered of its catalogue, as of the frame's moment. */
    public function answered(ThePluginCatalogue $catalogue, Instant $now): ThePluginCatalogueAsShown
    {
        return $catalogue->either(
            listed: fn(Instant $published, int $serial, int $unreadable, array $entries): ThePluginCatalogueAsShown => $this->listed(AgoAsShown::since($published, $now), $unreadable, $entries),
            switchedOff: fn(ASettingThatReaches $setting): ThePluginCatalogueAsShown => $this->shown(listed: false, said: 'plugins.catalogue.off', filling: ['key' => $setting->key()], reach: $setting->key()),
            unreached: fn(string $code, string $summary): ThePluginCatalogueAsShown => $this->shown(listed: false, said: 'plugins.catalogue.failed', filling: ['summary' => $summary, 'code' => $code]),
        );
    }

    /** Asking met this instead, which is said as the obstacle says it. */
    public function met(Obstacle $why): ThePluginCatalogueAsShown
    {
        $went = HowTheReadingWent::somethingStopped($why);

        return $this->shown(listed: false, said: $went->met, filling: $went->filling(), remedy: $went->remedy);
    }

    /** @param list<ACatalogueEntry> $entries */
    private function listed(AgoAsShown $published, int $unreadable, array $entries): ThePluginCatalogueAsShown
    {
        $rows = [];
        $newer = [];

        foreach ($entries as $entry) {
            $rows[] = new ACatalogueEntryAsShown($entry->id(), $entry->name(), $entry->description(), $entry->version(), $entry->withoutIt(), $entry->installed(), $entry->isNewer());

            if ($entry->isNewer()) {
                $newer[$entry->id()] = $entry->version();
            }
        }

        return new ThePluginCatalogueAsShown(
            listed: true,
            said: $rows === [] ? 'plugins.catalogue.empty' : '',
            filling: [],
            remedy: '',
            entries: $rows,
            published: $published,
            unreadable: $unreadable,
            reach: '',
            newer: $newer,
        );
    }

    /** @param array<string, int|string> $filling */
    private function shown(bool $listed, string $said, array $filling = [], string $remedy = '', string $reach = ''): ThePluginCatalogueAsShown
    {
        return new ThePluginCatalogueAsShown($listed, $said, $filling, $remedy, entries: [], published: AgoAsShown::saysNothing(), unreadable: 0, reach: $reach, newer: []);
    }
}
