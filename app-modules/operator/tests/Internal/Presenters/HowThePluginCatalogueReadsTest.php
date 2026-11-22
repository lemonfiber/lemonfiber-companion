<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\ACatalogueEntry;
use Modules\Kernel\Api\ASettingThatReaches;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ThePluginCatalogue;
use Modules\Operator\Internal\Presenters\AgoAsShown;
use Modules\Operator\Internal\Presenters\HowThePluginCatalogueReads;
use Modules\Operator\Internal\ViewModels\ACatalogueEntryAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\ThePluginCatalogueAsShown;

/** The frame's moment. */
function theCataloguesNow(): Instant
{
    return Instant::atEpochSeconds(1_800_000_000);
}

/** Two days before the frame's moment. */
function twoDaysBefore(): Instant
{
    return Instant::atEpochSeconds(1_800_000_000 - (2 * 86_400));
}

/**
 * A catalogue not read, as every state that lists nothing but its sentence draws it.
 *
 * @param array<string, int|string> $filling
 */
function aCatalogueSaying(string $said, array $filling = [], string $remedy = '', string $reach = ''): ThePluginCatalogueAsShown
{
    return new ThePluginCatalogueAsShown(listed: false, said: $said, filling: $filling, remedy: $remedy, entries: [], published: AgoAsShown::saysNothing(), unreadable: 0, reach: $reach, newer: []);
}

it('draws nothing while the catalogue is still to be read', function (): void {
    $shown = new HowThePluginCatalogueReads()->reading();

    expect($shown)->toEqual(aCatalogueSaying(''))
        ->and($shown->isBeingRead())->toBeTrue();
});

it('lists each entry in the stack\'s order, installed or not, with the newer version by the installed plugin\'s id', function (): void {
    $catalogue = ThePluginCatalogue::listed(
        twoDaysBefore(),
        7,
        2,
        ACatalogueEntry::offered('tdarr', 'Tdarr', 'Transcodes what comes in', '2.2.0', 'Files stay as they arrive'),
        ACatalogueEntry::installedHere('bazarr', 'Bazarr', 'Fetches subtitles', '1.4.0', 'Nothing has subtitles', '1.4.0', newer: false),
        ACatalogueEntry::installedHere('kometa', 'Kometa', 'Tidies the library', '2.0.1', 'Collections are kept by hand', '1.9.0', newer: true),
    );

    $shown = new HowThePluginCatalogueReads()->answered($catalogue, theCataloguesNow());

    expect($shown)->toEqual(new ThePluginCatalogueAsShown(
        listed: true,
        said: '',
        filling: [],
        remedy: '',
        entries: [
            new ACatalogueEntryAsShown('tdarr', 'Tdarr', 'Transcodes what comes in', '2.2.0', 'Files stay as they arrive', '', newer: false),
            new ACatalogueEntryAsShown('bazarr', 'Bazarr', 'Fetches subtitles', '1.4.0', 'Nothing has subtitles', '1.4.0', newer: false),
            new ACatalogueEntryAsShown('kometa', 'Kometa', 'Tidies the library', '2.0.1', 'Collections are kept by hand', '1.9.0', newer: true),
        ],
        published: AgoAsShown::since(twoDaysBefore(), theCataloguesNow()),
        unreadable: 2,
        reach: '',
        newer: ['kometa' => '2.0.1'],
    ))
        ->and($shown->published->count)->toBe(2)
        ->and($shown->isBeingRead())->toBeFalse()
        ->and($shown->newerFor('kometa'))->toBe('2.0.1')
        ->and($shown->newerFor('bazarr'))->toBe('')
        ->and($shown->newerFor('tdarr'))->toBe('');
});

it('says a catalogue listing nothing is empty, which a catalogue not read never says', function (): void {
    $shown = new HowThePluginCatalogueReads()->answered(ThePluginCatalogue::listed(twoDaysBefore(), 7, 0), theCataloguesNow());

    expect($shown)->toEqual(new ThePluginCatalogueAsShown(listed: true, said: 'plugins.catalogue.empty', filling: [], remedy: '', entries: [], published: AgoAsShown::since(twoDaysBefore(), theCataloguesNow()), unreadable: 0, reach: '', newer: []))
        ->and($shown->isBeingRead())->toBeFalse();
});

it('says reach is switched off by the setting the stack named, and holds it to turn on', function (): void {
    $shown = new HowThePluginCatalogueReads()->answered(ThePluginCatalogue::switchedOff(ASettingThatReaches::named('LEMONFIBER_REACH_CATALOGUE', 'on', 'config-set')), theCataloguesNow());

    expect($shown)->toEqual(aCatalogueSaying('plugins.catalogue.off', ['key' => 'LEMONFIBER_REACH_CATALOGUE'], reach: 'LEMONFIBER_REACH_CATALOGUE'))
        ->and($shown->isBeingRead())->toBeFalse()
        ->and($shown->newerFor('kometa'))->toBe('');
});

it('says a catalogue that was not reached in the stack\'s words, with its code', function (): void {
    $shown = new HowThePluginCatalogueReads()->answered(ThePluginCatalogue::unreached('catalogue-signature', 'The index signature did not verify'), theCataloguesNow());

    expect($shown)->toEqual(aCatalogueSaying('plugins.catalogue.failed', ['summary' => 'The index signature did not verify', 'code' => 'catalogue-signature']));
});

it('says what stood in the way where the stack could not be asked, as the obstacle says it', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $went = HowTheReadingWent::somethingStopped($why);

    expect(new HowThePluginCatalogueReads()->met($why))->toEqual(aCatalogueSaying($went->met, $went->filling(), $went->remedy))
        ->and($went->met)->not->toBe('');
});
