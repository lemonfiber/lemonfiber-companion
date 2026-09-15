<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Carried;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\RepairWasConfirmedAgainstAnOldReading;
use Modules\Kernel\Api\RepairWasNotInThatOffer;
use Modules\Kernel\Api\Undoing;

use function sprintf;

/** The repair the operator said yes to, offered the way N2-R4 requires. */
function theRepair(): Repair
{
    return Repair::offered(
        check: Check::of('indexer-reachable'),
        does: 'restart the indexer',
        effects: Effects::of('downloads pause for about a minute'),
        undoing: Undoing::Possible,
    );
}

/**
 * The listing that repair was offered in.
 *
 * Takes the repair rather than building its own, because `N2-R6` turns on the
 * two being the same object: a listing holding an equal-looking repair is a
 * different listing about a different moment.
 */
function theOfferHolding(Repair $repair): Offer
{
    return Offer::of('a-listing-the-engine-named', Repairs::of($repair));
}

/**
 * Both branches, each naming itself and everything it was handed.
 *
 * Written once so that neither arm can quietly stop using an argument — an arm
 * that ignored what it was given would still pass a test that only asked which
 * side ran.
 */
function foldCarried(Carried $carried): Code
{
    return $carried->either(
        out: static fn(Repair $repair): Code => Code::of(sprintf('out:%s', $repair->answers()->shown())),
        refused: static fn(Repair $repair, Reading $now): Code => Code::of(sprintf(
            'refused:%s:%s',
            $repair->answers()->shown(),
            $now->mayConfirmAnAction() ? 'live' : 'retained',
        )),
    );
}

it('N2-R5 — a repair is carried out only with a confirmation, not with a view', function (): void {
    // The requirement is broken by nobody deciding to break it: a screen
    // renders a finding, the repair is on it, and a tap handler calls the thing
    // that applies it. Nothing says "this was confirmed" because nothing had
    // to. `carriedOut()` takes one of these, and the only way to make one names
    // the repair and the reading together.
    $shown = Reading::live(Code::of('the-indexer-is-down'));

    $repair = theRepair();

    expect(foldCarried(Confirmed::against($repair, theOfferHolding($repair), $shown)->carriedOut($shown))->shown())
    ->toBe('out:indexer-reachable');
});

it('N2-R6 — a repair confirmed against one reading is refused against another', function (): void {
    // The operator agreed to a repair for the situation in front of them. If
    // the stack has moved, that agreement is about something no longer true,
    // and carrying it out applies a decision nobody made to the state it lands
    // on.
    $shown = Reading::live(Code::of('the-indexer-is-down'));
    $now = Reading::live(Code::of('the-indexer-is-down'));

    $repair = theRepair();

    expect(foldCarried(Confirmed::against($repair, theOfferHolding($repair), $shown)->carriedOut($now))->shown())
    ->toBe('refused:indexer-reachable:live');
});

it('N2-R6 — a re-read that says the same thing is still a different reading', function (): void {
    // Identity rather than equality, deliberately. Treating a re-read with the
    // same values as the same reading would mean deciding, on the operator's
    // behalf, that nothing important changed — which is exactly the judgement
    // the requirement takes away from the app.
    $value = Code::of('the-indexer-is-down');
    $shown = Reading::live($value);
    $sameValueAgain = Reading::live($value);

    $repair = theRepair();

    expect(foldCarried(Confirmed::against($repair, theOfferHolding($repair), $shown)->carriedOut($sameValueAgain))->shown())
    ->toStartWith('refused:');
});

it('N2-R6 — the refusal carries what is needed to re-offer', function (): void {
    // "Refuse" and "re-offer" are one requirement, not two. An arm handed only
    // the new reading leaves the screen to remember which repair this was
    // about, and a screen that remembers wrong offers the wrong one.
    $shown = Reading::live(Code::of('was'));
    $now = Reading::retained(Code::of('is'), Instant::atEpochSeconds(1_757_808_000));

    $repair = theRepair();

    expect(foldCarried(Confirmed::against($repair, theOfferHolding($repair), $shown)->carriedOut($now))->shown())
    ->toBe('refused:indexer-reachable:retained');
});

it('N1-R39 — a retained reading cannot confirm a repair at all', function (): void {
    // A screen that offered confirmation over a reading it knows is old has
    // already broken N1-R39, and there is no half-confirmed repair to carry on
    // with.
    $old = Reading::retained(Code::of('the-indexer-is-down'), Instant::atEpochSeconds(1_757_808_000));

    $repair = theRepair();

    expect(fn(): Confirmed => Confirmed::against($repair, theOfferHolding($repair), $old))
    ->toThrow(RepairWasConfirmedAgainstAnOldReading::class);
});

it('N2-R6 — refuses a yes that quotes a listing the repair was never in', function (): void {
    // Not defensive. The repair and the listing arrive together, so a screen
    // reaching this has lost track of which listing a button belonged to — and
    // the engine would see a listing it recognises and a repair it was asked
    // for, and carry out something the operator agreed to under a different set
    // of consequences.
    $repair = theRepair();
    $elsewhere = theRepair();

    expect(fn(): Confirmed => Confirmed::against(
        $repair,
        theOfferHolding($elsewhere),
        Reading::live(theRepair()),
    ))->toThrow(RepairWasNotInThatOffer::class, 'indexer-reachable');
});

it('N2-R6 — quotes the listing it was agreed to, for the engine to check', function (): void {
    // The word is carried out of here and nowhere else reads it. The engine is
    // where `N2-R6` is finally settled, because it can see whether the machine
    // has moved and this app cannot.
    $repair = theRepair();

    expect(Confirmed::against($repair, theOfferHolding($repair), Reading::live($repair))->quoting())
        ->toBe('a-listing-the-engine-named');
});

it('names the repair it was agreed to, for asking the engine about it', function (): void {
    $repair = theRepair();

    expect(Confirmed::against($repair, theOfferHolding($repair), Reading::live($repair))->repair())
        ->toBe($repair);
});
