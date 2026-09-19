<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;

it('keeps what the core said in the order it said it', function (): void {
    // The order is the reading. The core writes what happens to what a member
    // asks for, then what their period has left, then when it makes room —
    // and a collection that re-ordered them would be editing an answer it did
    // not write.
    $said = Sentences::of(
        Sentence::of('Anything you ask for goes to whoever looks after this house first.'),
        Sentence::of('You have two left this month.'),
    );

    $shown = [];

    foreach ($said as $sentence) {
        $shown[] = $sentence->shown();
    }

    expect($shown)->toBe([
        'Anything you ask for goes to whoever looks after this house first.',
        'You have two left this month.',
    ]);
});

it('has an empty form, which is an answer rather than a missing one', function (): void {
    expect(iterator_to_array(Sentences::none(), preserve_keys: false))->toBe([]);
});

it('builds a list even from named arguments', function (): void {
    // A variadic collected from named arguments has string keys, and this type
    // publishes itself as holding `int` ones — a template walking it reads the
    // position off the loop. `Sentences::of(first: ...)` is a legal call, so
    // the reindexing is load-bearing rather than tidy.
    $said = Sentences::of(first: Sentence::of('One thing'), then: Sentence::of('Another'));

    expect(array_keys(iterator_to_array($said, preserve_keys: true)))->toBe([0, 1]);
});
