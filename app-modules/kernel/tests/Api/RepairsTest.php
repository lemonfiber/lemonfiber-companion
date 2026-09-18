<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\Undoing;

/** A repair the stack offered, stated the way one has to be. */
function oneRepairAbout(string $check): Repair
{
    return Repair::offered(
        check: Check::of($check),
        does: 'restart the indexer',
        effects: Effects::of('downloads pause for about a minute'),
        undoing: Undoing::Possible,
    );
}

it('builds a list even from named arguments', function (): void {
    // A variadic collected from named arguments has string keys, and
    // `Repairs::of(first: ..., then: ...)` is a legal call. `Remedies` reindexes
    // for a different reason — `likeliest()` slices by position — and nothing
    // here reads by position, so the reason this one does is the type: without
    // it the field is not the `array<int, Repair>` it is declared as, and every
    // reader trusting the declaration is trusting something untrue.
    $repairs = Repairs::of(first: oneRepairAbout('one'), then: oneRepairAbout('two'));

    expect(array_keys(iterator_to_array($repairs, preserve_keys: true)))->toBe([0, 1]);
});

it('N2-R4 — offering nothing is an answer a screen reads, not an absence', function (): void {
    expect(Repairs::none()->isEmpty())->toBeTrue()
        ->and(Repairs::none()->count())->toBe(0);
});

it('holds what it was given, and says how many', function (): void {
    $repairs = Repairs::of(oneRepairAbout('one'), oneRepairAbout('two'));

    expect($repairs->isEmpty())->toBeFalse()
        ->and($repairs->count())->toBe(2);
});

it('keeps the order the stack offered them in', function (): void {
    // The engine decided which to put first and a screen re-sorting them is
    // discarding the one thing it cannot work out for itself.
    $answered = [];

    foreach (Repairs::of(oneRepairAbout('first'), oneRepairAbout('second')) as $repair) {
        $answered[] = $repair->answers()->shown();
    }

    expect($answered)->toBe(['first', 'second']);
});

it('says which findings have something on offer under them', function (): void {
    $repairs = Repairs::of(oneRepairAbout('indexer-reachable'));

    expect($repairs->answering(Check::of('indexer-reachable')))->toBeTrue()
        ->and($repairs->answering(Check::of('vpn.egress-match')))->toBeFalse()
        ->and(Repairs::none()->answering(Check::of('indexer-reachable')))->toBeFalse();
});

it('N2-R6 — tells the repair it holds from an equal-looking one it does not', function (): void {
    // By identity rather than by value: two listings can offer a repair that
    // looks the same and be about different moments, and treating them as one
    // would be this app deciding that nothing important changed.
    $held = oneRepairAbout('indexer-reachable');
    $elsewhere = oneRepairAbout('indexer-reachable');

    expect(Repairs::of($held)->holds($held))->toBeTrue()
        ->and(Repairs::of($held)->holds($elsewhere))->toBeFalse()
        ->and(Repairs::none()->holds($held))->toBeFalse();
});
