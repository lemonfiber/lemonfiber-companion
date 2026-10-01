<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AClientToHandOver;
use Modules\Kernel\Api\AHandoff;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\ASignedInDevice;
use Modules\Kernel\Api\HandoffSaysNothing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\TheClientsToHandOver;
use Modules\Kernel\Api\TheSignedInDevices;
use Modules\Kernel\Api\TheStepsOnTheirDevice;
use Modules\Kernel\Api\WhatBecameOfTheHandoff;
use Modules\Kernel\Api\WhatTheHandoffNeedsNext;
use Modules\Kernel\Api\WhatToHandThem;
use Modules\Kernel\Api\WhereTheHandoffStands;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** A hand-off with a reason where a case gives one. */
function aHandoffSaying(string $reason): AHandoff
{
    return AHandoff::answered(
        SomebodyInTheHousehold::called('Sam'),
        WhereTheHandoffStands::Failed,
        $reason,
        WhatTheHandoffNeedsNext::StartServer,
        WhatToHandThem::of(
            AnAddressToHand::none(),
            TheStepsOnTheirDevice::of(),
            TheClientsToHandOver::of(),
        ),
        AMomentAsWritten::of(''),
        TheSignedInDevices::of(),
    );
}

/** Which arm an answer takes. */
function whichArmTheHandoffTook(WhatBecameOfTheHandoff $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('underway %s', $job->shown())),
        answered: static fn(AHandoff $handoff): TheWordCarriedOut => new TheWordCarriedOut(sprintf('answered %s', $handoff->stands()->value)),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(string $because): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met %s', $why->kind()->name)),
    )->said;
}

it('takes the arm it was made on, and no other', function (): void {
    expect(whichArmTheHandoffTook(WhatBecameOfTheHandoff::underway(Job::named('handoff-1'))))->toBe('underway handoff-1')
        ->and(whichArmTheHandoffTook(WhatBecameOfTheHandoff::answered(aHandoffSaying('It could not go ahead.'))))->toBe('answered failed')
        ->and(whichArmTheHandoffTook(WhatBecameOfTheHandoff::ended()))->toBe('ended')
        ->and(whichArmTheHandoffTook(WhatBecameOfTheHandoff::refused('no such name')))->toBe('refused no such name')
        ->and(whichArmTheHandoffTook(WhatBecameOfTheHandoff::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe('met StackDidNotAnswer');
});

it('refuses a refusal or a reason with nothing in it, and takes an empty reason as none', function (): void {
    expect(static fn(): WhatBecameOfTheHandoff => WhatBecameOfTheHandoff::refused('  '))->toThrow(HandoffSaysNothing::class, '`reason` blank')
        ->and(static fn(): AHandoff => aHandoffSaying(' '))->toThrow(HandoffSaysNothing::class, '`reason` blank')
        ->and(aHandoffSaying('')->reason())->toBe('');
});

it('refuses an app, a device signed in or a step with a word left blank', function (): void {
    expect(static fn(): AClientToHandOver => AClientToHandOver::named(' ', 'Swiftfin', openSource: true, code: 'x', deepLink: false))->toThrow(HandoffSaysNothing::class, '`device`')
        ->and(static fn(): AClientToHandOver => AClientToHandOver::named('An iPhone', '', openSource: true, code: 'x', deepLink: false))->toThrow(HandoffSaysNothing::class, '`client`')
        ->and(static fn(): AClientToHandOver => AClientToHandOver::named('An iPhone', 'Swiftfin', openSource: true, code: ' ', deepLink: false))->toThrow(HandoffSaysNothing::class, '`code`')
        ->and(static fn(): ASignedInDevice => ASignedInDevice::listed('', 'Jellyfin Web', AMomentAsWritten::of('')))->toThrow(HandoffSaysNothing::class, '`device`')
        ->and(static fn(): ASignedInDevice => ASignedInDevice::listed('A laptop', ' ', AMomentAsWritten::of('')))->toThrow(HandoffSaysNothing::class, '`client`')
        ->and(static fn(): TheStepsOnTheirDevice => TheStepsOnTheirDevice::of('Open the app', "\n"))->toThrow(HandoffSaysNothing::class, '`step`');
});

it('keeps every list in the stack\'s order, and counts it', function (): void {
    $steps = TheStepsOnTheirDevice::of(...['first' => 'Open the app', 'second' => 'Sign in']);
    $clients = TheClientsToHandOver::of(
        AClientToHandOver::named('An iPhone', 'Swiftfin', openSource: true, code: 'swiftfin://x', deepLink: true),
        AClientToHandOver::named('A TV', 'A closed app', openSource: false, code: 'https://x', deepLink: false),
    );
    $signedIn = TheSignedInDevices::of(ASignedInDevice::listed('A laptop', 'Jellyfin Web', AMomentAsWritten::of('')));
    $named = [];

    foreach ($clients as $client) {
        $named[] = $client->client();
    }

    expect(iterator_to_array($steps, preserve_keys: true))->toBe(['Open the app', 'Sign in'])
        ->and($named)->toBe(['Swiftfin', 'A closed app'])
        ->and([count($steps), count($clients), count($signedIn)])->toBe([2, 2, 1]);
});
