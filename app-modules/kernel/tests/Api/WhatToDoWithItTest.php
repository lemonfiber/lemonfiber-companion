<?php

declare(strict_types=1);

use Modules\Kernel\Api\WhatToDoWithIt;

it('N2-R8 — a start disturbs nothing and the other two do', function (): void {
    // The line is drawn once, here. A screen deciding for itself which verbs
    // are disruptive would eventually ask for a confirmation of a start, which
    // teaches an operator to confirm without reading.
    expect(WhatToDoWithIt::Start->takesSomethingAway())->toBeFalse();
    expect(WhatToDoWithIt::Stop->takesSomethingAway())->toBeTrue();
    expect(WhatToDoWithIt::Restart->takesSomethingAway())->toBeTrue();
});

it('a restart counts as taking something away', function (): void {
    // It comes back, but the gap is real and the household is in it. Said in a
    // case of its own because it is the one somebody would argue about.
    expect(WhatToDoWithIt::Restart->takesSomethingAway())->toBe(WhatToDoWithIt::Stop->takesSomethingAway());
});

it('is shown in the operator\'s words and asked for in lemonfiber\'s', function (): void {
    // The one place the two vocabularies are told apart. A catalogue key comes
    // from the value, which is why the value is the English word — `L7`
    // rebuilds a key from a case's value, and `health.do.up` is a sentence that
    // rule could not find a reader for.
    expect(WhatToDoWithIt::Stop->saidOnTheScreen())->toBe('health.do.stop');
    expect(WhatToDoWithIt::Stop->asked())->toBe('down');
});

it('asks for a start and a stop by the words that surface offers', function (): void {
    // `up` and `down`, which are two of the names on lemonfiber's `OFFERED`
    // list. A verb asked for by the operator's word would be refused by name,
    // in front of somebody holding a phone.
    expect(WhatToDoWithIt::Start->asked())->toBe('up');
    expect(WhatToDoWithIt::Restart->asked())->toBe('restart');
});

it('asks for each verb by a name of its own', function (): void {
    $asked = array_map(
        static fn(WhatToDoWithIt $doing): string => $doing->asked(),
        WhatToDoWithIt::cases(),
    );

    expect(array_unique($asked))->toHaveCount(count(WhatToDoWithIt::cases()));
});

it('gives every verb a key of its own', function (): void {
    $keys = array_map(
        static fn(WhatToDoWithIt $doing): string => $doing->saidOnTheScreen(),
        WhatToDoWithIt::cases(),
    );

    expect(array_unique($keys))->toHaveCount(count(WhatToDoWithIt::cases()));
});
