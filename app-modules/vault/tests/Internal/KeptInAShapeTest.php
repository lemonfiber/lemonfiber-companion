<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Internal;

use function expect;
use function it;
use function json_decode;

use Modules\Vault\Internal\KeptInAShape;

it('writes the shape beside the fields, first', function (): void {
    expect(KeptInAShape::written(1, ['stacks' => []]))->toBe(['shape' => 1, 'stacks' => []]);
});

it('knows a record only in the shape it was asked about', function (string $read, bool $known): void {
    expect(KeptInAShape::isIn(json_decode($read, associative: true), 1))->toBe($known);
})->with([
    'the shape asked about' => ['{"shape":1}', true],
    'another shape' => ['{"shape":2}', false],
    'no shape at all' => ['{"stacks":[]}', false],
    'not a record' => ['"shape"', false],
]);

it('knows a record written in a later shape than the one it was asked about', function (string $read, bool $newer): void {
    expect(KeptInAShape::isNewerThan(json_decode($read, associative: true), 1))->toBe($newer);
})->with([
    'a later shape' => ['{"shape":2}', true],
    'the shape asked about' => ['{"shape":1}', false],
    'an earlier shape' => ['{"shape":0}', false],
    'a shape that is not a number' => ['{"shape":"2"}', false],
    'no shape at all' => ['{"stacks":[]}', false],
    'not a record' => ['"shape"', false],
]);
