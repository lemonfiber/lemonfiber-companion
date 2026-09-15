<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\OfferHasNoName;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\Undoing;
use Modules\Kernel\Api\WhatIsOnOffer;

use function sprintf;

/** A repair the stack offered, stated the way `N2-R4` requires. */
function aRepairOf(string $check = 'indexer-reachable'): Repair
{
    return Repair::offered(
        check: Check::of($check),
        does: 'restart the indexer',
        effects: Effects::of('downloads pause for about a minute'),
        undoing: Undoing::Possible,
    );
}

it('N2-R6 — carries the word the engine names the listing by', function (): void {
    // The whole reason the repairs and the word travel together: a yes quotes
    // the listing it answers, and the engine refuses it where the machine has
    // moved. Repairs without the word could only be agreed to in the abstract.
    expect(Offer::of('a-listing-the-engine-named', Repairs::of(aRepairOf()))->named())
        ->toBe('a-listing-the-engine-named');
});

it('N2-R5 — refuses a listing the stack did not name', function (): void {
    // Not a field missing from a listing. A listing nobody can quote is one
    // whose only possible yes is the one with no listing attached — standing
    // consent — which is what this surface must never send.
    expect(fn(): Offer => Offer::of('   ', Repairs::of(aRepairOf())))
        ->toThrow(OfferHasNoName::class);
});

it('takes the word as the stack wrote it, less the space around it', function (): void {
    expect(Offer::of("  a-listing  \n", Repairs::none())->named())->toBe('a-listing');
});

it('N2-R4 — a stack offering nothing is an answer, not a failure to ask', function (): void {
    // Most runs offer none, because most findings are things the operator has
    // to go and do. An empty listing under a perfectly good name is how that
    // arrives, and a screen reads it as nothing to offer here.
    $offer = Offer::of('a-listing', Repairs::none());

    expect($offer->repairs()->isEmpty())->toBeTrue()
        ->and($offer->repairs()->count())->toBe(0);
});

it('keeps the repairs in the order the stack offered them', function (): void {
    // The engine decided which to put first and a screen re-sorting them is
    // discarding the one thing it cannot work out for itself.
    $offer = Offer::of('a-listing', Repairs::of(aRepairOf('first'), aRepairOf('second')));
    $answered = [];

    foreach ($offer->repairs() as $repair) {
        $answered[] = $repair->answers()->shown();
    }

    expect($answered)->toBe(['first', 'second']);
});

it('reads which repairs answer which check, and nothing about what they do', function (): void {
    // The check is the only thing a repair publishes on its own. A screen
    // matching repairs to findings has learned nothing about what any of them
    // would do, which is `Repair::stated()`'s business.
    $repairs = Repairs::of(aRepairOf('indexer-reachable'));

    expect($repairs->answering(Check::of('indexer-reachable')))->toBeTrue()
        ->and($repairs->answering(Check::of('vpn.egress-match')))->toBeFalse();
});

it('N2-R6 — tells one listing from another holding an equal-looking repair', function (): void {
    // By identity rather than by value, which is `Confirmed`'s argument about
    // readings: two listings can offer a repair that looks the same and be
    // about different moments, and treating them as one would be this app
    // deciding on the operator's behalf that nothing important changed.
    $offered = aRepairOf();
    $elsewhere = aRepairOf();

    expect(Repairs::of($offered)->holds($offered))->toBeTrue()
        ->and(Repairs::of($offered)->holds($elsewhere))->toBeFalse();
});

it('N1-R10 — says what the operator met where nobody could ask', function (): void {
    // The same six situations they meet signing in or asking after the machine,
    // rather than a vocabulary of this screen's own.
    $met = WhatIsOnOffer::met(Obstacle::StackDidNotAnswer)->either(
        offered: static fn(Offer $offer): Code => Code::of(sprintf('offered-%s', $offer->named())),
        met: static fn(Obstacle $why): Code => Code::of($why->value),
    );

    expect($met->shown())->toBe(Obstacle::StackDidNotAnswer->value);
});

it('hands the listing over where the stack answered', function (): void {
    $answered = WhatIsOnOffer::offer(Offer::of('a-listing', Repairs::of(aRepairOf())))->either(
        offered: static fn(Offer $offer): Code => Code::of(sprintf(
            '%s|%d',
            $offer->named(),
            $offer->repairs()->count(),
        )),
        met: static fn(Obstacle $why): Code => Code::of($why->value),
    );

    expect($answered->shown())->toBe('a-listing|1');
});
