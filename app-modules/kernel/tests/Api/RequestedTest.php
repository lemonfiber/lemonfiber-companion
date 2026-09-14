<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;

use function sprintf;

/** One request, named so a case can say which row it expects back. */
function aWant(int $number, string $forWhat, Waiting $standing): Wanted
{
    return Wanted::of($number, 'Robin', $forWhat, Size::unknown(), $standing);
}

it('holds what it was given, in the order it was given', function (): void {
    $wanted = Requested::of(
        aWant(1, 'First', Waiting::ForApproval),
        aWant(2, 'Second', Waiting::Getting),
    );

    $titles = [];

    foreach ($wanted as $one) {
        $titles[] = $one->forWhat();
    }

    expect($titles)->toBe(['First', 'Second'])
        ->and($wanted->count())->toBe(2);
});

it('counts nothing when nothing has been asked for', function (): void {
    expect(Requested::none()->count())->toBe(0)
        ->and(Requested::none()->waiting())->toBe(0);
});

it('builds a list even from named arguments', function (): void {
    // A variadic collected from named arguments has string keys, and this
    // collection is handed out again through its iterator — so unlike the
    // reader that fills it, the keys here escape and everything downstream
    // reads by position. `Requested::of(first: ...)` is a legal call, which is
    // what makes the reindexing load-bearing rather than tidy.
    $wanted = Requested::of(
        first: aWant(1, 'First', Waiting::ForApproval),
        then: aWant(2, 'Second', Waiting::Getting),
    );

    expect(array_keys(iterator_to_array($wanted, preserve_keys: true)))->toBe([0, 1]);
});

it('N2-R11 — counts the ones waiting on the operator, not the ones that are not', function (): void {
    // Two waiting and one not, deliberately lopsided. A count that answered
    // *how many are not waiting* is identical wherever every row is waiting or
    // none is — which is most households most weeks, so a fold inverted here
    // would have read correctly for a fortnight and then quietly told an
    // operator there was nothing to decide.
    $wanted = Requested::of(
        aWant(1, 'Waiting', Waiting::ForApproval),
        aWant(2, 'Also waiting', Waiting::ForApproval),
        aWant(3, 'Already coming', Waiting::Getting),
    );

    expect($wanted->waiting())->toBe(2)
        ->and($wanted->count())->toBe(3);
});

it('N2-R11 — asks each row what it stands at rather than deciding for it', function (): void {
    // The line between waiting and not is `Waiting`'s to draw, and every case
    // is put to it here so this count cannot come to disagree with the enum
    // about one of them.
    foreach (Waiting::cases() as $standing) {
        $one = Requested::of(aWant(1, sprintf('A %s request', $standing->value), $standing));

        expect($one->waiting())->toBe($standing->wantsADecision() ? 1 : 0);
    }
});
