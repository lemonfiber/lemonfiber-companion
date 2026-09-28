<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_is_list;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\WhereAServiceEndedUp;
use Modules\Kernel\Api\WhereTheServicesEndedUp;

use function sprintf;

/**
 * Each service, as `name:state`, in the order held.
 *
 * @return list<string>
 */
function whereTheyEndedUp(WhereTheServicesEndedUp $services): array
{
    $said = [];

    foreach ($services as $service) {
        $said[] = sprintf('%s:%s', $service->name(), $service->runs()->value);
    }

    return $said;
}

it('keeps what did not come back, in the stack\'s order, and leaves what did', function (): void {
    $services = WhereTheServicesEndedUp::of(
        WhereAServiceEndedUp::as('Jellyfin', HowAServiceRuns::Healthy),
        WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Failed),
        WhereAServiceEndedUp::as('Radarr', HowAServiceRuns::Running),
        WhereAServiceEndedUp::as('Prowlarr', HowAServiceRuns::Starting),
    );

    expect(whereTheyEndedUp($services->thatDidNotComeBack()))->toBe(['Sonarr:failed', 'Prowlarr:starting'])
        ->and($services->thatDidNotComeBack())->toHaveCount(2)
        ->and($services)->toHaveCount(4)
        ->and(whereTheyEndedUp($services))->toBe(['Jellyfin:healthy', 'Sonarr:failed', 'Radarr:running', 'Prowlarr:starting']);
});

it('is a list whichever spread it was built from', function (): void {
    // A named spread keeps its string keys, and the iterator promises a list.
    $services = WhereTheServicesEndedUp::of(...['a' => WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Failed)]);

    expect(array_is_list(iterator_to_array($services, preserve_keys: true)))->toBeTrue()
        // What is left once the ones that came back are taken out is a list
        // too, rather than the survivors under the positions they had.
        ->and(array_is_list(iterator_to_array(WhereTheServicesEndedUp::of(
            WhereAServiceEndedUp::as('Jellyfin', HowAServiceRuns::Healthy),
            WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Failed),
        )->thatDidNotComeBack(), preserve_keys: true)))->toBeTrue();
});

it('names a service by what an operator reads, and refuses one with no name', function (): void {
    expect(WhereAServiceEndedUp::as('  Sonarr ', HowAServiceRuns::Absent)->name())->toBe('Sonarr')
        ->and(fn(): WhereAServiceEndedUp => WhereAServiceEndedUp::as(' ', HowAServiceRuns::Absent))->toThrow(ServiceIsUnnamed::class);
});
