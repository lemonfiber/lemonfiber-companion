<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function array_filter;
use function expect;
use function it;
use function json_encode;

use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Generated\RefusalCode;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Sdk\Internal\WhatARefusalMeant;

use function sprintf;
use function str_repeat;

use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// What the operator met, given what the far end refused with.
//
// Two of these are a stack that answered, and telling them apart is the whole
// point of the class: one ends the session and the other must not touch it.

/** A refusal from the stack, as the client raises one. */
function refusedWith(int $status): RequestFailed
{
    return RequestFailed::from('/api/requests', $status, '');
}

it('reads a session a stack without codes will not accept as a refused credential', function (): void {
    // A refusal carrying no code is read by its status, and this is the one that
    // signs somebody out.
    $obstacle = WhatARefusalMeant::obstacle(refusedWith(401));

    expect($obstacle)->toEqual(Obstacle::of(KindOfObstacle::CredentialWasRefused))
        ->and($obstacle->meansWeAreSignedOut())->toBeTrue();
});

it('reads a stack without codes that will not let this account ask as its own obstacle', function (): void {
    // The session is good and the answer is no. Reading it as the case above
    // would end a member's session the first time they reached something that
    // was never theirs.
    $obstacle = WhatARefusalMeant::obstacle(refusedWith(403));

    expect($obstacle)->toEqual(Obstacle::of(KindOfObstacle::NotForThisAccount))
        ->and($obstacle->meansWeAreSignedOut())->toBeFalse();
});

it('reads everything else as a stack that did not answer', function (): void {
    // A stack asleep, a network that dropped, an endpoint answering five
    // hundred — and a stack that could not check an account with its media
    // server, which belongs here rather than beside the refusal above: nothing
    // about it is the account's doing, and what it wants is another go.
    foreach ([500, 502, 503, 418] as $status) {
        $obstacle = WhatARefusalMeant::obstacle(refusedWith($status));

        expect($obstacle)->toEqual(Obstacle::of(KindOfObstacle::StackDidNotAnswer), (string) $status)
            ->and($obstacle->meansWeAreSignedOut())->toBeFalse();
    }
});

it('reads a stack held by other work as busy, which signs nobody out', function (): void {
    // Nothing was changed and the same request goes through once that work
    // has finished, so it is neither silence nor a refusal of who is asking.
    $obstacle = WhatARefusalMeant::obstacle(refusedWith(409));

    expect($obstacle)->toEqual(Obstacle::of(KindOfObstacle::StackIsBusy))
        ->and($obstacle->meansWeAreSignedOut())->toBeFalse();
});

it('reads a peer presenting a certificate the pairing did not name as not the paired stack', function (): void {
    // Something answered, and it is not the machine paired with. It is never
    // silence, and it signs nobody out: the session belongs to the paired
    // machine, which has not been heard from.
    $obstacle = WhatARefusalMeant::obstacle(
        CertificateWasRefused::whenAsking('/api/status', str_repeat('b', 64), str_repeat('a', 64)),
    );

    expect($obstacle)->toEqual(Obstacle::of(KindOfObstacle::StackIsNotTheOnePaired))
        ->and($obstacle->meansWeAreSignedOut())->toBeFalse();
});

it('hands on the stack\'s own sentence only for a request it turned down', function (): void {
    // Everything from the first refusal up to the first fault on the stack's
    // side, bar the three with remedies of their own, is the stack saying no in
    // words, and those words are the answer.
    $said = [];

    foreach ([399, 400, 401, 403, 409, 499, 500] as $status) {
        $said[$status] = WhatARefusalMeant::inItsOwnWords(RequestFailed::from('/api/actions/seed', $status, 'Nothing here to wire'));
    }

    expect($said)->toBe([
        399 => null,
        400 => 'Nothing here to wire',
        401 => null,
        403 => null,
        409 => null,
        499 => 'Nothing here to wire',
        500 => null,
    ])->and(WhatARefusalMeant::inItsOwnWords(refusedWith(422)))->toBeNull();
});

/** What a refusal comes to when it is read for the stack's own words, as a line. */
function whatTheRefusalSaidInItsWords(CertificateWasRefused|RequestFailed $why): string
{
    return WhatARefusalMeant::inItsWords(
        $why,
        refused: static fn(ARefusalInItsWords $words): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            'refused: %s | %s | %s',
            $words->summary(),
            $words->meaning(),
            $words->named()->forTheOperator(),
        )),
        met: static fn(Obstacle $obstacle): TheWordCarriedOut => new TheWordCarriedOut($obstacle->kind()->name),
    )->said;
}

/**
 * The error envelope a stack answers a refused restore with, changed where a
 * case says, and without a field a case sets to null.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function aProblemTheStackWrites(array $changed = []): array
{
    $problem = [
        'code' => 'RESTORE-2',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'This backup is from a newer lemonfiber',
        'meaning' => 'It is refused rather than half-applied. Nothing was touched.',
        'remedies' => [['action' => 'Update lemonfiber, then restore']],
        ...$changed,
    ];

    return ['api_version' => 1, 'kind' => 'error', 'data' => array_filter($problem, static fn(mixed $value): bool => $value !== null)];
}

/**
 * That envelope, as the client raises it at a status.
 *
 * @param array<string, mixed> $changed
 */
function aProblemTheStackSent(int $status, array $changed = []): RequestFailed
{
    return RequestFailed::from('/api/actions/restore', $status, (string) json_encode(aProblemTheStackWrites($changed)));
}

it('stands in for a stack with a problem the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aProblemTheStackWrites(['detail' => 'the backup is 2.0.0, this is 1.4.0'])))->toBe([]);
});

it('reads a problem the stack sent as its refusal, in its words, with what it named', function (): void {
    expect(whatTheRefusalSaidInItsWords(aProblemTheStackSent(500, ['detail' => 'the backup is 2.0.0, this is 1.4.0'])))
        ->toBe('refused: This backup is from a newer lemonfiber | It is refused rather than half-applied. Nothing was touched. | the backup is 2.0.0, this is 1.4.0')
        ->and(whatTheRefusalSaidInItsWords(aProblemTheStackSent(404)))
        ->toBe('refused: This backup is from a newer lemonfiber | It is refused rather than half-applied. Nothing was touched. | ');
});

it('reads a problem at a status that says who may ask as what was met', function (): void {
    expect(whatTheRefusalSaidInItsWords(aProblemTheStackSent(401)))->toEqual(KindOfObstacle::CredentialWasRefused->name)
        ->and(whatTheRefusalSaidInItsWords(aProblemTheStackSent(403)))->toEqual(KindOfObstacle::NotForThisAccount->name)
        ->and(whatTheRefusalSaidInItsWords(CertificateWasRefused::whenAsking('/api/actions/restore', str_repeat('b', 64), str_repeat('a', 64))))
        ->toEqual(KindOfObstacle::StackIsNotTheOnePaired->name);
});

it('reads an answer holding no problem with a sentence in it as a stack that did not answer', function (RequestFailed $why): void {
    expect(whatTheRefusalSaidInItsWords($why))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
})->with([
    'a sentence with no problem around it' => [RequestFailed::from('/api/actions/restore', 500, 'This machine would not supply the randomness a job needs to be named.')],
    'a problem with a blank summary' => [aProblemTheStackSent(500, ['summary' => ' '])],
    'a problem missing its remedies' => [aProblemTheStackSent(500, ['remedies' => null])],
    'nothing at all' => [refusedWith(500)],
]);

/** A refusal carrying a code, at the status the contract answers it with. */
function refusedFor(RefusalCode $code): RequestFailed
{
    return aProblemTheStackSent($code->status(), ['code' => $code->value, 'summary' => 'The stack said no.']);
}

it('stands in for a stack with a refusal the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aProblemTheStackWrites(['code' => RefusalCode::NotAdmitted->value])))->toBe([]);
});

it('reads a session the stack no longer admits as signed out, though it answered forbidden', function (): void {
    // What a restarted stack says to a phone still holding the last run's
    // session. The status is the one an account refused entitlement gets too;
    // the code is what says signing in again is the remedy.
    $obstacle = WhatARefusalMeant::obstacle(refusedFor(RefusalCode::NotAdmitted));

    expect($obstacle)->toEqual(Obstacle::of(KindOfObstacle::CredentialWasRefused))
        ->and($obstacle->meansWeAreSignedOut())->toBeTrue();
});

it('reads each refusal of who is asking as its own obstacle', function (RefusalCode $code, Obstacle $met): void {
    expect(WhatARefusalMeant::obstacle(refusedFor($code)))->toEqual($met);
})->with([
    'an account asking for what is not its own' => [RefusalCode::NotYours, Obstacle::of(KindOfObstacle::NotForThisAccount)],
    'an account the media server could not vouch for' => [RefusalCode::Unconfirmed, Obstacle::of(KindOfObstacle::MediaServerDidNotAnswer)],
    'an address the stack is not listening on' => [RefusalCode::Elsewhere, Obstacle::of(KindOfObstacle::AddressIsNotTheStacks)],
]);

it('signs out on the one code that says the session is not admitted, and on no other', function (): void {
    $signedOut = [];

    foreach (RefusalCode::cases() as $code) {
        if (WhatARefusalMeant::obstacle(refusedFor($code))->meansWeAreSignedOut()) {
            $signedOut[] = $code;
        }
    }

    expect($signedOut)->toBe([RefusalCode::NotAdmitted]);
});

it('reads a refusal of what was asked in the stack\'s own words, whatever its status', function (): void {
    expect(whatTheRefusalSaidInItsWords(refusedFor(RefusalCode::NoTerm)))
        ->toBe('refused: The stack said no. | It is refused rather than half-applied. Nothing was touched. | ')
        ->and(WhatARefusalMeant::inItsOwnWords(refusedFor(RefusalCode::WrongMethod)))->toBe('The stack said no.');
});

it('reads a code it does not know by the status it came with', function (): void {
    // A stack newer than this app can refuse with a code this build has no case
    // for, and the status is what is left to go on.
    $unknown = static fn(int $status): Obstacle => WhatARefusalMeant::obstacle(
        aProblemTheStackSent($status, ['code' => 'NEWER-1']),
    );

    expect($unknown(401))->toEqual(Obstacle::of(KindOfObstacle::CredentialWasRefused))
        ->and($unknown(403))->toEqual(Obstacle::of(KindOfObstacle::NotForThisAccount))
        ->and($unknown(409))->toEqual(Obstacle::of(KindOfObstacle::StackIsBusy))
        ->and($unknown(400))->toEqual(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
});

it('reads a media server that could not vouch for an account as met rather than in the stack\'s words', function (): void {
    expect(whatTheRefusalSaidInItsWords(refusedFor(RefusalCode::Unconfirmed)))->toEqual(KindOfObstacle::MediaServerDidNotAnswer->name)
        ->and(whatTheRefusalSaidInItsWords(refusedFor(RefusalCode::Elsewhere)))->toEqual(KindOfObstacle::AddressIsNotTheStacks->name);
});
