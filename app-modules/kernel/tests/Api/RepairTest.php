<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\CheckIsUnnamed;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\RepairSaysNothing;
use Modules\Kernel\Api\Undoing;

use function sprintf;

/** A repair offered with everything N2-R4 asks for. */
function anOfferedRepair(Undoing $undoing = Undoing::Possible): Repair
{
    return Repair::offered(
        check: Check::of('indexer-reachable'),
        does: 'restart the indexer',
        effects: Effects::of('downloads pause for about a minute'),
        undoing: $undoing,
    );
}

it('N2-R4 — states what it does, what else it affects and whether it can be undone', function (): void {
    // One closure rather than three accessors. The three clauses are one
    // requirement, and three getters are three chances to call two of them.
    expect(anOfferedRepair()->stated(
        static fn(string $does, Effects $effects, Undoing $undoing): Code => Code::of(sprintf(
            '%s|%s|%s',
            $does,
            implode(',', iterator_to_array($effects, preserve_keys: false)),
            $undoing->value,
        )),
    )->shown())->toBe('restart the indexer|downloads pause for about a minute|possible');
});

it('N2-R4 — a permanent repair says so where a reversible one says otherwise', function (): void {
    // The clause an operator most needs before agreeing. Read through the same
    // fold as everything else, so a screen cannot reach it on its own.
    expect(anOfferedRepair(Undoing::Permanent)->stated(
        static fn(string $does, Effects $effects, Undoing $undoing): Code => Code::of($undoing->value),
    )->shown())->toBe('permanent');
});

it('N2-R4 — a repair that will not say what it does cannot be built', function (): void {
    // Refused at construction rather than rendered short. A repair with a blank
    // `does` renders as a button with no label above a list of consequences,
    // which is the worst version of this screen.
    expect(fn(): Repair => Repair::offered(
        check: Check::of('indexer-reachable'),
        does: '   ',
        effects: Effects::nothingElse(),
        undoing: Undoing::Possible,
    ))->toThrow(RepairSaysNothing::class);
});

it('cannot be built without naming the check it answers', function (): void {
    // Refused a layer down now that the check is a `Check`: without a name
    // nothing can say which finding the repair belongs under, and a repair
    // offered under the wrong finding is worse than one not offered. Asserted
    // here as well as in `CheckTest`, because what this pins is that the
    // refusal is on the road a repair is built along.
    expect(fn(): Repair => Repair::offered(
        check: Check::of(' '),
        does: 'restart the indexer',
        effects: Effects::nothingElse(),
        undoing: Undoing::Possible,
    ))->toThrow(CheckIsUnnamed::class);
});

it('trims what it was given', function (): void {
    expect(Repair::offered(
        check: Check::of('  indexer-reachable  '),
        does: '  restart the indexer  ',
        effects: Effects::nothingElse(),
        undoing: Undoing::Possible,
    )->answers()->shown())->toBe('indexer-reachable');
});

it('publishes the check on its own and nothing else', function (): void {
    // The asymmetry is the design. `answers()` is how a screen files a repair
    // under a finding and teaches it nothing about what the repair would do;
    // the three things an operator reads leave together or not at all.
    //
    // Answered as the type a finding carries, so that *the same check* is one
    // decision rather than a `===` written once per screen.
    expect(anOfferedRepair()->answers()->is(Check::of('indexer-reachable')))->toBeTrue();
});
