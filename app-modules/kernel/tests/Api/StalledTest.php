<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowMuchIsShown;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stalled;
use Modules\Kernel\Api\Stuck;

/** One title carried out of `stated()`, since it must hand back an object. */
final readonly class WhatOneTitleSaid
{
    public function __construct(public string $said) {}
}

/** A stalled item, named so the order can be read back. */
function aStalledItem(string $called): Stuck
{
    return Stuck::at($called, 'radarr', Stage::Searching);
}

/** Every title in a listing, in the order it holds them. */
function titlesIn(Stalled $stalled): string
{
    $rows = [];

    foreach ($stalled as $one) {
        $rows[] = $one->stated(
            static fn(string $title): WhatOneTitleSaid => new WhatOneTitleSaid($title),
        )->said;
    }

    return implode(' | ', $rows);
}

it('holds what the stack listed, in the order it listed it', function (): void {
    // The order is the stack's, which is the order the work was queued in — and
    // the oldest thing stuck is usually the one that has been wrong longest.
    $stalled = Stalled::of(
        HowMuchIsShown::AllOfIt,
        aStalledItem('The first thing'),
        aStalledItem('The second thing'),
    );

    expect(titlesIn($stalled))->toBe('The first thing | The second thing')
        ->and($stalled->count())->toBe(2);
});

it('N2-R9 — carries whether this is the whole of what the stack holds', function (): void {
    $partial = Stalled::of(HowMuchIsShown::SomeOfIt, aStalledItem('The only one shown'));

    expect($partial->howMuchIsShown())->toBe(HowMuchIsShown::SomeOfIt);
});

it('nothing stuck is an answer, and it is the whole answer', function (): void {
    // The answer an operator most wants. It is told apart from a stack that
    // could not be asked by `WhatIsStuck`, not here — and it claims to be
    // complete, because a stack with nothing stuck is not keeping anything
    // back.
    $nothing = Stalled::nothing();

    expect($nothing->count())->toBe(0)
        ->and($nothing->howMuchIsShown())->toBe(HowMuchIsShown::AllOfIt)
        ->and(titlesIn($nothing))->toBe('');
});

it('reads by position, whatever keys the variadic arrived with', function (): void {
    // Named arguments give a variadic string keys, and everything here reads by
    // position — the reindex `Requested::of()` makes for the same reason.
    $stalled = Stalled::of(
        shown: HowMuchIsShown::AllOfIt,
        first: aStalledItem('The first thing'),
        second: aStalledItem('The second thing'),
    );

    expect(titlesIn($stalled))->toBe('The first thing | The second thing');
});
