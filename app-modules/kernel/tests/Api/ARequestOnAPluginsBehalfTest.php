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
use Modules\Kernel\Api\WhetherItIsAllowed;

use function sprintf;

it('carries the plugin that declared it, where it goes, why, whether it may, what refuses it and what that costs', function (): void {
    $request = ARequestOnAPluginsBehalf::declaredBy(APluginId::called('plex'), 'plex.tv', 'To claim the media server', WhetherItIsAllowed::SwitchedOff, 'plugin.plex.reach.plex.tv', 'The server cannot be claimed');

    expect([$request->plugin()->named(), $request->destination(), $request->purpose(), $request->allowed(), $request->switch(), $request->cost()])
        ->toBe(['plex', 'plex.tv', 'To claim the media server', WhetherItIsAllowed::SwitchedOff, 'plugin.plex.reach.plex.tv', 'The server cannot be claimed']);
});

it('refuses a request that leaves a word blank, naming which', function (string $field): void {
    $words = ['destination' => 'plex.tv', 'purpose' => 'To claim the media server', 'switch' => 'plugin.plex.reach.plex.tv', 'cost' => 'The server cannot be claimed'];
    $words[$field] = ' ';

    expect(static fn(): ARequestOnAPluginsBehalf => ARequestOnAPluginsBehalf::declaredBy(APluginId::called('plex'), $words['destination'], $words['purpose'], WhetherItIsAllowed::Allowed, $words['switch'], $words['cost']))
        ->toThrow(RequestSaysNothing::class, sprintf('`%s`', $field));
})->with(['destination', 'purpose', 'switch', 'cost']);

it('keeps the requests made for plugins in the stack\'s order, as a list however it was handed them', function (): void {
    $onBehalf = RequestsOnAPluginsBehalf::of(...[
        'first' => ARequestOnAPluginsBehalf::declaredBy(APluginId::called('plex'), 'plex.tv', 'To claim the media server', WhetherItIsAllowed::Allowed, 'plugin.plex.reach.plex.tv', 'The server cannot be claimed'),
        'second' => ARequestOnAPluginsBehalf::declaredBy(APluginId::called('jellyfin'), 'repo.jellyfin.org', 'To fetch its plugins', WhetherItIsAllowed::Allowed, 'plugin.jellyfin.reach.repo.jellyfin.org', 'No plugin of its own can be fetched'),
    ]);
    $named = [];

    foreach ($onBehalf as $request) {
        $named[] = $request->plugin()->named();
    }

    expect($named)->toBe(['plex', 'jellyfin'])
        ->and($onBehalf)->toHaveCount(2)
        ->and(array_keys(iterator_to_array($onBehalf, preserve_keys: true)))->toBe([0, 1]);
});
