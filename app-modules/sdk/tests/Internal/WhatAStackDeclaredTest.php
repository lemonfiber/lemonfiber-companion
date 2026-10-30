<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function it;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\WhetherItIsOffered;
use Modules\Sdk\Internal\WhatAStackDeclared;

it('reads a stack too old to say as needing a newer lemonfiber for everything but what an update brings and taking it', function (): void {
    $old = WhatAStackDeclared::tooOldToSay(Session::of('a-session-not-a-secret'), Instant::atEpochSeconds(0));

    expect($old->at(Ability::of(Api::UPDATE_ENDPOINT)))->toBe(WhetherItIsOffered::NotKnown)
        ->and($old->at(Ability::of(Api::action(TakingAnUpdate::named()))))->toBe(WhetherItIsOffered::NotKnown)
        ->and($old->at(Ability::of(Api::STATUS_ENDPOINT)))->toBe(WhetherItIsOffered::NeedsANewerLemonfiber);
});

it('reads a stack it could not ask as offering everything, asked again after a break where one too old to say is held while a screen is open', function (): void {
    $asked = Instant::atEpochSeconds(0);
    $afterABreak = Instant::atEpochSeconds(HowOftenAScreenLooks::AfterABreak->seconds());
    $unasked = WhatAStackDeclared::unasked(Session::of('a-session-not-a-secret'), $asked);
    $old = WhatAStackDeclared::tooOldToSay(Session::of('a-session-not-a-secret'), $asked);

    expect($unasked->at(Ability::of(Api::STATUS_ENDPOINT)))->toBe(WhetherItIsOffered::NotKnown)
        ->and($unasked->isOlderThanABreakAt($afterABreak))->toBeTrue()
        ->and($old->isOlderThanABreakAt($afterABreak))->toBeFalse();
});
