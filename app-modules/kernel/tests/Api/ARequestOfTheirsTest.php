<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARequestOfTheirs;
use Modules\Kernel\Api\RequestSaysNothing;
use Modules\Kernel\Api\ServiceId;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhatAServiceWasSaidToReach
{
    public function __construct(public string $said) {}
}

/** Which arm a request takes, and what it carried there. */
function whatItReaches(ARequestOfTheirs $request): string
{
    return $request->reaches(
        recorded: static fn(string $destination, string $purpose): WhatAServiceWasSaidToReach => new WhatAServiceWasSaidToReach(sprintf('recorded:%s:%s', $destination, $purpose)),
        unrecorded: static fn(): WhatAServiceWasSaidToReach => new WhatAServiceWasSaidToReach('unrecorded'),
    )->said;
}

it('carries where a recorded service goes and why', function (): void {
    $request = ARequestOfTheirs::recorded(ServiceId::called('sonarr'), 'thetvdb.com', 'Series metadata');

    expect($request->service()->named())->toBe('sonarr')
        ->and(whatItReaches($request))->toBe('recorded:thetvdb.com:Series metadata');
});

it('a recorded service reaching nothing says so with an empty destination', function (): void {
    expect(whatItReaches(ARequestOfTheirs::recorded(ServiceId::called('gluetun'), '', 'Nothing of its own')))
        ->toBe('recorded::Nothing of its own');
});

it('an unrecorded service is its own arm, carrying no destination to be mistaken for none', function (): void {
    // *Reaches nothing* and *nobody knows* are opposite claims.
    expect(whatItReaches(ARequestOfTheirs::unrecorded(ServiceId::called('my-fork'))))->toBe('unrecorded');
});

it('refuses a recorded service with no purpose', function (): void {
    expect(fn(): ARequestOfTheirs => ARequestOfTheirs::recorded(ServiceId::called('sonarr'), 'thetvdb.com', '  '))
        ->toThrow(RequestSaysNothing::class, '`purpose`');
});
