<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheGrantIsFor;
use Modules\Kernel\Api\ThisDevice;
use Modules\Kernel\Api\Whose;
use Modules\Vault\Api\PlatformGrants;
use Modules\Vault\Internal\KeptUnder;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\APlatformStore;
use Tests\Support\TheGrantAsSeen;

// What the contract cannot say for both implementations: where the adapter
// writes, and what it makes of a value nobody in this build wrote. The rest is
// `KeepingTheGrantContractTest`'s.

/** A stack a grant is kept for. Named for this file (G10). */
function aStackThePlatformKeepsAGrantFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST)));
}

it('keeps the grant under a key of its own for each stack', function (): void {
    $store = APlatformStore::working();
    $stack = aStackThePlatformKeepsAGrantFor();

    new PlatformGrants($store)->keepTheGrant($stack, TheGrantIsFor::of(Whose::member('ada'), ThisDevice::named('this-device')), AGrant::of(str_repeat('a', 32), Instant::atEpochSeconds(1_000)));

    expect($store->whatIsUnder(KeptUnder::Grant->beneath($stack->stored())))
        ->toBe(sprintf('{"shape":1,"for_the_door":"%s","lapses_at":1000,"played_by":"ada","played_on":"this-device"}', str_repeat('a', 32)));
});

it('reads no grant from a value nobody in this build wrote', function (): void {
    $stack = aStackThePlatformKeepsAGrantFor();
    $grants = new PlatformGrants(APlatformStore::working()->alreadyHolding(KeptUnder::Grant->beneath($stack->stored()), 'a grant from elsewhere'));

    expect(TheGrantAsSeen::of($grants->theGrantOn($stack, TheGrantIsFor::of(Whose::member('ada'), ThisDevice::named('this-device')))))->toBe(TheGrantAsSeen::NONE)
        ->and($grants->keepsAnythingOf($stack))->toBeTrue();
});

it('says it may still hold something where the store cannot be read', function (): void {
    expect(new PlatformGrants(APlatformStore::refusing())->keepsAnythingOf(aStackThePlatformKeepsAGrantFor()))->toBeTrue();
});
