<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\Capabilities;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhetherItIsOffered;
use Modules\Sdk\Api\Abilities;
use Modules\Sdk\Api\CapabilitiesAreUnreadable;

use function str_repeat;

use Tests\Support\WhatTheContractAccepts;

/** The stack whose declaration is read. */
function theStackThatDeclares(): StackId
{
    return StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST)));
}

/**
 * A `capabilities` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, for {@see aRepertoireSaying()}'s reason:
 * what is tested includes what the wire says when the contract does not allow
 * it.
 *
 * @return Envelope<mixed>
 */
function aDeclarationSaying(mixed $data): Envelope
{
    return new Envelope(1, 'capabilities', $data);
}

/** What the declaration came to, for one path. */
function whatItSaysOf(Capabilities $declared, string $path): WhetherItIsOffered
{
    return $declared->whetherItOffers(Ability::of($path));
}

it('reads each path the stack declares as what the stack says of it', function (): void {
    $declared = Abilities::in(aDeclarationSaying(['capabilities' => [
        '/api/status' => 'available',
        '/api/actions/backup' => 'unconfigured',
        '/api/actions/invite' => 'unpermitted',
    ]]), theStackThatDeclares());

    expect(whatItSaysOf($declared, '/api/status'))->toBe(WhetherItIsOffered::Offered)
        ->and(whatItSaysOf($declared, '/api/actions/backup'))->toBe(WhetherItIsOffered::NotSetUp)
        ->and(whatItSaysOf($declared, '/api/actions/invite'))->toBe(WhetherItIsOffered::NotTheirs)
        ->and(whatItSaysOf($declared, '/api/actions/restart'))->toBe(WhetherItIsOffered::NeedsANewerLemonfiber)
        ->and($declared->describes(theStackThatDeclares()))->toBeTrue();
});

it('carries a path this app has never heard of rather than refusing the answer for it', function (): void {
    // A stack newer than this app declares requests nothing here asks for.
    $declared = Abilities::in(aDeclarationSaying(['capabilities' => [
        '/api/something-not-yet-written' => 'available',
        '/api/status' => 'available',
    ]]), theStackThatDeclares());

    expect(whatItSaysOf($declared, '/api/status'))->toBe(WhetherItIsOffered::Offered);
});

it('reads a stack that declares nothing as one that has none of it', function (): void {
    $declared = Abilities::in(aDeclarationSaying(['capabilities' => []]), theStackThatDeclares());

    expect(whatItSaysOf($declared, '/api/status'))->toBe(WhetherItIsOffered::NeedsANewerLemonfiber);
});

it('refuses a state the contract does not name, naming the path and the three it does', function (): void {
    expect(fn(): Capabilities => Abilities::in(aDeclarationSaying(['capabilities' => ['/api/status' => 'maybe']]), theStackThatDeclares()))
        ->toThrow(CapabilitiesAreUnreadable::class, '/api/status is in a state that is none of available, unconfigured, unpermitted');
});

it('refuses a state that is not a word at all', function (): void {
    expect(fn(): Capabilities => Abilities::in(aDeclarationSaying(['capabilities' => ['/api/status' => true]]), theStackThatDeclares()))
        ->toThrow(CapabilitiesAreUnreadable::class, '/api/status');
});

it('refuses a declaration with no list of what it serves', function (mixed $data): void {
    expect(fn(): Capabilities => Abilities::in(aDeclarationSaying($data), theStackThatDeclares()))
        ->toThrow(CapabilitiesAreUnreadable::class, 'has no `capabilities`');
})->with([
    'no payload' => [null],
    'no list' => [[]],
    'not a list' => [['capabilities' => 'everything']],
]);

it('refuses an answer in a wire version this app does not read', function (): void {
    expect(fn(): Capabilities => Abilities::in(new Envelope(99, 'capabilities', ['capabilities' => []]), theStackThatDeclares()))
        ->toThrow(EnvelopeIsNotRead::class);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    $payload = ['api_version' => 1, 'kind' => 'capabilities', 'data' => ['capabilities' => [
        '/api/status' => 'available',
        '/api/actions/backup' => 'unconfigured',
        '/api/actions/invite' => 'unpermitted',
    ]]];

    expect(WhatTheContractAccepts::complaintsAbout('CapabilitiesEnvelope', $payload))->toBe(
        [],
        "The payload this suite stands in for a stack with is not one a stack would send.\n",
    );
});
