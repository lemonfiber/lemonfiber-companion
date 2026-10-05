<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ALink;
use Modules\Kernel\Api\HowItReaches;
use Modules\Kernel\Api\ServiceId;

it('carries the service a link runs from and what it reaches', function (): void {
    $reaches = HowItReaches::byName(ServiceId::called('tdarr'), 'Transcoding runs elsewhere');
    $link = ALink::from(ServiceId::called('jellyfin'), $reaches);

    expect($link->by()->named())->toBe('jellyfin')
        ->and($link->reaches())->toBe($reaches);
});
