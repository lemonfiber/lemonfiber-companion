<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\Asked;

it('N4-R4 — a declined permission is not asked for again', function (): void {
    expect(Asked::NotYet->mayAsk())->toBeTrue();
    expect(Asked::Declined->mayAsk())->toBeFalse();
});

it('N4-R4 — never asked and declined are not the same answer', function (): void {
    // They are identical to a caller that only wants to know whether it may
    // proceed, and opposite to one deciding whether to ask. Collapsing them is
    // how an app ends up prompting somebody every time they open a screen,
    // which is the behaviour the requirement exists to forbid.
    expect(Asked::NotYet->mayProceed())->toBeFalse();
    expect(Asked::Declined->mayProceed())->toBeFalse();
    expect(Asked::NotYet->mayAsk())->not->toBe(Asked::Declined->mayAsk());
});

it('N4-R2 — never asked does not mean may proceed', function (): void {
    // Deliberately not the negation of `mayAsk()`. A caller that conflated them
    // would treat "never asked" as "go ahead", which puts the platform prompt
    // in the middle of an action rather than before it — and N4-R2 wants the
    // app's own words first.
    expect(Asked::NotYet->mayProceed())->toBeFalse();
    expect(Asked::Granted->mayProceed())->toBeTrue();
});

it('granted may not be asked for either, for a different reason', function (): void {
    // Both false, and only one of them is a refusal: there is nothing left to
    // ask for. Written as a method rather than a comparison at each call site
    // precisely because the two falses have different reasons.
    expect(Asked::Granted->mayAsk())->toBeFalse();
});

it('is a decision each case answers for itself', function (): void {
    $asking = [];
    $proceeding = [];

    foreach (Asked::cases() as $case) {
        $asking[$case->mayAsk() ? 'yes' : 'no'][] = $case;
        $proceeding[$case->mayProceed() ? 'yes' : 'no'][] = $case;
    }

    expect(count($asking))->toBe(2);
    expect(count($proceeding))->toBe(2);
});
