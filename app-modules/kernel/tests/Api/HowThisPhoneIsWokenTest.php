<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\ADistributorToInstall;
use Modules\Kernel\Api\AnEndpoint;
use Modules\Kernel\Api\AnEndpointGiven;
use Modules\Kernel\Api\APushName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AWakeIsUnfit;
use Modules\Kernel\Api\AWakeToRegister;
use Modules\Kernel\Api\AWakingKey;
use Modules\Kernel\Api\AWakingSecret;
use Modules\Kernel\Api\HowThisPhoneIsWoken;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatRegisteringAWakeCameTo;
use Modules\Kernel\Api\WhatRemovingAWakeCameTo;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhyNoEndpoint;

use function print_r;
use function sprintf;
use function str_contains;
use function str_repeat;

use Tests\Support\TheWordCarriedOut;

function aWake(): AWakeToRegister
{
    return new AWakeToRegister(
        APushName::of('galaxy-a51-7f3a'),
        AnEndpoint::of('https://ntfy.sh/upAbC?up=1'),
        AWakingKey::of(sprintf("\x04%s", str_repeat("\x01", 64))),
        AWakingSecret::of(str_repeat('s', 16)),
    );
}

/** How the phone is woken, as one word. */
function howItIsWoken(HowThisPhoneIsWoken $woken): string
{
    return $woken->either(
        off: static fn(): TheWordCarriedOut => new TheWordCarriedOut('off'),
        on: static fn(ADistributor $through, AWakeToRegister $wake): TheWordCarriedOut => new TheWordCarriedOut(sprintf('on through %s at %s', $through->called(), $wake->endpoint->address())),
        owed: static fn(APushName $name): TheWordCarriedOut => new TheWordCarriedOut(sprintf('owed %s', $name->shown())),
    )->said;
}

it('is off, on through a distributor, or off with a removal owed to the stack', function (): void {
    $on = HowThisPhoneIsWoken::on(ADistributor::of('io.heckel.ntfy', 'ntfy'), aWake());
    $owed = HowThisPhoneIsWoken::owed(APushName::of('galaxy-a51-7f3a'));

    expect(howItIsWoken(HowThisPhoneIsWoken::off()))->toBe('off')
        ->and(howItIsWoken($on))->toBe('on through ntfy at https://ntfy.sh/upAbC?up=1')
        ->and(howItIsWoken($owed))->toBe('owed galaxy-a51-7f3a')
        ->and([HowThisPhoneIsWoken::off()->isOn(), $on->isOn(), $owed->isOn()])->toBe([false, true, false])
        ->and([HowThisPhoneIsWoken::off()->owesARemoval(), $on->owesARemoval(), $owed->owesARemoval()])->toBe([false, false, true])
        ->and([count(HowThisPhoneIsWoken::off()->through()), count($on->through()), count($owed->through())])->toBe([0, 1, 0]);
});

it('moves a wake to another endpoint and keeps the rest, and prints none of its keys or its endpoint', function (): void {
    $moved = aWake()->at(AnEndpoint::of('https://ntfy.sh/upNew?up=1'));
    $printed = print_r(aWake(), return: true);

    expect($moved->endpoint->address())->toBe('https://ntfy.sh/upNew?up=1')
        ->and($moved->name->shown())->toBe('galaxy-a51-7f3a')
        ->and($moved->endpoint->is(aWake()->endpoint))->toBeFalse()
        ->and(str_contains($printed, 'ntfy.sh') || str_contains($printed, 'ssss'))->toBeFalse()
        ->and(str_contains($printed, 'galaxy-a51-7f3a'))->toBeTrue()
        ->and(str_contains(print_r(AnEndpoint::of('https://ntfy.sh/upAbC?up=1'), return: true), 'ntfy.sh'))->toBeFalse()
        ->and(static fn(): AnEndpoint => AnEndpoint::of(' '))->toThrow(AWakeIsUnfit::class);
});

it('gives an endpoint with its key, or why there is none, and names the line each reason is said with', function (): void {
    $given = AnEndpointGiven::given(AnEndpoint::of('https://ntfy.sh/upAbC?up=1'), aWake()->key);
    $said = static fn(AnEndpointGiven $one): string => $one->either(
        given: static fn(AnEndpoint $endpoint): TheWordCarriedOut => new TheWordCarriedOut($endpoint->address()),
        not: static fn(WhyNoEndpoint $why): TheWordCarriedOut => new TheWordCarriedOut($why->saidOnTheScreen()),
    )->said;

    expect($said($given))->toBe('https://ntfy.sh/upAbC?up=1')
        ->and($said(AnEndpointGiven::not(WhyNoEndpoint::NoAnswer)))->toBe('waking.no_endpoint.no_answer')
        ->and(WhyNoEndpoint::said('dropped', WhyNoEndpoint::Refused))->toBe(WhyNoEndpoint::Dropped)
        ->and(WhyNoEndpoint::said('perhaps', WhyNoEndpoint::Refused))->toBe(WhyNoEndpoint::Refused);
});

it('reads what a stack said to registering and removing as kept, refused in its words, or not reached', function (): void {
    $words = ARefusalInItsWords::said('Another push endpoint is registered under that name.', '', WhatTheRefusalNamed::nothing());
    $met = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $registering = static fn(WhatRegisteringAWakeCameTo $came): string => $came->either(
        registered: static fn(AnEndpoint $held): TheWordCarriedOut => new TheWordCarriedOut($held->address()),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut($why->summary()),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
    $removing = static fn(WhatRemovingAWakeCameTo $came): string => $came->either(
        removed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('removed'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut($why->summary()),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;

    expect($registering(WhatRegisteringAWakeCameTo::registered(AnEndpoint::of('https://ntfy.sh/upAbC?up=1'))))->toBe('https://ntfy.sh/upAbC?up=1')
        ->and($registering(WhatRegisteringAWakeCameTo::refused($words)))->toBe($words->summary())
        ->and($registering(WhatRegisteringAWakeCameTo::met($met)))->toBe(KindOfObstacle::StackDidNotAnswer->value)
        ->and($removing(WhatRemovingAWakeCameTo::removed()))->toBe('removed')
        ->and($removing(WhatRemovingAWakeCameTo::refused($words)))->toBe($words->summary())
        ->and($removing(WhatRemovingAWakeCameTo::met($met)))->toBe(KindOfObstacle::StackDidNotAnswer->value);
});

it('points to ntfy\'s own page for a distributor to install', function (): void {
    expect(ADistributorToInstall::ntfy()->carried())->toBe('https://ntfy.sh/');
});
