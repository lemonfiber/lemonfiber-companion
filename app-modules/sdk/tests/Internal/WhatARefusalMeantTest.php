<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function it;

use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Modules\Kernel\Api\Obstacle;
use Modules\Sdk\Internal\WhatARefusalMeant;

use function str_repeat;

// What the operator met, given what the far end refused with.
//
// Two of these are a stack that answered, and telling them apart is the whole
// point of the class: one ends the session and the other must not touch it.

/** A refusal from the stack, as the client raises one. */
function refusedWith(int $status): RequestFailed
{
    return RequestFailed::from('/api/requests', $status, '');
}

it('reads a session the stack will not accept as a refused credential', function (): void {
    // The one that signs somebody out, and the only one that may.
    $obstacle = WhatARefusalMeant::obstacle(refusedWith(401));

    expect($obstacle)->toBe(Obstacle::CredentialWasRefused)
        ->and($obstacle->meansWeAreSignedOut())->toBeTrue();
});

it('reads a stack that will not let this account ask as its own obstacle', function (): void {
    // The session is good and the answer is no. Reading it as the case above
    // would end a member's session the first time they reached something that
    // was never theirs.
    $obstacle = WhatARefusalMeant::obstacle(refusedWith(403));

    expect($obstacle)->toBe(Obstacle::NotForThisAccount)
        ->and($obstacle->meansWeAreSignedOut())->toBeFalse();
});

it('reads everything else as a stack that did not answer', function (): void {
    // A stack asleep, a network that dropped, an endpoint answering five
    // hundred — and a stack that could not check an account with its media
    // server, which belongs here rather than beside the refusal above: nothing
    // about it is the account's doing, and what it wants is another go.
    foreach ([500, 502, 503, 418] as $status) {
        $obstacle = WhatARefusalMeant::obstacle(refusedWith($status));

        expect($obstacle)->toBe(Obstacle::StackDidNotAnswer, (string) $status)
            ->and($obstacle->meansWeAreSignedOut())->toBeFalse();
    }
});

it('reads a peer presenting a certificate the pairing did not name as not the paired stack', function (): void {
    // Something answered, and it is not the machine paired with. It is never
    // silence, and it signs nobody out: the session belongs to the paired
    // machine, which has not been heard from.
    $obstacle = WhatARefusalMeant::obstacle(
        CertificateWasRefused::whenAsking('/api/status', str_repeat('b', 64), str_repeat('a', 64)),
    );

    expect($obstacle)->toBe(Obstacle::StackIsNotTheOnePaired)
        ->and($obstacle->meansWeAreSignedOut())->toBeFalse();
});
