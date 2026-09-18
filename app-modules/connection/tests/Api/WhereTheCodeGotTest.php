<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function array_map;
use function array_unique;
use function count;
use function expect;
use function it;

use Modules\Connection\Api\WhereTheCodeGot;

it('says something different under the field for each state it can be in', function (): void {
    // The whole reason there are four states rather than "valid or not": each
    // owes the operator a different sentence, and two states sharing one is the
    // collapse this enum exists to prevent.
    $said = [];

    foreach (WhereTheCodeGot::cases() as $state) {
        $said[] = $state->saidUnderTheField();
    }

    expect(count(array_unique($said)))->toBe(count(WhereTheCodeGot::cases()));
});

it('tells a mistyped code apart from one that expired', function (): void {
    // The pairing worth reading twice. An expired code was typed
    // perfectly, so sending its operator to check the characters sends them
    // looking for a mistake that is not there.
    expect(WhereTheCodeGot::Unreadable->saidUnderTheField())
        ->not->toBe(WhereTheCodeGot::Expired->saidUnderTheField());
});

it('has something to say about a field nobody has typed in', function (): void {
    // A field with nothing under it is how an operator learns the app has
    // nothing to tell them, which is the state they are in longest.
    //
    // Every state, and every one different: the key is built from the case, so
    // what this asserts is that four states are four sentences rather than
    // restating the four stems a second time. `EveryDerivedKeyResolvesTest`
    // holds that each of them is a line the catalogue actually has.
    $said = array_map(
        static fn(WhereTheCodeGot $got): string => $got->saidUnderTheField(),
        WhereTheCodeGot::cases(),
    );

    expect($said)->toHaveCount(count(array_unique($said)))
        ->and($said)->not->toContain('');
});
