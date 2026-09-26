<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\APieceOfABundle;
use Modules\Kernel\Api\ThePiecesOfABundle;

it('walks the files in the stack\'s order, numbered from the first however they were handed over', function (): void {
    $pieces = iterator_to_array(ThePiecesOfABundle::of(...[
        'first' => APieceOfABundle::of('diagnosis.txt', 'passed'),
        'second' => APieceOfABundle::of('services.txt', 'running'),
    ]), preserve_keys: true);

    expect(array_keys($pieces))->toBe([0, 1])
        ->and($pieces[0]->name())->toBe('diagnosis.txt')
        ->and($pieces[1]->body())->toBe('running');
});
