<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARelocation;
use Modules\Kernel\Api\WhereTheDataGoes;

use function sprintf;

/** One line carried out of an arm of where the data goes. */
final readonly class WhichArmTheDataTook
{
    public function __construct(public string $said) {}
}

/** Which arm where the data goes takes, and what it carried there. */
function whereThisDataGoes(WhereTheDataGoes $data): string
{
    return $data->either(
        whereItWas: static fn(): WhichArmTheDataTook => new WhichArmTheDataTook('where it was'),
        elsewhere: static fn(ARelocation $moved): WhichArmTheDataTook => new WhichArmTheDataTook(sprintf('%s to %s', $moved->was(), $moved->now())),
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
