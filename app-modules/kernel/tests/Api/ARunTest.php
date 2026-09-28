<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ARun;
use Modules\Kernel\Api\ARunAgreedTo;
use Modules\Kernel\Api\ARunToPutBack;
use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\ThereIsNothingToAgreeTo;
use Modules\Kernel\Api\UndoSaysNothing;
use Modules\Kernel\Api\WhatToDoWithARun;
use Modules\Kernel\Api\WhenItWasMade;

/** One line carried out of an arm. */
final readonly class WhenTheRunWasSaid
{
    public function __construct(public string $said) {}
}

/** A change made at a moment, or at none, going back as far as a case says. */
function aChangeInARun(
    ?int $at,
    string $did = 'Set LIBRARY_PATH',
    HowFarItGoesBack $reversal = HowFarItGoesBack::Whole,
    int $alongside = 1,
): Change {
    return Change::made(
        $did,
        'reconfigure',
        'lemonfiber',
        $at === null ? WhenItWasMade::unreadable() : WhenItWasMade::at(Instant::atEpochSeconds($at)),
        $reversal,
        $alongside,
    );
}

/** When a run was made, as a line: the seconds, the clock not saying, or nothing held. */
function whenTheRunWasMade(ARunToPutBack $run): string
{
    return $run->when(
        made: static fn(WhenItWasMade $when): WhenTheRunWasSaid => $when->either(
            at: static fn(Instant $at): WhenTheRunWasSaid => new WhenTheRunWasSaid((string) $at->epochSeconds()),
            unreadable: static fn(): WhenTheRunWasSaid => new WhenTheRunWasSaid('undated'),
        ),
        nowhere: static fn(): WhenTheRunWasSaid => new WhenTheRunWasSaid('nowhere'),
    )->said;
}

it('keeps a stamp as it was handed, less the space around it', function (): void {
    expect(ARun::stamped(' 1790150000 ')->stamp())->toBe('1790150000');
});

it('refuses a blank stamp, naming the field', function (string $blank): void {
    expect(static fn(): ARun => ARun::stamped($blank))->toThrow(UndoSaysNothing::class, '`at` blank');
})->with(['empty' => [''], 'spaces' => ['  ']]);

it('reads the stamp back off when a change was made, and the clock not saying as nought', function (): void {
    expect(ARun::madeAt(WhenItWasMade::at(Instant::atEpochSeconds(1_790_150_000)))->stamp())->toBe('1790150000')
        ->and(ARun::madeAt(WhenItWasMade::unreadable())->stamp())->toBe('0');
});

it('holds the changes stamped with it and no others', function (): void {
    $run = ARun::stamped('1790150000');

    expect($run->holds(aChangeInARun(1_790_150_000)))->toBeTrue()
        ->and($run->holds(aChangeInARun(1_790_150_001)))->toBeFalse()
        ->and($run->holds(aChangeInARun(null)))->toBeFalse()
        ->and(ARun::stamped('0')->holds(aChangeInARun(null)))->toBeTrue();
});

it('takes from a record only the changes under the stamp, in the record\'s order', function (): void {
    $first = aChangeInARun(1_790_150_000, 'Set LIBRARY_PATH', alongside: 2);
    $other = aChangeInARun(1_790_149_000, 'Set TZ');
    $second = aChangeInARun(1_790_150_000, 'Made /srv/films', alongside: 3);
    $run = TheRecord::reaching('the last ninety days', $first, $other, $second)->theRun(ARun::stamped('1790150000'));

    expect(iterator_to_array($run, preserve_keys: true))->toBe([$first, $second])
        ->and($run)->toHaveCount(2)
        ->and($run->run()->stamp())->toBe('1790150000')
        ->and($run->alongside())->toBe(2)
        ->and(whenTheRunWasMade($run))->toBe('1790150000');
});

it('says a run whose changes the clock could not date is undated', function (): void {
    $run = ARunToPutBack::of(ARun::stamped('0'), aChangeInARun(null));

    expect(whenTheRunWasMade($run))->toBe('undated');
});

it('holds nothing of a stamp the record does not carry, and says so rather than a count', function (): void {
    $run = TheRecord::reaching('the last ninety days', aChangeInARun(1_790_149_000))->theRun(ARun::stamped('1790150000'));

    expect($run)->toHaveCount(0)
        ->and($run->alongside())->toBe(0)
        ->and(whenTheRunWasMade($run))->toBe('nowhere')
        ->and($run->goesBack())->toBeFalse();
});

it('goes back only where no change of it says it cannot', function (bool $goes, HowFarItGoesBack ...$reversals): void {
    $changes = [];

    foreach ($reversals as $reversal) {
        $changes[] = aChangeInARun(1_790_150_000, reversal: $reversal, alongside: 3);
    }

    expect(ARunToPutBack::of(ARun::stamped('1790150000'), ...$changes)->goesBack())->toBe($goes);
})->with([
    'whole' => [true, HowFarItGoesBack::Whole],
    'partial and whole' => [true, HowFarItGoesBack::Partial, HowFarItGoesBack::Whole],
    'none last' => [false, HowFarItGoesBack::Whole, HowFarItGoesBack::Partial, HowFarItGoesBack::None],
    'none first' => [false, HowFarItGoesBack::None, HowFarItGoesBack::Whole],
]);

it('agrees to a run the record shows can go back, carrying its stamp', function (): void {
    $agreed = ARunAgreedTo::by(ARunToPutBack::of(ARun::stamped('1790150000'), aChangeInARun(1_790_150_000)));

    expect($agreed->run()->stamp())->toBe('1790150000');
});

it('refuses to agree to a run the record holds none of, or says cannot go back, naming its stamp', function (ARunToPutBack $shown): void {
    expect(static fn(): ARunAgreedTo => ARunAgreedTo::by($shown))
        ->toThrow(ThereIsNothingToAgreeTo::class, 'the run stamped `1790150000`');
})->with([
    'held none of' => [ARunToPutBack::of(ARun::stamped('1790150000'))],
    'cannot go back' => [ARunToPutBack::of(ARun::stamped('1790150000'), aChangeInARun(1_790_150_000, reversal: HowFarItGoesBack::None))],
]);

it('asks for putting a run back by lemonfiber\'s word for it', function (): void {
    expect(WhatToDoWithARun::PutBack->asked())->toBe('undo');
});
