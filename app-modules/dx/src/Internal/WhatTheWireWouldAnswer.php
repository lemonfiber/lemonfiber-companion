<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function implode;
use function json_encode;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Dx\Api\AStandInStack;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

/**
 * An answer for every request, assembled out of the contract rather than typed.
 *
 * This is the whole of what makes the app usable with no lemonfiber running.
 * The seam is below the SDK and not above it — the client is the real one, the
 * envelope reader is the real one, the version check is the real one, and every
 * refusal the SDK can make it still makes. The single thing replaced is the
 * socket, which is the one part of the stack a phone in a test cannot have.
 *
 * That choice is what makes the exercise worth anything. A stand-in bound at
 * the port would skip the reader, and skipping the reader is skipping the code
 * most likely to be wrong: the shape check, the `api_version` comparison, the
 * `kind` requirement. A screen that renders against a stand-in above the SDK
 * has proved that the screen works. A screen that renders against this one has
 * proved that the screen, the reader and the shape agree.
 *
 * **A machine that is not answering answers the same way about everything.**
 * The status comes from {@see AStandInStack}, which holds one
 * per machine rather than one per endpoint: a stack that is down is down for
 * all of it, and a stand-in where `/api/status` failed and `/api/checks` did
 * not would be a machine no operator has ever met.
 *
 * The body is built either way, and deliberately. `RequestFailed` carries what
 * came back beside the status, so a refusal answering with a conforming
 * envelope is the shape a real stack has when something in front of it refuses
 * — and it means the reader is exercised on the failing path too, rather than
 * only where it succeeds.
 */
final readonly class WhatTheWireWouldAnswer
{
    /**
     * What an endpoint answers with where the SDK's declarations do not say.
     *
     * Three endpoints are in that position and two of them are this: an action
     * is asked for at `/api/actions/<name>` and the work it starts is asked
     * about at `/api/jobs/<name>`, and both answer with a name for the work
     * rather than its outcome. `Api` does not write that down;
     * `Client::repair()` does, in the sentence *"what comes back is a name for
     * the work rather than its outcome — the `job` envelope"*.
     *
     * So this is the one piece of the mapping read out of prose a human wrote
     * for another human. It is named here, once, rather than being pattern
     * matched out of `Client.php`: the payload underneath it is still built
     * from `JobEnvelope`'s own declaration, which is what `N1-R59` is about,
     * and a regex over a different file's paragraphs would be a second thing to
     * keep true for no gain.
     */
    private const string A_NAME_FOR_WORK = 'JobEnvelope';

    /**
     * How many lines a stand-in scrollback carries.
     *
     * More than one, because a window showing a single line looks the same as
     * a window showing all there is, and telling those apart is what somebody
     * opens a scrollback to do.
     */
    private const int A_FEW_LINES = 3;

    /**
     * A mock for every request this app can make.
     *
     * One catch-all rather than an entry per endpoint. An entry per endpoint is
     * a list, and a list is the thing that goes quietly stale: the day the SDK
     * grows a twelfth path, a table answers nothing for it and Saloon raises
     * where a screen wanted an envelope. A closure asked at the moment of the
     * request answers for paths nobody has written yet.
     */
    public static function asFarAs(AStandInStack $machine): MockClient
    {
        $status = $machine->answersWith();

        return new MockClient([
            '*' => static fn(PendingRequest $asked): MockResponse => self::to(
                $asked->getRequest()->resolveEndpoint(),
                $status,
            ),
        ]);
    }

    /**
     * What one path answers with.
     *
     * The scrollback is the one body that is not a single envelope — it is one
     * document a line, which {@see \Lemonfiber\Sdk\Envelope\EnvelopeReader}
     * reads with `readEach()` rather than `read()`. Told apart by the endpoint
     * rather than by the envelope, because the difference is a property of that
     * path and not of the `log` kind: one `log` envelope is a perfectly good
     * single document, and it is `/api/logs` that sends many.
     */
    public static function to(string $endpoint, int $status): MockResponse
    {
        if ($endpoint === Api::LOGS_ENDPOINT) {
            return new MockResponse(self::aDocumentALine(), $status);
        }

        $envelope = WhichEnvelopeAnEndpointAnswersWith::at($endpoint);

        return new MockResponse(
            self::oneEnvelope($envelope === '' ? self::A_NAME_FOR_WORK : $envelope),
            $status,
        );
    }

    /**
     * One envelope, wrapped the way the wire wraps one.
     *
     * The version comes off `Api::VERSION` rather than being written here, so a
     * stand-in can never answer with a version the client it is answering would
     * refuse — which would turn every screen into the mismatch message and read
     * exactly like a stack running the wrong release.
     *
     * @return array<string, mixed>
     */
    private static function oneEnvelope(string $envelope): array
    {
        return [
            'api_version' => Api::VERSION,
            'kind' => WhatTheContractDeclares::kindOf($envelope),
            'data' => WhatAStackWouldSay::inside($envelope),
        ];
    }

    /**
     * A scrollback, which is a `log` envelope a line.
     *
     * Every line is the same, and deliberately so. What a stand-in scrollback
     * is for is seeing that the window scrolls, wraps and is readable at the
     * size the device draws it; invented log lines that read like real ones
     * would put somebody in the position of reading them.
     */
    private static function aDocumentALine(): string
    {
        $lines = [];

        for ($at = 0; $at < self::A_FEW_LINES; $at++) {
            $lines[] = json_encode(self::oneEnvelope(WhatTheContractDeclares::envelopeOfKind('log')), JSON_THROW_ON_ERROR);
        }

        return implode("\n", $lines);
    }
}
