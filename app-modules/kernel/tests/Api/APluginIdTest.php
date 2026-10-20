<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\PluginSaysNothing;

it('names a plugin by the id it is installed under, trimmed', function (): void {
    expect(APluginId::called(' gluetun ')->named())->toBe('gluetun');
});

it('refuses a plugin named by nothing', function (): void {
    expect(static fn(): APluginId => APluginId::called(' '))->toThrow(PluginSaysNothing::class, '`plugin`');
});
