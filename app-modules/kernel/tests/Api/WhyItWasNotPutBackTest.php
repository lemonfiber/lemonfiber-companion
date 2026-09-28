<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\UndoSaysNothing;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhyItWasNotPutBack;

it('keeps what the stack said, meant and named, less the space around it', function (): void {
    $why = WhyItWasNotPutBack::said(
        ' The run stamped 1790150000 cannot be put back ',
        ' One of its changes, against sonarr, cannot be reversed. ',
        WhatTheRefusalNamed::as(' /srv/sonarr/compose.yaml: permission denied '),
    );

    expect([$why->summary(), $why->meaning(), $why->named()->forTheOperator()])->toBe([
        'The run stamped 1790150000 cannot be put back',
        'One of its changes, against sonarr, cannot be reversed.',
        '/srv/sonarr/compose.yaml: permission denied',
    ]);
});

it('carries a refusal that meant and named nothing more as blank', function (): void {
    $why = WhyItWasNotPutBack::said('Nothing was changed at 1790150000', ' ', WhatTheRefusalNamed::nothing());

    expect([$why->meaning(), $why->named()->forTheOperator()])->toBe(['', '']);
});

it('refuses a refusal that says nothing, naming the field', function (string $summary): void {
    expect(static fn(): WhyItWasNotPutBack => WhyItWasNotPutBack::said($summary, 'Nothing was put back.', WhatTheRefusalNamed::nothing()))
        ->toThrow(UndoSaysNothing::class, '`summary`');
})->with(['empty' => [''], 'only spaces' => ['  ']]);
