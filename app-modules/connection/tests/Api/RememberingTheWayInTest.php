<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use ArrayObject;

use function expect;
use function it;

use Modules\Connection\Api\RememberingTheWayIn;
use Modules\Connection\Api\TheWayIn;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\StackId;

use function str_repeat;

use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\WaysInKeptInMemory;

function aHouseRemembered(string $letter = 'e'): StackId
{
    return StackId::of(Nonce::of(str_repeat($letter, Nonce::SHORTEST)));
}

function rememberingOver(ASealInMemory $seal, WaysInKeptInMemory $kept): RememberingTheWayIn
{
    return new RememberingTheWayIn($seal, $kept, FrozenClock::at(Instant::atEpochSeconds(7)));
}

function wasNoted(Noted $noted): bool
{
    return $noted->either(
        down: static fn(): ArrayObject => new ArrayObject(['noted']),
        notKept: static fn(): ArrayObject => new ArrayObject([]),
    )->count() === 1;
}

it('leads with the password on a house nothing let anybody in on yet', function (): void {
    expect(rememberingOver(ASealInMemory::working(), WaysInKeptInMemory::empty())->ledWithOn(aHouseRemembered()))->toBe(TheWayIn::Password);
});

it('leads with the way that last let a member in, house by house', function (): void {
    $remembering = rememberingOver(ASealInMemory::working(), WaysInKeptInMemory::empty());
    $noted = $remembering->letIn(aHouseRemembered(), TheWayIn::Code);

    expect($remembering->ledWithOn(aHouseRemembered()))->toBe(TheWayIn::Code)
        ->and($remembering->ledWithOn(aHouseRemembered('f')))->toBe(TheWayIn::Password)
        ->and(wasNoted($noted))->toBeTrue();

    $remembering->letIn(aHouseRemembered(), TheWayIn::Password);

    expect($remembering->ledWithOn(aHouseRemembered()))->toBe(TheWayIn::Password);
});

it('keeps nothing where the seal refuses, and leads with the password where what was kept will not open', function (): void {
    $kept = WaysInKeptInMemory::empty();
    $refused = rememberingOver(ASealInMemory::withNoSecureStorage(), $kept)->letIn(aHouseRemembered(), TheWayIn::Code);
    rememberingOver(ASealInMemory::working(), $kept)->letIn(aHouseRemembered(), TheWayIn::Code);

    expect(wasNoted($refused))->toBeFalse()
        ->and(rememberingOver(ASealInMemory::thatWillNotOpen(), $kept)->ledWithOn(aHouseRemembered()))->toBe(TheWayIn::Password);
});

it('forgets a house it is asked to, and says whether it keeps anything of one', function (): void {
    $kept = WaysInKeptInMemory::empty();
    $remembering = rememberingOver(ASealInMemory::working(), $kept);
    $remembering->letIn(aHouseRemembered(), TheWayIn::Code);
    $held = $remembering->keepsAnythingOf(aHouseRemembered());

    expect($held)->toBeTrue()
        ->and($remembering->forgetTheStack(aHouseRemembered())->howMany())->toBe(1)
        ->and($remembering->keepsAnythingOf(aHouseRemembered()))->toBeFalse()
        ->and($remembering->ledWithOn(aHouseRemembered()))->toBe(TheWayIn::Password)
        ->and(rememberingOver(ASealInMemory::withNoSecureStorage(), $kept)->keepsAnythingOf(aHouseRemembered()))->toBeTrue();
});
