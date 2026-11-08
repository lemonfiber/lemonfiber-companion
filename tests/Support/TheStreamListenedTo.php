<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;

use function iterator_to_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAlert;
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

use Tests\Support\Fakes\WhereHearingLeftOffInMemory;

/**
 * A stack's event stream as the Hearing contract listens to it: the stack, the session, an event and an alert as the core frames them, the alerts a wake heard, and both ways of listening.
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

                return new Listeners(new PinnedClients(), new NullLogger(), WhereHearingLeftOffInMemory::nowhere());
            },
        ];
    }

    /**
     * One `alert` envelope as the core writes it, changed where a case says.
     *
     * @param  array<mixed>         $changed
     * @return array<string, mixed>
     */
    public static function anAlertEnvelope(array $changed = []): array
    {
        return [
            'api_version' => 1,
            'kind' => 'alert',
            'data' => [
                'check' => 'vpn.tunnel',
                'kind' => 'vpn.down',
                'moment' => 'onset',
                'severity' => 'critical',
                'notification' => 'problem',
                'summary' => 'The tunnel dropped',
                'meaning' => 'Downloads have stopped rather than leak',
                'remedies' => ['Restart the gateway'],
                'affected' => ['vpn.tunnel'],
                'id' => 'vpn.tunnel#2',
                ...$changed,
            ],
        ];
    }

    /**
     * One `alert` event as the core frames it, changed where a case says.
     *
     * @param array<mixed> $changed
     */
    public static function anAlertEvent(array $changed = []): string
    {
        return self::anEvent('alert', json_encode(self::anAlertEnvelope($changed), JSON_THROW_ON_ERROR));
    }

    /**
     * Every alert one wake heard, in order.
     *
     * @return list<AnAlert>
     */
    public static function theAlertsAWakeHeard(Hearing $hearing): array
    {
        return iterator_to_array($hearing->howItIs(self::aStack(), self::theSession())->theAlerts(), preserve_keys: false);
    }
}
