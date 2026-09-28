<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\RefusalSaysNothing;
use Modules\Kernel\Api\WhatTheRefusalNamed;

it('keeps what the stack said, meant and named, less the space around it', function (): void {
    $why = ARefusalInItsWords::said(
        ' This backup is from a newer lemonfiber ',
        ' It may hold configuration this version would not restore correctly. ',
        WhatTheRefusalNamed::as(' the backup is 2.0.0, this is 1.4.0 '),
    );

    expect([$why->summary(), $why->meaning(), $why->named()->forTheOperator()])->toBe([
        'This backup is from a newer lemonfiber',
        'It may hold configuration this version would not restore correctly.',
        'the backup is 2.0.0, this is 1.4.0',
    ]);
});

it('carries a refusal that meant and named nothing more as blank', function (): void {
    $why = ARefusalInItsWords::said('The backup could not be unpacked', ' ', WhatTheRefusalNamed::nothing());

    expect([$why->meaning(), $why->named()->forTheOperator()])->toBe(['', '']);
});

it('refuses a refusal that says nothing, naming the field', function (string $summary): void {
    expect(static fn(): ARefusalInItsWords => ARefusalInItsWords::said($summary, 'Nothing was touched.', WhatTheRefusalNamed::nothing()))
        ->toThrow(RefusalSaysNothing::class, '`summary`');
})->with(['empty' => [''], 'only spaces' => ['  ']]);
