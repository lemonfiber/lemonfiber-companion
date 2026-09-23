<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\OriginSaysNothing;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhereItComesFrom;
use Modules\Kernel\Api\WhereTheServicesComeFrom;

use function sprintf;

/** One service's origin, named for the service so an order can be read off. */
function theOriginOf(string $service): WhereItComesFrom
{
    return WhereItComesFrom::declared(
        ServiceId::called($service),
        $service,
        sprintf('ghcr.io/example/%s', $service),
        '1.0.0',
        sprintf('https://example.org/%s', $service),
        'MIT',
    );
}

/**
 * Every service a set declares, in the order it holds them.
 *
 * @return list<string>
 */
function theServicesDeclared(WhereTheServicesComeFrom $origins): array
{
    $named = [];

    foreach ($origins as $origin) {
        $named[] = $origin->service()->named();
    }

    return $named;
}

it('keeps the order the stack declares them in', function (): void {
    $origins = WhereTheServicesComeFrom::declaring(theOriginOf('sonarr'), theOriginOf('radarr'), theOriginOf('jellyfin'));

    expect(theServicesDeclared($origins))->toBe(['sonarr', 'radarr', 'jellyfin'])
        ->and($origins)->toHaveCount(3);
});

it('is a list however it was handed its origins', function (): void {
    // A spread of named arguments keeps its string keys, and the iterator
    // promises a list.
    $origins = WhereTheServicesComeFrom::declaring(...['first' => theOriginOf('sonarr'), 'second' => theOriginOf('radarr')]);

    expect(array_keys(iterator_to_array($origins, preserve_keys: true)))->toBe([0, 1]);
});

it('may declare nothing, which is an answer', function (): void {
    expect(WhereTheServicesComeFrom::declaring())->toHaveCount(0);
});

it('refuses one service declared twice', function (): void {
    // Whichever a screen drew, the other would be the one somebody checked.
    expect(fn(): WhereTheServicesComeFrom => WhereTheServicesComeFrom::declaring(theOriginOf('sonarr'), theOriginOf('radarr'), theOriginOf('sonarr')))
        ->toThrow(OriginSaysNothing::class, '`sonarr`');
});
