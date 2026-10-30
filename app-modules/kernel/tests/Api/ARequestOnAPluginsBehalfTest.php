<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARequestOnAPluginsBehalf;
use Modules\Kernel\Api\RequestSaysNothing;
use Modules\Kernel\Api\RequestsOnAPluginsBehalf;

it('carries the plugin that declared it, where it goes and why', function (): void {
    $request = ARequestOnAPluginsBehalf::declaredBy(APluginId::called('plex'), 'plex.tv', 'To claim the media server');

    expect([$request->plugin()->named(), $request->destination(), $request->purpose()])->toBe(['plex', 'plex.tv', 'To claim the media server']);
});

it('refuses a request that will not say where it goes or why, naming which', function (): void {
    expect(static fn(): ARequestOnAPluginsBehalf => ARequestOnAPluginsBehalf::declaredBy(APluginId::called('plex'), ' ', 'To claim the media server'))
        ->toThrow(RequestSaysNothing::class, '`destination`')
        ->and(static fn(): ARequestOnAPluginsBehalf => ARequestOnAPluginsBehalf::declaredBy(APluginId::called('plex'), 'plex.tv', ' '))
        ->toThrow(RequestSaysNothing::class, '`purpose`');
});

it('keeps the requests made for plugins in the stack\'s order, as a list however it was handed them', function (): void {
    $onBehalf = RequestsOnAPluginsBehalf::of(...[
        'first' => ARequestOnAPluginsBehalf::declaredBy(APluginId::called('plex'), 'plex.tv', 'To claim the media server'),
        'second' => ARequestOnAPluginsBehalf::declaredBy(APluginId::called('jellyfin'), 'repo.jellyfin.org', 'To fetch its plugins'),
    ]);
    $named = [];

    foreach ($onBehalf as $request) {
        $named[] = $request->plugin()->named();
    }

    expect($named)->toBe(['plex', 'jellyfin'])
        ->and($onBehalf)->toHaveCount(2)
        ->and(array_keys(iterator_to_array($onBehalf, preserve_keys: true)))->toBe([0, 1]);
});
