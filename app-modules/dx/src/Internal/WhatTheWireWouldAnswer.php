<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function array_filter;

use const ARRAY_FILTER_USE_KEY;

use function implode;
use function is_array;
use function is_string;
use function json_encode;

use Lemonfiber\Sdk\Admission;
use Lemonfiber\Sdk\Contract\Api;
use Modules\Dx\Api\AStandInStack;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function str_starts_with;

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
     * from `JobEnvelope`'s own declaration, which is the point of it,
     * and a regex over a different file's paragraphs would be a second thing to
     * keep true for no gain.
     */
    private const string A_NAME_FOR_WORK = 'JobEnvelope';

    /**
     * What work already begun redeems into.
     *
     * `/api/jobs/<name>` is one path with two shapes behind it, and neither the
     * contract nor the name says which: a repair minted the job, or an action
     * did, and a stand-in that reads only the path cannot tell them apart. The
     * SDK decides by *kind* — {@see \Lemonfiber\Sdk\JobStanding::of()} reads a
     * `job` envelope as *still going* at `202` and as *ended* at anything else,
     * and anything that is not a `job` as the outcome itself.
     *
     * So answering with `job` at `200` tells every screen the work expired. It
     * is a real state and it is the wrong one to be stuck in: the repairs
     * screen is the one that draws a redemption on a frame — the contract states
     * what a stack would put right and what became of each — and it
     * drew *that question has expired* and nothing else, against a machine
     * answering everything.
     *
     * Repairs rather than actions, and the choice is forced rather than
     * preferred. An action's redemption is what a tap produces; a repair's is
     * what a frame draws, twice, and `RepairEnvelope` carries both halves of it
     * — `offered` for the listing and `mended` for the outcomes — so one
     * envelope answers the offer and the agreement. A verb told to a service
     * reads its redemption as an unexpected kind and reports an obstacle, which
     * is the honest cost of one path with two shapes.
     */
    private const string WHAT_WORK_BECOMES = 'RepairEnvelope';

    /**
     * What a door answers a password with.
     *
     * The third endpoint the contract does not write down, and the one that is
     * not on `Api` at all: `Admission::ENDPOINT` is the SDK's, because the door
     * is a transport of its own. Answered from `AdmissionEnvelope`'s own
     * declaration all the same, so the token and the ending are the shape the
     * reader is about to insist on.
     */
    private const string WHAT_A_DOOR_ANSWERS = 'AdmissionEnvelope';

    /**
     * When a stand-in session ends.
     *
     * One of the two places in this whole surface whose *shape* a reader
     * insists on beyond its type ({@see WHEN_A_CHANGE_WAS_MADE} is the other):
     * {@see \Lemonfiber\Sdk\Admitted::of()} puts `until` through `Stamp`, and
     * a stamp it cannot read is an `UnreadableResponse` — which arrives at the
     * sign-in screen as *this stack did not answer*, about a door that answered
     * perfectly well. Nearly every other string in a synthesised payload is
     * free-form and carries its own field name, which is what makes a stand-in
     * payload obvious on a screen; this one cannot.
     *
     * Far enough ahead that it is never a session that has already ended, and
     * written rather than counted from a clock: `B1` keeps time behind a port,
     * and a payload assembled from the moment it was built would make two runs
     * of the same stand-in answer differently.
     */
    private const string LONG_AFTER_ANY_RUN = '2099-01-01T00:00:00Z';

    /**
     * When every stand-in change was made.
     *
     * The other field whose shape a reader insists on. The core declares a
     * change's `at` as seconds since the epoch written in digits, and the
     * generated type says only `string` — so a synthesised `at` is the word
     * `at`, `Records` refuses it as it would refuse any stack that wrote one,
     * and the record screen draws *this stack did not answer* against a machine
     * answering everything.
     *
     * In the past, so every change reads with an age rather than as something
     * yet to happen, and written rather than counted from a clock for the same
     * reason {@see LONG_AFTER_ANY_RUN} is. Not `0`, which is how the stack says
     * its clock could not be read.
     */
    private const string WHEN_A_CHANGE_WAS_MADE = '1700000000';

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
        return new MockResponse(self::whatThatPathSends($endpoint), $status);
    }

    /**
     * The body one path sends, whichever of the four shapes it has.
     *
     * Split from {@see to()} because that method had four ways out and this has
     * one question (`H8`): the status is the machine's and belongs to every
     * path, and which body a path sends is a property of the path.
     *
     * @return array<string, mixed>|string
     */
    private static function whatThatPathSends(string $endpoint): array|string
    {
        // The four paths whose body is not one envelope built from the
        // contract's declaration, and then everything else. `match` rather than
        // four early returns, because what this is doing is naming a path
        // rather than deciding anything (`H8`, `C5`).
        return match (true) {
            $endpoint === Api::LOGS_ENDPOINT => self::aDocumentALine(),
            $endpoint === Admission::ENDPOINT => self::aDoorThatOpened(),
            $endpoint === Api::HISTORY_ENDPOINT => self::aRecordThatReads(),
            str_starts_with($endpoint, Api::JOBS_ENDPOINT) => self::oneEnvelope(self::WHAT_WORK_BECOMES),
            default => self::oneEnvelope(self::whateverTheContractSaysAbout($endpoint)),
        };
    }

    /**
     * The envelope a path declares, or the name for work where it declares none.
     *
     * Three endpoints declare nothing, and two of them are the work pair above;
     * this is what answers for the third and for any the SDK grows tomorrow.
     */
    private static function whateverTheContractSaysAbout(string $endpoint): string
    {
        $envelope = WhichEnvelopeAnEndpointAnswersWith::at($endpoint);

        return $envelope === '' ? self::A_NAME_FOR_WORK : $envelope;
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
     * A door that opened, with an ending a reader can parse.
     *
     * Built from `AdmissionEnvelope`'s own declaration and then corrected in
     * one field, rather than assembled here: the token, the shape and the kind
     * stay the contract's, and only the ending is replaced — which is the only
     * part of it the SDK reads as something other than text.
     *
     * @return array<string, mixed>
     */
    private static function aDoorThatOpened(): array
    {
        $envelope = self::oneEnvelope(self::WHAT_A_DOOR_ANSWERS);
        $data = $envelope['data'];

        // The narrowing is on one line on purpose. An envelope's `data` is
        // always an array — every `Data` the contract declares is an
        // `array{...}` or a `list<...>` — so the other arm is a shape no
        // envelope has, and written across three lines it is a line no run
        // reaches and the coverage gate is right to say so.
        $envelope['data'] = [...(is_array($data) ? $data : []), 'until' => self::LONG_AFTER_ANY_RUN];

        return $envelope;
    }

    /**
     * A record whose changes say when they were made in a way a reader can
     * parse.
     *
     * The `history` envelope's own declaration, corrected in the one field of
     * every change whose shape the generated type leaves open, for the reason
     * {@see aDoorThatOpened()} corrects one: everything else stays the
     * contract's, so the rest of the record is still the shape the reader is
     * about to insist on.
     *
     * @return array<string, mixed>
     */
    private static function aRecordThatReads(): array
    {
        $envelope = self::oneEnvelope(WhatTheContractDeclares::envelopeOfKind('history'));
        $data = $envelope['data'];
        // One line for the same reason as the door's: `data` is always an
        // array, and a second arm spread over lines is one no run reaches.
        $record = is_array($data) ? $data : [];
        // `changes` is required by the declaration, so the key is always
        // there; what is not known to the analyser is that it holds a list.
        $changes = $record['changes'];
        $corrected = [];

        foreach (is_array($changes) ? $changes : [] as $change) {
            $corrected[] = self::aChangeThatReads($change);
        }

        $record['changes'] = $corrected;
        $envelope['data'] = $record;

        return $envelope;
    }

    /**
     * One synthesised change, dated in digits.
     *
     * Its reversal is left as synthesised: the generated type closes that set,
     * so the stand-in already picks a word `Records` reads.
     *
     * @return array<string, mixed>
     */
    private static function aChangeThatReads(mixed $change): array
    {
        // A change's fields are named, so only its named keys are carried.
        return [
            ...array_filter(is_array($change) ? $change : [], is_string(...), ARRAY_FILTER_USE_KEY),
            'at' => self::WHEN_A_CHANGE_WAS_MADE,
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
