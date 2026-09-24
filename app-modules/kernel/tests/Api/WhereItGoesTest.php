<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\RequestSaysNothing;
use Modules\Kernel\Api\WhereItGoes;

it('keeps a request\'s destinations in the stack\'s order', function (): void {
    expect(iterator_to_array(WhereItGoes::to('ghcr.io', 'lscr.io', 'docker.io'), preserve_keys: false))->toBe(['ghcr.io', 'lscr.io', 'docker.io'])
        ->and(WhereItGoes::to('ghcr.io', 'lscr.io'))->toHaveCount(2);
});


it('may go nowhere, which is an answer', function (): void {
    expect(WhereItGoes::to())->toHaveCount(0);
});


it('refuses a blank destination among real ones', function (): void {
    expect(fn(): WhereItGoes => WhereItGoes::to('ghcr.io', ' '))->toThrow(RequestSaysNothing::class, '`destination`');
});


it('is a list however it was handed its destinations', function (): void {
    // A spread of named arguments keeps its string keys, and the iterator
    // promises a list.
    expect(array_keys(iterator_to_array(WhereItGoes::to(...['first' => 'ghcr.io', 'second' => 'lscr.io']), preserve_keys: true)))->toBe([0, 1]);
});
