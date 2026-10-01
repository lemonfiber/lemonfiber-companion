<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARelocation;
use Modules\Kernel\Api\WhereTheDataGoes;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm where the data goes takes, and what it carried there. */
function whereThisDataGoes(WhereTheDataGoes $data): string
{
    return $data->either(
        whereItWas: static fn(): TheWordCarriedOut => new TheWordCarriedOut('where it was'),
        elsewhere: static fn(ARelocation $moved): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s to %s', $moved->was(), $moved->now())),
    )->said;
}

it('goes back where it came from, and says it is not elsewhere', function (): void {
    $data = WhereTheDataGoes::whereItWas();

    expect(whereThisDataGoes($data))->toBe('where it was')
        ->and($data->isElsewhere())->toBeFalse();
});

it('goes elsewhere, with both roots, and says so', function (): void {
    $data = WhereTheDataGoes::elsewhere(ARelocation::from('/srv/old', '/srv/new'));

    expect(whereThisDataGoes($data))->toBe('/srv/old to /srv/new')
        ->and($data->isElsewhere())->toBeTrue();
});
