<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Carried;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\RepairWasConfirmedAgainstAnOldReading;

use function sprintf;

/** The repair the operator said yes to. */
function theRepair(): Remedy
{
    return Remedy::of('restart the indexer');
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
        out: static fn(Remedy $remedy): Code => Code::of(sprintf('out:%s', $remedy->action())),
        refused: static fn(Remedy $remedy, Reading $now): Code => Code::of(sprintf(
            'refused:%s:%s',
            $remedy->action(),
            $now->mayConfirmAnAction() ? 'live' : 'retained',
        )),
    );
}

it('N2-R5 — a repair is carried out only with a confirmation, not with a view', function (): void {
    // The requirement is broken by nobody deciding to break it: a screen
    // renders a finding, the remedy is on it, and a tap handler calls the thing
    // that applies it. Nothing says "this was confirmed" because nothing had
    // to. `carriedOut()` takes one of these, and the only way to make one names
    // the remedy and the reading together.
    $shown = Reading::live(Code::of('the-indexer-is-down'));

    expect(foldCarried(Confirmed::against(theRepair(), $shown)->carriedOut($shown))->shown())
        ->toBe('out:restart the indexer');
});

it('N2-R6 — a repair confirmed against one reading is refused against another', function (): void {
    // The operator agreed to a repair for the situation in front of them. If
    // the stack has moved, that agreement is about something no longer true,
    // and carrying it out applies a decision nobody made to the state it lands
    // on.
    $shown = Reading::live(Code::of('the-indexer-is-down'));
    $now = Reading::live(Code::of('the-indexer-is-down'));

    expect(foldCarried(Confirmed::against(theRepair(), $shown)->carriedOut($now))->shown())
        ->toBe('refused:restart the indexer:live');
});

it('N2-R6 — a re-read that says the same thing is still a different reading', function (): void {
    // Identity rather than equality, deliberately. Treating a re-read with the
    // same values as the same reading would mean deciding, on the operator's
    // behalf, that nothing important changed — which is exactly the judgement
    // the requirement takes away from the app.
    $value = Code::of('the-indexer-is-down');
    $shown = Reading::live($value);
    $sameValueAgain = Reading::live($value);

    expect(foldCarried(Confirmed::against(theRepair(), $shown)->carriedOut($sameValueAgain))->shown())
        ->toStartWith('refused:');
});

it('N2-R6 — the refusal carries what is needed to re-offer', function (): void {
    // "Refuse" and "re-offer" are one requirement, not two. An arm handed only
    // the new reading leaves the screen to remember which repair this was
    // about, and a screen that remembers wrong offers the wrong one.
    $shown = Reading::live(Code::of('was'));
    $now = Reading::retained(Code::of('is'), Instant::atEpochSeconds(1_757_808_000));

    expect(foldCarried(Confirmed::against(theRepair(), $shown)->carriedOut($now))->shown())
        ->toBe('refused:restart the indexer:retained');
});

it('N1-R39 — a retained reading cannot confirm a repair at all', function (): void {
    // A screen that offered confirmation over a reading it knows is old has
    // already broken N1-R39, and there is no half-confirmed repair to carry on
    // with.
    $old = Reading::retained(Code::of('the-indexer-is-down'), Instant::atEpochSeconds(1_757_808_000));

    expect(fn(): Confirmed => Confirmed::against(theRepair(), $old))
        ->toThrow(RepairWasConfirmedAgainstAnOldReading::class);
});
