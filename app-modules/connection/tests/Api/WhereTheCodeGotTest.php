<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

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
    // N1-R49, and the pairing worth reading twice. An expired code was typed
    // perfectly, so sending its operator to check the characters sends them
    // looking for a mistake that is not there.
    expect(WhereTheCodeGot::Unreadable->saidUnderTheField())
        ->toBe('connection.typed_code_is_unreadable_action')
        ->and(WhereTheCodeGot::Expired->saidUnderTheField())
        ->toBe('connection.pairing_expired_action');
});

it('has something to say about a field nobody has typed in', function (): void {
    // A field with nothing under it is how an operator learns the app has
    // nothing to tell them, which is the state they are in longest.
    expect(WhereTheCodeGot::Waiting->saidUnderTheField())->toBe('connection.type_the_code_hint')
        ->and(WhereTheCodeGot::Comparing->saidUnderTheField())->toBe('connection.code_reads_as_a_stack');
});
