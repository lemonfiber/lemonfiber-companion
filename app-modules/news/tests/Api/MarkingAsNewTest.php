<?php

declare(strict_types=1);

namespace Modules\News\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\MarkingAsNew;
use Modules\News\Internal\NewsOfAStack;

use function str_repeat;

use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\NewsKeptInMemory;

/** The stack whose kinds are chosen. */
function theStackWhoseKindsAreChosen(): StackId
{
    return StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST)));
}

/** Choosing kinds over a seal and a store a test can see into. */
function markingOver(ASealInMemory $seal, NewsKeptInMemory $kept): MarkingAsNew
{
    return new MarkingAsNew(new NewsOfAStack($seal, $kept, FrozenClock::at(Instant::atEpochSeconds(1_790_000_000))));
}

/**
 * Which kinds the stack marks, in the enum's order.
 *
 * @return list<bool>
 */
function whichAreMarked(MarkingAsNew $marking, ?StackId $stack = null): array
{
    $marked = [];

    foreach (KindOfNews::cases() as $kind) {
        $marked[] = $marking->isMarked($stack ?? theStackWhoseKindsAreChosen(), $kind);
    }

    return $marked;
}

it('marks all three kinds until the operator chooses otherwise', function (): void {
    expect(whichAreMarked(markingOver(ASealInMemory::working(), NewsKeptInMemory::empty())))->toBe([true, true, true]);
});

it('stops marking a kind switched off, and marks it again when switched on, on that stack alone', function (): void {
    $marking = markingOver(ASealInMemory::working(), NewsKeptInMemory::empty());

    expect($marking->markNoLonger(theStackWhoseKindsAreChosen(), KindOfNews::Request))->toBeTrue()
        ->and($marking->markNoLonger(theStackWhoseKindsAreChosen(), KindOfNews::Request))->toBeTrue()
        ->and($marking->markNoLonger(theStackWhoseKindsAreChosen(), KindOfNews::Problem))->toBeTrue()
        ->and(whichAreMarked($marking))->toBe([true, false, false])
        ->and(whichAreMarked($marking, StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST)))))->toBe([true, true, true])
        ->and($marking->mark(theStackWhoseKindsAreChosen(), KindOfNews::Request))->toBeTrue()
        ->and($marking->mark(theStackWhoseKindsAreChosen(), KindOfNews::Update))->toBeTrue()
        ->and(whichAreMarked($marking))->toBe([true, true, false]);
});

it('marks every kind where what was kept does not read, and says a choice it cannot keep was not kept', function (): void {
    $seal = ASealInMemory::working();
    $kept = NewsKeptInMemory::empty()->holdsOneALaterBuildWrote($seal->stack(theStackWhoseKindsAreChosen()));

    expect(whichAreMarked(markingOver($seal, $kept)))->toBe([true, true, true])
        ->and(markingOver(ASealInMemory::working(), NewsKeptInMemory::unreachable())->markNoLonger(theStackWhoseKindsAreChosen(), KindOfNews::Update))->toBeFalse()
        ->and(markingOver(ASealInMemory::withNoSecureStorage(), NewsKeptInMemory::empty())->markNoLonger(theStackWhoseKindsAreChosen(), KindOfNews::Update))->toBeFalse();
});

it('marks every kind where the choice was sealed under a key that is gone', function (): void {
    $seal = ASealInMemory::working();
    $kept = NewsKeptInMemory::empty();
    markingOver($seal, $kept)->markNoLonger(theStackWhoseKindsAreChosen(), KindOfNews::Update);
    $sealed = $seal->stack(theStackWhoseKindsAreChosen());
    $seal->losesItsKeys();

    expect(whichAreMarked(markingOver($seal, $kept)))->toBe([true, true, true])
        ->and($kept->forget($sealed)->howMany())->toBe(1);
});

it('lists the kinds a stack marks, in the order they are declared', function (): void {
    $marking = markingOver(ASealInMemory::working(), NewsKeptInMemory::empty());
    $all = $marking->marked(theStackWhoseKindsAreChosen());
    $marking->markNoLonger(theStackWhoseKindsAreChosen(), KindOfNews::Request);

    $after = $marking->marked(theStackWhoseKindsAreChosen());

    expect(iterator_to_array($all, preserve_keys: false))->toBe([KindOfNews::Update, KindOfNews::Request, KindOfNews::Problem])
        ->and(iterator_to_array($after, preserve_keys: false))->toBe([KindOfNews::Update, KindOfNews::Problem])
        ->and($after->include(KindOfNews::Problem))->toBeTrue()
        ->and($after->include(KindOfNews::Request))->toBeFalse();
});

it('names each kind on a screen by a key of its own', function (): void {
    expect(array_map(static fn(KindOfNews $kind): string => $kind->saidOnTheScreen(), KindOfNews::cases()))
        ->toBe(['news.kind.update', 'news.kind.request', 'news.kind.problem']);
});
