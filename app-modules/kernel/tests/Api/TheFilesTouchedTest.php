<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\HandoverSaysNothing;
use Modules\Kernel\Api\TheFilesTouched;

it('keeps the files in the order given, less the space around each', function (): void {
    $files = TheFilesTouched::these(' /b.service ', '/a.plist');

    expect(iterator_to_array($files, preserve_keys: true))->toBe(['/b.service', '/a.plist'])
        ->and($files)->toHaveCount(2);
});

it('reads by position whatever the files arrived keyed by', function (): void {
    // Named arguments collected by a variadic arrive keyed by their names.
    $files = TheFilesTouched::these(...['second' => '/b', 'first' => '/a']);

    expect(iterator_to_array($files, preserve_keys: true))->toBe(['/b', '/a']);
});

it('is empty where nothing was touched, which is an answer', function (): void {
    expect(TheFilesTouched::these())->toHaveCount(0);
});

it('refuses a file that says nothing', function (): void {
    expect(static fn(): TheFilesTouched => TheFilesTouched::these('/a', "\t"))
        ->toThrow(HandoverSaysNothing::class, 'which file it touched');
});
