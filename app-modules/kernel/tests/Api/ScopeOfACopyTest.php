<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnExistingSetup;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatACopyHolds;
use Tests\Support\WhatAScopeSays;

it('says which of the three a copy covers, and carries what each names', function (): void {
    expect(WhatAScopeSays::of(ScopeOfACopy::theWholeStack()))->toBe('whole')
        ->and(WhatAScopeSays::of(ScopeOfACopy::oneService(ServiceId::called('sonarr'))))->toBe('service:sonarr')
        ->and(WhatAScopeSays::of(ScopeOfACopy::anExistingSetup(AnExistingSetup::of('media', WhatACopyHolds::these('/srv/arr', '/srv/plex')))))
        ->toBe('existing:media:/srv/arr,/srv/plex');
});
