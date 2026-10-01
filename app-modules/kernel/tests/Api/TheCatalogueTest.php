<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AServiceDropped;
use Modules\Kernel\Api\CatalogueSaysNothing;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheCatalogue;
use Modules\Kernel\Api\WhatAServiceIsFor;
use Modules\Kernel\Api\WhatTheCatalogueSaid;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheServicesAreFor;
use Modules\Kernel\Api\WhatWasDropped;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** A service as the catalogue declares it, any word replaced where a case says. */
function aServiceCatalogued(string $name = 'Sonarr', string $describes = 'Finds television', string $withoutIt = 'No new episodes'): WhatAServiceIsFor
{
    return WhatAServiceIsFor::declared(ServiceId::called('sonarr'), $name, $describes, $withoutIt, HowMuchItMatters::Important);
}

/** What took a dropped service's place, as a line. */
function whatReplacedIt(AServiceDropped $dropped): string
{
    return $dropped->replacement(
        by: static fn(string $by): TheWordCarriedOut => new TheWordCarriedOut($by),
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing'),
    )->said;
}

it('keeps every word of a service less the space around it, and how much it matters', function (): void {
    $service = WhatAServiceIsFor::declared(ServiceId::called('bazarr'), ' Bazarr ', ' Finds subtitles ', ' Nothing has subtitles ', HowMuchItMatters::Optional);

    expect([$service->service()->named(), $service->name(), $service->describes(), $service->withoutIt(), $service->matters()])
        ->toBe(['bazarr', 'Bazarr', 'Finds subtitles', 'Nothing has subtitles', HowMuchItMatters::Optional]);
});

it('refuses a service with any word blank, naming which', function (string $field): void {
    expect(static fn(): WhatAServiceIsFor => match ($field) {
        'name' => aServiceCatalogued(name: ' '),
        'describes' => aServiceCatalogued(describes: ''),
        default => aServiceCatalogued(withoutIt: ' '),
    })->toThrow(CatalogueSaysNothing::class, sprintf('`%s` blank', $field));
})->with(['name', 'describes', 'without_it']);

it('keeps a dropped service, why and when it went, and that nothing took its place', function (): void {
    $dropped = AServiceDropped::went(ServiceId::called('lidarr'), ' 0.9.0 ', ' Music is handled elsewhere ');

    expect([$dropped->service()->named(), $dropped->removedIn(), $dropped->reason(), whatReplacedIt($dropped)])
        ->toBe(['lidarr', '0.9.0', 'Music is handled elsewhere', 'nothing']);
});

it('keeps what took a dropped service\'s place, less the space around it', function (): void {
    $dropped = AServiceDropped::replaced(ServiceId::called('ombi'), '0.8.0', 'Requests moved', ' jellyseerr ');

    expect([$dropped->removedIn(), $dropped->reason(), whatReplacedIt($dropped)])->toBe(['0.8.0', 'Requests moved', 'jellyseerr']);
});

it('refuses a dropped service with any word blank, naming which', function (string $field): void {
    expect(static fn(): AServiceDropped => match ($field) {
        'removed_in' => AServiceDropped::went(ServiceId::called('ombi'), ' ', 'It went'),
        'reason' => AServiceDropped::replaced(ServiceId::called('ombi'), '0.8.0', '', 'jellyseerr'),
        default => AServiceDropped::replaced(ServiceId::called('ombi'), '0.8.0', 'It went', ' '),
    })->toThrow(CatalogueSaysNothing::class, sprintf('`%s` blank', $field));
})->with(['removed_in', 'reason', 'replaced_by']);

it('keeps both lists in the stack\'s order, whatever they were handed under', function (): void {
    $sonarr = aServiceCatalogued();
    $bazarr = aServiceCatalogued('Bazarr');
    $ombi = AServiceDropped::went(ServiceId::called('ombi'), '0.8.0', 'It went');
    $lidarr = AServiceDropped::went(ServiceId::called('lidarr'), '0.9.0', 'It went too');
    $services = WhatTheServicesAreFor::these(...['b' => $sonarr, 'a' => $bazarr]);
    $dropped = WhatWasDropped::these(...['y' => $ombi, 'x' => $lidarr]);
    $catalogue = TheCatalogue::of($services, $dropped);

    expect(iterator_to_array($services, preserve_keys: true))->toBe([$sonarr, $bazarr])
        ->and($services)->toHaveCount(2)
        ->and(iterator_to_array($dropped, preserve_keys: true))->toBe([$ombi, $lidarr])
        ->and($dropped)->toHaveCount(2)
        ->and([$catalogue->services(), $catalogue->dropped()])->toBe([$services, $dropped]);
});

it('answers with the catalogue, the refusal in the stack\'s words, or what stood in the way', function (): void {
    $catalogue = TheCatalogue::of(WhatTheServicesAreFor::these(), WhatWasDropped::these());
    $said = static fn(WhatTheCatalogueSaid $answer): string => $answer->either(
        catalogue: static fn(TheCatalogue $read): TheWordCarriedOut => new TheWordCarriedOut($read === $catalogue ? 'the catalogue' : 'another'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;

    expect($said(WhatTheCatalogueSaid::catalogue($catalogue)))->toBe('the catalogue')
        ->and($said(WhatTheCatalogueSaid::refused(ARefusalInItsWords::said('This stack file could not be read', '', WhatTheRefusalNamed::nothing()))))
        ->toBe('refused: This stack file could not be read')
        ->and($said(WhatTheCatalogueSaid::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
});
