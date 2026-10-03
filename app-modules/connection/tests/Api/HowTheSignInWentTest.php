<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function array_filter;
use function array_values;
use function expect;
use function it;

use Modules\Connection\Api\HowTheSignInWent;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;

it('is put right in the app\'s settings only where the local network was refused', function (): void {
    expect(array_values(array_filter(
        HowTheSignInWent::cases(),
        static fn(HowTheSignInWent $went): bool => $went->isPutRightInTheAppsSettings(),
    )))->toBe([HowTheSignInWent::TheNetworkIsNotPermitted]);
});

it('reads a refused credential offered with a name as the pair not recognised, and anything else as the door alone would', function (): void {
    expect(HowTheSignInWent::metWithAName(Obstacle::of(KindOfObstacle::CredentialWasRefused)))->toBe(HowTheSignInWent::ThePairWasRefused)
        ->and(HowTheSignInWent::metWithAName(Obstacle::of(KindOfObstacle::TooManyAttempts)))->toBe(HowTheSignInWent::TooManyAttempts)
        ->and(HowTheSignInWent::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)))->toBe(HowTheSignInWent::CredentialWasRefused);
});

it('offers another attempt, and a field to make it in, after a name and password that were not recognised', function (): void {
    expect(HowTheSignInWent::ThePairWasRefused->isWorthAnotherAttempt())->toBeTrue()
        ->and(HowTheSignInWent::ThePairWasRefused->mayTry())->toBeTrue()
        ->and(HowTheSignInWent::ThePairWasRefused->said())->toBe('connection.pair_was_refused')
        ->and(HowTheSignInWent::ThePairWasRefused->remedy())->toBe('connection.pair_was_refused_action');
});
