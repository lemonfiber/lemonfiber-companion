<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Sdk\Api\Listeners;
use Modules\Sdk\Api\PinnedClients;
use Psr\Log\NullLogger;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

use function sprintf;
use function str_repeat;

/**
 * A stack's event stream as the Hearing contract listens to it: the stack, the session, an event as the core frames it, and both ways of listening.
 *
 * Shared on purpose by the suites that hold the Hearing port to its contract.
 */
final readonly class TheStreamListenedTo
{
    /** A stack to listen to, reachable and pinned so the adapter will build a client. */
    public static function aStack(): Stack
    {
        return Stack::of(
            StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
            StackName::of('The attic'),
            Address::of('https://192.168.1.43:8443'),
            Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
        );
    }

    /** The session it is listened to with. */
    public static function theSession(): Session
    {
        return Session::of('a-session-not-a-secret');
    }

    /** One event as the core frames it. */
    public static function anEvent(string $kind, string $data): string
    {
        return sprintf("id: run-1\nevent: %s\ndata: %s\n\n", $kind, $data);
    }

    /**
     * Both ways of listening, each set up to hear the same thing.
     *
     * @param  list<MockResponse>  $streams  what the far end answers each time the adapter opens the stream
     * @param  Hearing             $fake     the fake, scripted to answer the same, wake by wake
     * @return array<string, Closure(): Hearing>
     */
    public static function everyWay(array $streams, Hearing $fake): array
    {
        return [
            'the fake' => static fn(): Hearing => $fake,
            'the adapter' => static function () use ($streams): Hearing {
                MockClient::destroyGlobal();
                MockClient::global($streams);

                return new Listeners(new PinnedClients(), new NullLogger());
            },
        ];
    }
}
