<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\TheCopies;

it('keeps each copy by its name, in the order given', function (): void {
    $copies = TheCopies::named('lemonfiber-20260924-0300-full', 'lemonfiber-20260923-0300-full');

    expect(iterator_to_array($copies, preserve_keys: false))->toBe(['lemonfiber-20260924-0300-full', 'lemonfiber-20260923-0300-full'])
        ->and($copies)->toHaveCount(2);
});

it('N6-R9 — no copy is an answer with nothing in it', function (): void {
    expect(TheCopies::named())->toHaveCount(0);
});

it('N6-R9 — refuses a copy nobody could name', function (): void {
    expect(fn(): TheCopies => TheCopies::named('lemonfiber-20260924-0300-full', ' '))->toThrow(KeepingSaysNothing::class, '`archive`');
});

it('is a list however it was handed its names', function (): void {
    expect(array_keys(iterator_to_array(TheCopies::named(...['first' => 'lemonfiber-20260924-0300-full', 'second' => 'lemonfiber-20260923-0300-full']), preserve_keys: true)))->toBe([0, 1]);
});
