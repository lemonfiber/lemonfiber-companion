<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\LineSaysNothing;
use Modules\Kernel\Api\Remarks;

it('keeps the stack\'s sentences in its order', function (): void {
    $remarks = Remarks::of('Measured at night', 'One client did not answer');

    expect(iterator_to_array($remarks, preserve_keys: false))->toBe(['Measured at night', 'One client did not answer'])
        ->and($remarks)->toHaveCount(2)
        ->and(Remarks::of())->toHaveCount(0);
});

it('refuses a blank among real ones', function (): void {
    expect(fn(): Remarks => Remarks::of('Measured at night', ' '))->toThrow(LineSaysNothing::class, '`remark`');
});

it('is a list however it was handed its sentences', function (): void {
    expect(array_keys(iterator_to_array(Remarks::of(...['first' => 'a', 'second' => 'b']), preserve_keys: true)))->toBe([0, 1]);
});
