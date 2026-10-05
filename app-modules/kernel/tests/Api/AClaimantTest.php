<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AClaimant;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhoPutItThere;

it('carries the service that claims a capability and where it came from', function (): void {
    $claimant = AClaimant::of(ServiceId::called('plex'), WhoPutItThere::plugin('plex'));

    expect($claimant->service()->named())->toBe('plex')
        ->and($claimant->from())->toEqual(WhoPutItThere::plugin('plex'));
});
