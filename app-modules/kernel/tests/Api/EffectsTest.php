<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\EffectSaysNothing;

it('keeps the order the stack sent', function (): void {
    // The stack lists the largest consequence first. A collection that re-sorted
    // would bury it under something smaller, and the operator agrees to the
    // repair having read the small one.
    $effects = Effects::of('the library is rescanned', 'downloads pause for about a minute');

    expect(iterator_to_array($effects, preserve_keys: false))->toBe([
        'the library is rescanned',
        'downloads pause for about a minute',
    ]);
});

it('affects nothing else, which is an answer rather than an absence', function (): void {
    // A repair that touches nothing besides what it fixes is a real thing to
    // say. Saying it with an empty collection keeps the question answerable without
    // a null anywhere (C2).
    expect(Effects::nothingElse()->count())->toBe(0);
});

it('refuses a blank effect rather than dropping it', function (): void {
    // Dropping would make a stack that sent four consequences render as three,
    // silently — and the operator would agree having read one fewer than they
    // were sent, with nothing on the screen to suggest it.
    expect(fn(): Effects => Effects::of('the library is rescanned', '   '))
        ->toThrow(EffectSaysNothing::class);
});

it('trims what it keeps', function (): void {
    expect(iterator_to_array(iterator: Effects::of('  the library is rescanned  '), preserve_keys: false))
        ->toBe(['the library is rescanned']);
});

it('survives named arguments without gaining string keys', function (): void {
    // `Findings::of()` needs `array_values` for this and this does not, because
    // the loop builds the list rather than passing the variadic through. Pinned
    // so a refactor back to the shorter form cannot quietly reintroduce keys
    // that everything downstream reads by position.
    expect(iterator_to_array(iterator: Effects::of(...['first' => 'a', 'second' => 'b']), preserve_keys: false))->toBe(['a', 'b']);
});
