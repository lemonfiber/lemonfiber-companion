<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AlertSaysNothing;
use Modules\Kernel\Api\AnEventSetApart;
use Modules\Kernel\Api\WhetherItIsHeard;

it('carries the event kind as the stack spells it, and whether it is heard', function (): void {
    $event = AnEventSetApart::of('disk-low', WhetherItIsHeard::Silenced);

    expect($event->kind())->toBe('disk-low')->and($event->heard())->toBe(WhetherItIsHeard::Silenced);
});

it('refuses an event with no kind', function (): void {
    expect(fn(): AnEventSetApart => AnEventSetApart::of(' ', WhetherItIsHeard::Heard))->toThrow(AlertSaysNothing::class, '`kind`');
});
