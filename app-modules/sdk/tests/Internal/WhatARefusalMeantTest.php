<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function array_filter;
use function expect;
use function it;
use function json_encode;

use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Sdk\Internal\WhatARefusalMeant;

use function sprintf;
use function str_repeat;

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

it('hands on the stack\'s own sentence only for a request it turned down', function (): void {
    // Everything from the first refusal up to the first fault on the stack's
    // side, bar the two with remedies of their own, is the stack saying no in
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
        409 => 'Nothing here to wire',
        499 => 'Nothing here to wire',
        500 => null,
    ])->and(WhatARefusalMeant::inItsOwnWords(refusedWith(409)))->toBeNull();
});

/** One line carried out of either arm of a refusal read in its words. */
final readonly class WhatTheRefusalCameTo
{
    public function __construct(public string $said) {}
}

/** What a refusal comes to when it is read for the stack's own words, as a line. */
function whatTheRefusalSaidInItsWords(CertificateWasRefused|RequestFailed $why): string
{
    return WhatARefusalMeant::inItsWords(
        $why,
        refused: static fn(ARefusalInItsWords $words): WhatTheRefusalCameTo => new WhatTheRefusalCameTo(sprintf(
            'refused: %s | %s | %s',
            $words->summary(),
            $words->meaning(),
            $words->named()->forTheOperator(),
        )),
        met: static fn(Obstacle $obstacle): WhatTheRefusalCameTo => new WhatTheRefusalCameTo($obstacle->name),
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
    expect(whatTheRefusalSaidInItsWords(aProblemTheStackSent(401)))->toBe(Obstacle::CredentialWasRefused->name)
        ->and(whatTheRefusalSaidInItsWords(aProblemTheStackSent(403)))->toBe(Obstacle::NotForThisAccount->name)
        ->and(whatTheRefusalSaidInItsWords(CertificateWasRefused::whenAsking('/api/actions/restore', str_repeat('b', 64), str_repeat('a', 64))))
        ->toBe(Obstacle::StackIsNotTheOnePaired->name);
});

it('reads an answer holding no problem with a sentence in it as a stack that did not answer', function (RequestFailed $why): void {
    expect(whatTheRefusalSaidInItsWords($why))->toBe(Obstacle::StackDidNotAnswer->name);
})->with([
    'a sentence with no problem around it' => [RequestFailed::from('/api/actions/restore', 500, 'This machine would not supply the randomness a job needs to be named.')],
    'a problem with a blank summary' => [aProblemTheStackSent(500, ['summary' => ' '])],
    'a problem missing its remedies' => [aProblemTheStackSent(500, ['remedies' => null])],
    'nothing at all' => [refusedWith(500)],
]);
