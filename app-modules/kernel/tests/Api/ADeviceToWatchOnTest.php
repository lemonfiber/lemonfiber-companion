<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ADeviceToWatchOn;
use Modules\Kernel\Api\AdviceSaysNothing;
use Modules\Kernel\Api\HowWellADeviceIsServed;
use Modules\Kernel\Api\TheDevices;

/** A device with the words given here. */
function aDeviceSaying(string $device, string $client, string $caution, string $instead): ADeviceToWatchOn
{
    return ADeviceToWatchOn::rated($device, $client, HowWellADeviceIsServed::Poor, $caution, $instead);
}

it('keeps everything it was rated with', function (): void {
    $device = ADeviceToWatchOn::rated('An old TV', 'The TV browser', HowWellADeviceIsServed::Poor, 'Subtitles lag', 'A streaming stick');

    expect([$device->device(), $device->client(), $device->support(), $device->caution(), $device->instead()])
        ->toBe(['An old TV', 'The TV browser', HowWellADeviceIsServed::Poor, 'Subtitles lag', 'A streaming stick']);
});

it('takes a caution and an instead that are empty', function (): void {
    $device = aDeviceSaying('An old TV', 'The TV browser', '', '');

    expect([$device->caution(), $device->instead()])->toBe(['', '']);
});

it('refuses a blank device or app, and a caution or instead that is blank rather than empty', function (): void {
    expect(fn(): ADeviceToWatchOn => aDeviceSaying(' ', 'The TV browser', '', ''))->toThrow(AdviceSaysNothing::class, 'arrived with its `device` blank')
        ->and(fn(): ADeviceToWatchOn => aDeviceSaying('An old TV', '', '', ''))->toThrow(AdviceSaysNothing::class, '`client`')
        ->and(fn(): ADeviceToWatchOn => aDeviceSaying('An old TV', 'The TV browser', ' ', ''))->toThrow(AdviceSaysNothing::class, '`caution`')
        ->and(fn(): ADeviceToWatchOn => aDeviceSaying('An old TV', 'The TV browser', '', "\n"))->toThrow(AdviceSaysNothing::class, '`instead`');
});

it('keeps every device in the stack\'s order', function (): void {
    $devices = TheDevices::of(...['first' => aDeviceSaying('An iPhone', 'Jellyfin', '', ''), 'second' => aDeviceSaying('An old TV', 'A browser', '', '')]);
    $named = [];

    foreach ($devices as $device) {
        $named[] = $device->device();
    }

    expect($named)->toBe(['An iPhone', 'An old TV'])
        ->and(array_keys(iterator_to_array($devices, preserve_keys: true)))->toBe([0, 1])
        ->and($devices)->toHaveCount(2);
});

it('says each rating under a key of its own', function (): void {
    expect(HowWellADeviceIsServed::Fallback->saidOnTheScreen())->toBe('stacks.clients.support.fallback');
});
