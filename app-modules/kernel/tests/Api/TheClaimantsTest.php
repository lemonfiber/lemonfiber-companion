<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AClaimant;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheClaimants;
use Modules\Kernel\Api\WhoPutItThere;

it('keeps the claimants in the order the core listed them, each with where it came from', function (): void {
    $claimants = TheClaimants::these(
        AClaimant::of(ServiceId::called('plex'), WhoPutItThere::plugin('plex')),
        AClaimant::of(ServiceId::called('jellyfin'), WhoPutItThere::bundled()),
    );
    $named = [];

    foreach ($claimants as $claimant) {
        $named[] = $claimant->service()->named();
    }

    expect($named)->toBe(['plex', 'jellyfin'])
        ->and($claimants->count())->toBe(2)
        ->and(iterator_to_array($claimants, preserve_keys: true)[0]->from())->toEqual(WhoPutItThere::plugin('plex'));
});

it('holds nothing where nothing claims it', function (): void {
    expect(TheClaimants::none()->count())->toBe(0);
});

it('is a list whatever names its claimants were handed under', function (): void {
    $claimants = TheClaimants::these(first: AClaimant::of(ServiceId::called('plex'), WhoPutItThere::bundled()));

    expect(array_keys(iterator_to_array($claimants, preserve_keys: true)))->toBe([0]);
});
