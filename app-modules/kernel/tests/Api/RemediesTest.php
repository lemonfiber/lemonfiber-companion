<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;

it('holds what it was given, in the order it was given', function (): void {
    // The order is the server's judgement about likelihood, and it is the one
    // thing a screen cannot work out for itself.
    $remedies = Remedies::of(
        Remedy::of('Free space on the media drive'),
        Remedy::of('Move the library to another disk'),
    );

    $actions = array_map(
        static fn(Remedy $remedy): string => $remedy->action(),
        iterator_to_array($remedies, preserve_keys: false),
    );

    expect($actions)->toBe(['Free space on the media drive', 'Move the library to another disk']);
});

it('counts what it holds', function (): void {
    expect(Remedies::of(Remedy::of('One'), Remedy::of('Two'))->count())->toBe(2);
});

it('is empty when there is nothing to suggest', function (): void {
    // A problem with no known remedy is what Standing::Unknown is for, and an
    // empty collection says it without a null anywhere.
    expect(Remedies::none()->count())->toBe(0)
        ->and(iterator_to_array(Remedies::none(), preserve_keys: false))->toBe([]);
});

it('offers the likeliest one on its own', function (): void {
    $likeliest = Remedies::of(
        Remedy::of('First'),
        Remedy::of('Second'),
        Remedy::of('Third'),
    )->likeliest();

    expect($likeliest->count())->toBe(1)
        ->and(iterator_to_array($likeliest, preserve_keys: false)[0]->action())->toBe('First');
});

it('offers the only one when there is one', function (): void {
    $likeliest = Remedies::of(Remedy::of('Only'))->likeliest();

    expect($likeliest->count())->toBe(1)
        ->and(iterator_to_array($likeliest, preserve_keys: false)[0]->action())->toBe('Only');
});

it('offers nothing when there is nothing', function (): void {
    expect(Remedies::none()->likeliest()->count())->toBe(0);
});

it('builds a list even from named arguments', function (): void {
    // A variadic collected from named arguments has string keys, and
    // `likeliest()` slices by position. `Remedies::of(likeliest: ...)` is a
    // legal call, so the reindexing is load-bearing rather than tidy.
    $remedies = Remedies::of(likeliest: Remedy::of('First'), then: Remedy::of('Second'));

    expect(array_keys(iterator_to_array($remedies, preserve_keys: true)))->toBe([0, 1]);
});

it('leaves the collection it came from alone', function (): void {
    $remedies = Remedies::of(Remedy::of('First'), Remedy::of('Second'));
    $remedies->likeliest();

    expect($remedies->count())->toBe(2);
});
