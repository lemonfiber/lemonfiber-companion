<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Internal;

use function array_keys;
use function array_unique;
use function expect;
use function it;

use Modules\Dx\Internal\WhatAStandInServes;
use Modules\Sdk\Api\EveryRequestThisAppSends;
use Tests\Support\WhatTheContractAccepts;

it('serves every request this app sends, each available', function (): void {
    $declared = [];

    foreach (EveryRequestThisAppSends::listed() as $path) {
        $declared[] = $path->named();
    }

    $served = WhatAStandInServes::everything()['capabilities'];

    expect(array_keys($served))->toBe($declared)
        ->and(array_unique($served))->toBe([$declared[0] => 'available']);
});

it('says so in a declaration the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('CapabilitiesEnvelope', ['api_version' => 1, 'kind' => 'capabilities', 'data' => WhatAStandInServes::declaration()]))
        ->toBe([]);
});
