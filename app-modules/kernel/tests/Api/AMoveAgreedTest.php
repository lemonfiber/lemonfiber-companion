<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AMove;
use Modules\Kernel\Api\AMoveAgreed;
use Modules\Kernel\Api\MovingInBy;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TheAdoption;
use Modules\Kernel\Api\TheImport;
use Modules\Kernel\Api\ThePortsMoved;
use Modules\Kernel\Api\TheStandingBeside;
use Modules\Kernel\Api\TheReplacement;
use Modules\Kernel\Api\TheRecords;
use Modules\Kernel\Api\ThereIsNothingToAgreeTo;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatWasNamed;

use function sprintf;

it('agrees to the way of moving in the stack staged', function (): void {
    $adopting = AMove::at(Stance::Pending, TheAdoption::of('media', WhatWasNamed::of('back_up'), ''));
    $importing = AMove::at(Stance::Pending, TheImport::of('media', TheRecords::of(), TheRecords::of(), WhatIsUnsupported::none()));

    expect(AMoveAgreed::after($adopting)->by())->toBe(MovingInBy::Adopting)
        ->and(AMoveAgreed::after($importing)->by())->toBe(MovingInBy::Importing);
});

it('carries the offer a replacement named as its yes, and none for a way agreed to by confirming it', function (): void {
    $replacing = AMove::at(Stance::Pending, TheReplacement::of('media', WhatWasNamed::of('would_stop', 'sonarr'), WhatWasNamed::of('stopped'), WhatWasNamed::of('still_running'), '5c3a1d20'));
    $beside = AMove::at(Stance::Pending, TheStandingBeside::of(ThePortsMoved::of(), '/srv/beside.yml'));

    expect(AMoveAgreed::after($replacing)->by())->toBe(MovingInBy::Replacing)
        ->and(AMoveAgreed::after($replacing)->offer())->toBe('5c3a1d20')
        ->and(AMoveAgreed::after($beside)->by())->toBe(MovingInBy::StandingBeside)
        ->and(AMoveAgreed::after($beside)->offer())->toBe('');
});

it('has nothing to agree to where the stack turned it away, found nothing to do, or already did it', function (): void {
    $came = TheAdoption::of('media', WhatWasNamed::of('back_up'), '');

    foreach ([AMove::at(Stance::Unchanged, $came), AMove::at(Stance::Applied, $came), AMove::blocked('Nothing to take over', $came)] as $move) {
        expect(static fn(): AMoveAgreed => AMoveAgreed::after($move))
            ->toThrow(ThereIsNothingToAgreeTo::class, sprintf('`%s`', $move->stance()->value));
    }
});

it('asks for each way of moving in by lemonfiber\'s name for the act', function (): void {
    expect([
        MovingInBy::Adopting->asked(),
        MovingInBy::Importing->asked(),
        MovingInBy::StandingBeside->asked(),
        MovingInBy::Replacing->asked(),
    ])->toBe(['migrate-adopt', 'migrate-import', 'migrate-beside', 'migrate-replace'])
        ->and(MovingInBy::tryFrom('beside'))->toBe(MovingInBy::StandingBeside)
        ->and(MovingInBy::tryFrom('side-by-side'))->toBeNull();
});
