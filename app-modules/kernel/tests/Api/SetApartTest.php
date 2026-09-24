<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AlertSaysNothing;
use Modules\Kernel\Api\AnEventSetApart;
use Modules\Kernel\Api\SetApart;
use Modules\Kernel\Api\WhetherItIsHeard;

it('keeps the stack\'s order', function (): void {
    $kinds = [];

    foreach (SetApart::of(AnEventSetApart::of('update-available', WhetherItIsHeard::Silenced), AnEventSetApart::of('disk-low', WhetherItIsHeard::Heard)) as $event) {
        $kinds[] = $event->kind();
    }

    expect($kinds)->toBe(['update-available', 'disk-low']);
});

it('may set nothing apart, which is an answer', function (): void {
    expect(SetApart::of())->toHaveCount(0);
});

it('refuses one kind set apart twice, whatever each says', function (): void {
    // Two answers about one event: whichever a screen drew, the other is the
    // one the machine acts on.
    expect(fn(): SetApart => SetApart::of(AnEventSetApart::of('disk-low', WhetherItIsHeard::Heard), AnEventSetApart::of('disk-low', WhetherItIsHeard::Silenced)))
        ->toThrow(AlertSaysNothing::class, '`disk-low`');
});

it('is a list however it was handed its events', function (): void {
    $set = SetApart::of(...['first' => AnEventSetApart::of('a', WhetherItIsHeard::Heard), 'second' => AnEventSetApart::of('b', WhetherItIsHeard::Heard)]);

    expect(array_keys(iterator_to_array($set, preserve_keys: true)))->toBe([0, 1])->and($set)->toHaveCount(2);
});
