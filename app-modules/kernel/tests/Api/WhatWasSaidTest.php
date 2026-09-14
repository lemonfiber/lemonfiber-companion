<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Said;
use Modules\Kernel\Api\Scrollback;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Stream;
use Modules\Kernel\Api\WhatWasSaid;

/** One word carried out of an `either()` arm. */
final readonly class WhatTheReadSaid
{
    public function __construct(public string $said) {}
}

/** Whichever arm an answer takes, as a word. */
function whatCameBackAboutTheService(WhatWasSaid $answer): string
{
    return $answer->either(
        this_: static fn(Scrollback $scrollback): WhatTheReadSaid
            => new WhatTheReadSaid(sprintf('%d lines', $scrollback->count())),
        met: static fn(Obstacle $why): WhatTheReadSaid => new WhatTheReadSaid($why->value),
    )->said;
}

it('N2-R10 — a window takes the arm that renders lines', function (): void {
    $service = ServiceId::called('gluetun');
    $window = Scrollback::of(
        $service,
        HowManyLines::of(10),
        Said::whenever('tunnel up', $service, Stream::Stdout),
    );

    expect(whatCameBackAboutTheService(WhatWasSaid::this($window)))->toBe('1 lines');
});

it('N1-R10 — an obstacle takes the other arm, carrying which one it was', function (): void {
    expect(whatCameBackAboutTheService(WhatWasSaid::met(Obstacle::StackDidNotAnswer)))
        ->toBe(Obstacle::StackDidNotAnswer->value);
});

it('a silent service is not the same answer as a stack that could not be asked', function (): void {
    // *This service has been silent* is a finding an operator acts on. *Your
    // phone is on the wrong network* is not, and folding them would have the
    // second read as the first.
    $window = Scrollback::of(ServiceId::called('gluetun'), HowManyLines::of(10));

    expect(whatCameBackAboutTheService(WhatWasSaid::this($window)))->toBe('0 lines')
        ->and(whatCameBackAboutTheService(WhatWasSaid::met(Obstacle::DeviceHasNoNetwork)))
        ->toBe(Obstacle::DeviceHasNoNetwork->value);
});
