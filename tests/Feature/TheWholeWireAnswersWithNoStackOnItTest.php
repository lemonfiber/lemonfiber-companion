<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Client;
use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Logs;
use Lemonfiber\Sdk\LogWindow;
use Modules\Dx\Api\AStandInStack;
use Modules\Dx\Api\ClientsThatReachNothing;
use Modules\Dx\Internal\WhatTheContractDeclares;
use Modules\Dx\Internal\WhichEnvelopeAnEndpointAnswersWith;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Tests\Support\WhatTheContractAccepts;

// N1-R59 — the whole app can be run and looked at with nothing else running.
//
// The point of the seam being below the SDK rather than above it, asserted
// rather than argued. What these ask is not *did the stand-in produce the bytes
// it meant to* — the arch test beside it asks that — but *did the real client,
// the real reader and the real version check accept them*. A stand-in bound at
// the port would pass the first question and never be asked the second, which
// is why it would be worth so much less: the shape check, the `api_version`
// comparison and the required `kind` are the code most likely to be wrong and
// the code such a fake skips entirely.
//
// Nothing here opens a socket, and that is not a hope. Saloon consults the mock
// before the sender and raises on a request with no matching entry, so a body
// arriving at all is proof the mock answered it — there is no path where the
// network was reached and the test still passed.

/** Named for this file: the root suites share one namespace (G10). */
function aStackThatIsNotListening(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('Nothing is running here'),
        // Reserved by RFC 2606 so it resolves nowhere, which makes the claim
        // above testable rather than trusted: were the mock ever to miss, the
        // failure would be a name that cannot resolve and not a request to
        // somebody's actual machine.
        Address::of('https://a-stack-that-is-not-there.invalid:8443'),
        Fingerprint::of(str_repeat('ab', 32)),
    );
}

function aSessionThatOpensNothing(): Session
{
    // N1-R60 — a word, not a credential. Nothing accepts it because nothing is
    // listening, and a real-looking token in a fixture is the thing somebody
    // copies into a place where something is.
    return Session::of('not-a-credential');
}

/**
 * The stand-in's client, which is the application's client with no socket under it.
 *
 * Declared rather than narrowed. {@see Modules\Sdk\Api\Clients} is what the
 * stand-in implements and it says what a client is, so there is no crossing to
 * make here — which is the point of the interface: every adapter that opens a
 * connection used to name the concrete class instead, and a concrete final
 * class is a seam nothing can be put into.
 */
function aClientForNothing(): Client
{
    return new ClientsThatReachNothing(new PinnedClients())
        ->client(aStackThatIsNotListening(), aSessionThatOpensNothing());
}

/**
 * What the application made of one machine's answer, or nothing where it read.
 *
 * Outside a closure for {@see whatCameBackFrom()}'s reason, and it catches
 * rather than letting the refusal through: a refusal is the expected result for
 * two of the three, so raising here would be this rule failing on the case it
 * was written for.
 */
function whatStoodInTheWayOf(AStandInStack $machine): ?Obstacle
{
    $client = new ClientsThatReachNothing(new PinnedClients())
        ->client($machine->asAStack(), aSessionThatOpensNothing());

    try {
        $client->read(Api::STATUS_ENDPOINT);
    } catch (RequestFailed $why) {
        return WhatARefusalMeant::obstacle($why);
    }

    return null;
}

/**
 * One endpoint's answer, asked for outside a closure.
 *
 * For {@see theScrollbackOf()}'s reason, and it is the same three exceptions:
 * the analyser refuses a checked exception raised inside a closure and every
 * Pest body is one. Left to raise rather than caught, because each of the three
 * is this test failing and each says more about why than an expectation would.
 *
 * `Envelope<mixed>`, which is what the client answers with and all it can: only
 * the generated types know which shape a given `kind` carries, and narrowing
 * here would be this file claiming to know before the `kind` has been read.
 *
 * @return Envelope<mixed>
 */
function whatCameBackFrom(string $endpoint): Envelope
{
    return aClientForNothing()->read($endpoint);
}

/**
 * One scrollback, asked for outside a closure.
 *
 * A named function rather than the call at the site, because the analyser
 * refuses a checked exception raised inside a closure and every Pest body is
 * one. The exception is deliberately not caught: a stand-in that could not
 * assemble the request is this test failing, and it fails loudest by raising
 * with the SDK's own message.
 */
function theScrollbackOf(string $service): LogWindow
{
    return aClientForNothing()->logs(Logs::ofService($service, 3));
}

it('answers a read with an envelope the real reader accepts', function (): void {
    $envelope = whatCameBackFrom(Api::STATUS_ENDPOINT);

    expect($envelope)->toBeInstanceOf(Envelope::class)
        ->and($envelope->apiVersion)->toBe(Api::VERSION)
        ->and($envelope->kind)->toBe('status');
});

it('N1-R59 — every endpoint the contract declares answers with a shape it accepts', function (): void {
    $refused = [];

    foreach (WhichEnvelopeAnEndpointAnswersWith::everyOneNamed() as $path => $envelope) {
        // The scrollback is the one body that is not a single envelope, so it
        // is asked for through the method that reads it that way.
        if ($path === Api::LOGS_ENDPOINT) {
            continue;
        }

        $answered = whatCameBackFrom($path);
        $complaints = WhatTheContractAccepts::complaintsAbout($envelope, ['data' => $answered->data]);

        if ($answered->kind !== WhatTheContractDeclares::kindOf($envelope)) {
            $complaints[] = sprintf('answered as `%s`', $answered->kind);
        }

        if ($complaints !== []) {
            $refused[] = sprintf('%s: %s', $path, implode('; ', $complaints));
        }
    }

    expect($refused)->toBe([], sprintf(
        "These endpoints answered with something the contract does not accept:\n  %s\n\n"
        . 'Each answer came back through the real client and the real envelope reader, so '
        . 'a complaint here is the stand-in and not the checker: the reader had already '
        . "agreed the body was an envelope before the shape was looked at.\n"
        . 'What the stand-in answers with is read off each envelope\'s own declaration, so '
        . 'the usual cause is the notation being misread rather than the contract having '
        . 'moved (N1-R59).',
        implode("\n  ", $refused),
    ));
});

it('answers the scrollback as a document a line', function (): void {
    // The one body that is not a single envelope, and the one the reader takes
    // apart with `readEach()` rather than `read()`. Worth its own test because
    // a stand-in that answered it with a single document would be refused by
    // the reader rather than by a shape check — the failure would arrive as
    // *this is not readable as JSON*, which names nothing.
    $window = theScrollbackOf('a-service');

    expect($window->lines())->toHaveCount(3);
});

it('N1-R10 — a machine that is not answering reaches the obstacle for it', function (): void {
    // The half a single stand-in cannot show. Every screen behind a stack that
    // answers is reachable already; the screens an operator actually meets on a
    // bad evening are behind one that does not, and a build where those cannot
    // be reached is a build where nobody has looked at them.
    //
    // Asserted at the obstacle rather than at the status, because the status is
    // the stand-in's own output and proves only that it did what it was told.
    // What matters is that the application's own reading of it — the one
    // sentence `WhatARefusalMeant` makes — comes out different for the two.
    $met = [];

    foreach (AStandInStack::cases() as $machine) {
        $met[$machine->value] = whatStoodInTheWayOf($machine);
    }

    expect($met[AStandInStack::Answering->value])->toBeNull()
        ->and($met[AStandInStack::NotAnswering->value])->toBe(Obstacle::StackDidNotAnswer)
        ->and($met[AStandInStack::RefusingTheSession->value])->toBe(Obstacle::CredentialWasRefused);
});

it('finds endpoints to ask about', function (): void {
    // The floor. A resolver that read nothing would make the sweep above pass
    // with no iterations, which is the shape of silence every rule in this
    // repository is written against.
    expect(WhichEnvelopeAnEndpointAnswersWith::everyOneNamed())->not->toBeEmpty();
});
