<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ARequestOfTheirs;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheirRequests;
use Modules\Kernel\Api\WhoPutItThere;

it('keeps the services\' requests in the stack\'s order', function (): void {
    $theirs = TheirRequests::of(ARequestOfTheirs::unrecorded(ServiceId::called('b'), WhoPutItThere::bundled()), ARequestOfTheirs::unrecorded(ServiceId::called('a'), WhoPutItThere::bundled()));
    $named = [];

    foreach ($theirs as $request) {
        $named[] = $request->service()->named();
    }

    expect($named)->toBe(['b', 'a'])->and($theirs)->toHaveCount(2);
});

it('is a list however it was handed its requests', function (): void {
    $theirs = TheirRequests::of(...['first' => ARequestOfTheirs::unrecorded(ServiceId::called('a'), WhoPutItThere::bundled()), 'second' => ARequestOfTheirs::unrecorded(ServiceId::called('b'), WhoPutItThere::bundled())]);

    expect(array_keys(iterator_to_array($theirs, preserve_keys: true)))->toBe([0, 1]);
});
