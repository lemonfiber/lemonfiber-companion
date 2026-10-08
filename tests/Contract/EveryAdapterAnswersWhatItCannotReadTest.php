<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\Unreachable;
use Modules\Dx\Internal\WhatAStackWouldSay;
use Modules\Dx\Internal\WhatTheWireWouldAnswer;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\EveryAdapterCallThatReads;
use Tests\Support\Tree;
use Tests\Support\WalkthroughsToFollow;
use Tests\Support\WhatAResetSays;

// A malformed answer reaches a screen as an obstacle, whichever adapter read it,
// and so do a stack that could not be asked at all and a machine that is not the
// one paired with.
//
// Each adapter is asked once for every way its answer can be spoiled: `data`
// that is not a table, each text in it left blank, and each table or list in
// it replaced by a word. The answer it spoils is the one the stand-in stack
// sends, which is built from the contract, so a field the contract adds is
// spoiled here without anyone listing it.
//
// For a spoiled answer, nothing here asserts which obstacle comes back, or that
// it was refused at all: a blank the stack is allowed to send is read as one.
// What is asserted is that nothing escapes the adapter as an exception, which a
// screen would draw as a crash rather than as the sentence an obstacle carries.
//
// Each adapter is also asked once with the connection refused before any
// answer exists, and once with the peer presenting a certificate the pairing did
// not name. Each has a single right answer: the obstacle for a stack that did
// not answer, and the obstacle for a stack that is not the one paired.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** Where the call following an invitation's work is answered, which no path of the stand-in names. */
const THE_WORK_AN_INVITATION_BECOMES = 'the work an invitation becomes';

/**
 * Where an answer is built from an envelope's declaration rather than asked of a path.
 *
 * The stand-in answers every action as work, and no path it serves answers
 * with the `music`, `upgrade`, `backup`, `restore` or `stop-seeding`
 * envelope, so a call answered with one of those is given the envelope
 * itself, built by the stand-in from the contract.
 */
const AN_ENVELOPE_BY_NAME = 'envelope:';

/**
 * The path whose answer a call is given, where it is not the one it asked.
 *
 * The stand-in answers a change to a setting as work, which leaves nothing
 * here for the reader to refuse, so the change is given the listing, reviewed.
 * The stand-in answers an update's job as a repair's, which an update's reader
 * refuses as the wrong kind, so an update's job is given the update's reading.
 * The stand-in answers handing a command over as work too, where the stack
 * answers it with the `hosting` envelope, so that act is given the reading.
 * An invitation's job finishes as an invitation, which no path of the stand-in
 * answers with, so it is given {@see THE_WORK_AN_INVITATION_BECOMES}.
 * A quality choice is given the quality reading, and a choice for music and an
 * upgrade the envelopes each is answered with. So are a finished copy, the
 * listing a restore answers without a yes, and a finished restore, what a
 * start, a stop or a restart came to, both what stopping seeding would cost
 * and what it came to, what taking somebody out came to, what taking
 * lemonfiber off came to, and a choice of filler, read or made.
 */
function theAnswerACallIsGiven(string $which, string $asked): string
{
    return match (true) {
        str_starts_with($which, 'Adjustments::') => Api::CONFIG_ENDPOINT,
        $which === 'Upkeepers::whatBecameOf' => Api::UPDATE_ENDPOINT,
        $which === 'Keepers::handOver' => Api::HOSTING_ENDPOINT,
        $which === 'Ushers::whatBecameOf' => THE_WORK_AN_INVITATION_BECOMES,
        $which === 'Graders::confirm' => Api::QUALITY_ENDPOINT,
        $which === 'Graders::choose' => sprintf('%sMusicEnvelope', AN_ENVELOPE_BY_NAME),
        str_starts_with($which, 'Upgraders::') => sprintf('%sUpgradeEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Scouts::whatBecameOf' => sprintf('%sAdoptionEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Wirers::whatBecameOf' => sprintf('%sSeedEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Copiers::whatBecameOf' => sprintf('%sBackupEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Supervisors::whatBecameOf' => sprintf('%sLifecycleEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Bundlers::whatBecameOf' => sprintf('%sBundleEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Guards::whatBecameOf', $which === 'Guards::letGo' => sprintf('%sWatchEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Restorers::rehearse', $which === 'Restorers::whatBecameOf' => sprintf('%sRestoreEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Releasers::whatTheOfferCameTo', $which === 'Releasers::whatBecameOf' => sprintf('%sStopSeedingEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Reversers::whatBecameOf' => sprintf('%sUndoEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Removers::whatBecameOf' => sprintf('%sRemovalEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Dismantlers::whatBecameOf' => sprintf('%sUninstallEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Extenders::whatBecameOf' => sprintf('%sPluginsEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Pairers::whatBecameOf' => sprintf('%sPairingEnvelope', AN_ENVELOPE_BY_NAME),
        $which === 'Connectors::whatBecameOf' => sprintf('%sHandoffEnvelope', AN_ENVELOPE_BY_NAME),
        str_starts_with($which, 'Fillers::') => sprintf('%sSubstitutionEnvelope', AN_ENVELOPE_BY_NAME),
        default => $asked,
    };
}

/** What the stand-in would answer a path with, or an envelope named in its place. */
function theStandInsAnswerTo(string $endpoint, string ...$asked): MockResponse
{
    if (! str_starts_with($endpoint, AN_ENVELOPE_BY_NAME)) {
        return WhatTheWireWouldAnswer::to($endpoint, 200, ...$asked);
    }

    return WhatTheWireWouldAnswer::withTheEnvelope(substr($endpoint, strlen(AN_ENVELOPE_BY_NAME)), 200);
}

/**
 * The envelopes one call is sent, as the stand-in would send them.
 *
 * The stand-in answers every job as a repair's, and no path answers with a
 * walkthrough, so a walkthrough's job is sent the record a finished walk
 * answers with. A reset's job is sent a reset with its diff in the stack's
 * own shape, which the contract types only as text and a payload built from
 * the declaration cannot be.
 *
 * Built once for each call and question, and handed out again for every
 * place it is spoiled at: a stand-in is read out of the contract's whole
 * declaration, and building the larger envelopes again at each of their
 * thousand-odd places is most of what this suite would otherwise spend.
 *
 * @return list<array<mixed>>
 */
function theEnvelopesACallIsSent(string $which, string $endpoint, string ...$asked): array
{
    /** @var array<string, list<array<mixed>>> $built */
    static $built = [];

    return $built[implode("\0", [$which, $endpoint, ...$asked])] ??= theEnvelopesBuiltFor($which, $endpoint, ...$asked);
}

/**
 * The envelopes one call is sent, built afresh.
 *
 * @return list<array<mixed>>
 */
function theEnvelopesBuiltFor(string $which, string $endpoint, string ...$asked): array
{
    return match ($which) {
        'Guides::whatBecameOf' => [WalkthroughsToFollow::whatAStackSaysOfTheWalkThatWorked()],
        'Resetters::whatBecameOf' => [WhatAResetSays::envelope(confirmed: false)],
        default => theEnvelopesAPathSends(theAnswerACallIsGiven($which, $endpoint), ...$asked),
    };
}

/**
 * Where each spoiling can be made in one payload, as a path of keys.
 *
 * @param list<int|string> $at
 * @return list<list<int|string>>
 */
function everyPlaceToSpoil(mixed $held, array $at = []): array
{
    if (! is_array($held)) {
        return is_string($held) ? [$at] : [];
    }

    $found = $at === [] ? [] : [$at];

    foreach ($held as $key => $inside) {
        $found = [...$found, ...everyPlaceToSpoil($inside, [...$at, $key])];
    }

    return $found;
}

/**
 * One payload with the value at a path replaced.
 *
 * A text there becomes blank and a table becomes a word, so that a reader
 * expecting either meets the other.
 *
 * @param list<int|string> $at
 */
function spoiledAt(mixed $held, array $at): mixed
{
    if ($at === []) {
        return is_string($held) ? '' : 'spoiled';
    }

    if (! is_array($held)) {
        return $held;
    }

    $key = array_shift($at);

    if (array_key_exists($key, $held)) {
        $held[$key] = spoiledAt($held[$key], $at);
    }

    return $held;
}

/**
 * What an envelope carries as `data`, or nothing where it carries none.
 *
 * @param array<mixed> $envelope
 */
function theDataIn(array $envelope): mixed
{
    if (! array_key_exists('data', $envelope)) {
        return null;
    }

    return $envelope['data'];
}

/**
 * One envelope's worth of body, with its `data` spoiled at a path.
 *
 * A path of `null` spoils `data` itself.
 *
 * @param array<mixed>          $envelope
 * @param list<int|string>|null $at
 * @return array<mixed>
 */
function anEnvelopeSpoiledAt(array $envelope, ?array $at): array
{
    $envelope['data'] = $at === null ? 'spoiled' : spoiledAt(theDataIn($envelope), $at);

    return $envelope;
}

/**
 * The text values a request asked with, which pick between the envelopes one path answers with.
 *
 * @return list<string>
 */
function whatARequestAsked(PendingRequest $asked): array
{
    return array_values(array_filter($asked->query()->all(), is_string(...)));
}

/**
 * The body a path sends, as the stand-in would send it.
 *
 * A list of envelopes for the scrollback, which is one document a line, the
 * envelope one event carries for the stream, and a single envelope for every
 * other path. What the request asked picks between
 * the envelopes one path answers with, as it does for the stand-in.
 *
 * @return list<array<mixed>>
 */
function theEnvelopesAPathSends(string $endpoint, string ...$asked): array
{
    // Work an invitation was asked for finishes as an invitation. The stand-in
    // answers every job with the envelope a repair finishes as, which is right
    // for the calls that follow a repair and says nothing of the one that
    // follows an invitation, so that one is answered with its own.
    //
    // `invitation` is stood in for and not judged: its data is the contract's own sample of the envelope, read out of the declaration by the stand-in.
    if ($endpoint === THE_WORK_AN_INVITATION_BECOMES) {
        return [[
            'api_version' => Api::VERSION,
            'kind' => 'invitation',
            'data' => WhatAStackWouldSay::inside('InvitationEnvelope'),
        ]];
    }

    $body = theStandInsAnswerTo($endpoint, ...$asked)->body()->all();

    if (is_array($body)) {
        return [$body];
    }

    $text = is_string($body) ? $body : '';

    if ($endpoint === Api::EVENTS_ENDPOINT) {
        $text = anEventsData($text);
    }

    $lines = [];

    foreach (explode("\n", trim($text)) as $line) {
        $read = json_decode($line, associative: true, flags: JSON_THROW_ON_ERROR);
        $lines[] = is_array($read) ? $read : [];
    }

    return $lines;
}

/**
 * What each event on the stream carries, which is the envelope after `data: `, one a line.
 *
 * Every event, because the stand-in's stream carries one of each kind a screen
 * holds it for, and each adapter reading the stream reads its own kind of them.
 */
function anEventsData(string $stream): string
{
    preg_match_all('/^data: (.*)$/m', $stream, $data);

    return implode("\n", $data[1]);
}

/**
 * The stream an adapter is answered with: every envelope, each framed as an event of its own kind.
 *
 * @param list<array<mixed>> $envelopes
 */
function aStreamOf(array $envelopes): string
{
    $said = '';

    foreach ($envelopes as $envelope) {
        $kind = array_key_exists('kind', $envelope) && is_string($envelope['kind']) ? $envelope['kind'] : '';
        $said = sprintf("%sevent: %s\ndata: %s\n\n", $said, $kind, (string) json_encode($envelope));
    }

    return $said;
}

/**
 * Answer every request with the stand-in's body, spoiled at one path.
 *
 * @param list<int|string>|null $at
 */
function answerEverythingSpoiledAt(string $which, ?array $at): void
{
    MockClient::destroyGlobal();
    MockClient::global([
        '*' => static function (PendingRequest $asked) use ($which, $at): MockResponse {
            $endpoint = theAnswerACallIsGiven($which, $asked->getRequest()->resolveEndpoint());
            $envelopes = array_map(
                static fn(array $envelope): array => anEnvelopeSpoiledAt($envelope, $at),
                theEnvelopesACallIsSent($which, $asked->getRequest()->resolveEndpoint(), ...whatARequestAsked($asked)),
            );

            return match ($endpoint) {
                Api::LOGS_ENDPOINT => MockResponse::make(implode("\n", array_map(
                    static fn(array $envelope): string => (string) json_encode($envelope),
                    $envelopes,
                ))),
                Api::EVENTS_ENDPOINT => MockResponse::make(aStreamOf($envelopes)),
                default => MockResponse::make($envelopes[0]),
            };
        },
    ]);
}

/**
 * Every path one call's answers can be spoiled at, learnt by asking it once.
 *
 * The call is asked with nothing spoiled, and it has to read that answer: a
 * call whose reader was never reached would pass every spoiled case below by
 * never reading one.
 *
 * @param Closure(): object $ask
 * @return list<list<int|string>|null>
 */
function everySpoilingOf(string $which, Closure $ask): array
{
    $paths = [null];

    MockClient::destroyGlobal();
    MockClient::global([
        '*' => static function (PendingRequest $asked) use ($which, &$paths): MockResponse {
            $endpoint = theAnswerACallIsGiven($which, $asked->getRequest()->resolveEndpoint());
            $sent = theEnvelopesACallIsSent($which, $asked->getRequest()->resolveEndpoint(), ...whatARequestAsked($asked));

            foreach ($sent as $envelope) {
                foreach (everyPlaceToSpoil(theDataIn($envelope)) as $at) {
                    $paths[] = $at;
                }
            }

            return in_array($which, ['Guides::whatBecameOf', 'Resetters::whatBecameOf'], strict: true)
                ? MockResponse::make($sent[0])
                : theStandInsAnswerTo($endpoint, ...whatARequestAsked($asked));
        },
    ]);

    expect(carriesAnObstacle($ask()))->toBeFalse(sprintf('%s did not read the answer nothing spoiled', $which));

    return array_values(array_unique($paths, SORT_REGULAR));
}

/** Whether an outcome carries an obstacle rather than what was read. */
function carriesAnObstacle(object $outcome): bool
{
    return theObstacleIn($outcome) instanceof Obstacle;
}

/** The obstacle an outcome carries, or nothing where it carries what was read. */
function theObstacleIn(object $outcome): ?Obstacle
{
    return array_find(get_mangled_object_vars($outcome), static fn(mixed $held): bool => $held instanceof Obstacle);
}

/**
 * Answer every request as the SDK does when nothing picks up.
 *
 * The connection is refused before any answer exists, which is what a stack
 * that is off, asleep or out of reach looks like from the device. Raised as the
 * SDK raises it rather than as the transport's failure beneath it: a transport
 * failure at a pinned address is followed by the SDK asking the address what
 * certificate it presents, and that is a connection this suite may not open.
 */
function answerNothingAtAll(): void
{
    MockClient::destroyGlobal();
    MockClient::global([
        '*' => MockResponse::make()->throw(static fn(PendingRequest $asked): Unreachable
            => Unreachable::whenAsking($asked->getRequest()->resolveEndpoint(), 'Connection refused')),
    ]);
}

/**
 * Answer every request as the SDK does when the peer is not the paired machine.
 *
 * The SDK raises this from its transport, having refused the peer during the
 * handshake, so it is raised here where the transport would raise it.
 */
function answerAsAStranger(): void
{
    MockClient::destroyGlobal();
    MockClient::global([
        '*' => MockResponse::make()->throw(static fn(PendingRequest $asked): CertificateWasRefused
            => CertificateWasRefused::whenAsking(
                $asked->getRequest()->resolveEndpoint(),
                str_repeat('b', 64),
                str_repeat('f', 64),
            )),
    ]);
}

/**
 * What one call answered where nothing answered it, as a word a failure can name.
 *
 * @param Closure(): object $ask
 */
function whatACallAnsweredToNothing(Closure $ask): string
{
    answerNothingAtAll();

    return whatACallAnswered($ask);
}

/**
 * What one call answered where a stranger answered it, as a word a failure can name.
 *
 * @param Closure(): object $ask
 */
function whatACallAnsweredToAStranger(Closure $ask): string
{
    answerAsAStranger();

    return whatACallAnswered($ask);
}

/**
 * What one call answered with the answers already arranged.
 *
 * @param Closure(): object $ask
 */
function whatACallAnswered(Closure $ask): string
{
    try {
        $met = theObstacleIn($ask());

        return $met instanceof Obstacle ? $met->kind()->value : 'what was read';
    } catch (LogicException|RuntimeException|TypeError $escaped) {
        return sprintf('%s, escaped as an exception', $escaped::class);
    }
}

/**
 * What escaped one call with its answers spoiled at one path, or nothing.
 *
 * @param Closure(): object     $ask
 * @param list<int|string>|null $at
 */
function whatEscaped(string $which, Closure $ask, ?array $at): string
{
    answerEverythingSpoiledAt($which, $at);

    try {
        $ask();
    } catch (LogicException|RuntimeException|TypeError $escaped) {
        return sprintf(
            'data%s: %s',
            $at === null ? '' : implode('', array_map(static fn(int|string $key): string => sprintf('[%s]', $key), $at)),
            $escaped::class,
        );
    }

    return '';
}

it('turns every spoiled answer into an outcome rather than an exception', function (string $which, Closure $ask): void {
    $escaped = [];

    foreach (everySpoilingOf($which, $ask) as $at) {
        $said = whatEscaped($which, $ask, $at);

        if ($said !== '') {
            $escaped[] = $said;
        }
    }

    expect($escaped)->toBe([], sprintf(
        "%s let these escape as exceptions:\n  %s\n\n"
        . 'An adapter answers a stack it could not read with an obstacle. Catch what its '
        . 'reader raises, or have the reader refuse the answer with the exception the '
        . 'adapter already catches.',
        $which,
        implode("\n  ", $escaped),
    ));
})->with(static function (): Generator {
    foreach (EveryAdapterCallThatReads::all() as $which => $ask) {
        yield $which => [$which, $ask];
    }
});

it('answers a stack that could not be asked as one that did not answer', function (string $which, Closure $ask): void {
    expect(whatACallAnsweredToNothing($ask))->toEqual(KindOfObstacle::StackDidNotAnswer->value, sprintf(
        '%s did not answer a stack nothing answered for with the obstacle for one. An adapter '
        . 'catches what the SDK raises when nothing answers and answers with that obstacle, '
        . 'beside every other failure it answers the same way.',
        $which,
    ));
})->with(static function (): Generator {
    foreach (EveryAdapterCallThatReads::all() as $which => $ask) {
        yield $which => [$which, $ask];
    }
});

it('answers a machine that is not the one paired as exactly that', function (string $which, Closure $ask): void {
    expect(whatACallAnsweredToAStranger($ask))->toEqual(KindOfObstacle::StackIsNotTheOnePaired->value, sprintf(
        '%s did not answer a peer presenting another certificate with the obstacle for a stack that '
        . 'is not the one paired. An adapter catches the refusal the SDK raises for it beside the '
        . 'refusals it hands to the one place that decides what a refusal meant.',
        $which,
    ));
})->with(static function (): Generator {
    foreach (EveryAdapterCallThatReads::all() as $which => $ask) {
        yield $which => [$which, $ask];
    }
});

/**
 * The public calls of a reading adapter that ask the stack nothing, or whose
 * answer is not an outcome that can meet an obstacle, each with why, so the
 * list above holds only calls that can meet a stack and the rule below still
 * accounts for every public method.
 *
 * @return array<string, string>
 */
function adapterCallsThatAskNothing(): array
{
    return [
        'Listeners::letGo' => 'lets go of the stream it holds, which asks the stack nothing',
        'Narrators::letGo' => 'lets go of the stream it holds, which asks the stack nothing',
        'StartLines::letGo' => 'lets go of the stream it holds, which asks the stack nothing',
        'Assessors::aScreenOpens' => 'lets go of what any stack said longer ago than a break, which asks the stack nothing',
        'Assessors::askAgain' => 'lets go of what the stack last declared, which asks the stack nothing',
        'Assessors::forgetTheStack' => 'lets go of what the stack last declared, which asks the stack nothing',
        'Assessors::keepsAnythingOf' => 'says whether anything is held for the stack, which asks the stack nothing',
        // Not a call that asks nothing, and not one whose answer can carry an
        // obstacle: what a stack declares is asked so a button can be drawn,
        // and an answer that could not be read is read as not knowing, which
        // keeps the button. `AssessorsTest` holds every way that answer ends.
        'Assessors::whetherItOffers' => 'reads what the stack declares, and reads an answer it cannot read as not knowing rather than as an obstacle',
    ];
}

it('asks every adapter call that reads a stack', function (): void {
    // The list above is written out, because each call needs its own
    // arguments. This holds it to the adapters: every public method of a class
    // in `Modules\Sdk\Api` that opens a client, through the gate, itself or
    // through the streams it holds, is one of the calls asked.
    $expected = [];

    foreach (Tree::filesUnder(Tree::at('app-modules/sdk/src/Api'), '.php') as $file) {
        $source = (string) file_get_contents($file);

        if (! str_contains($source, 'GatedClient::of($this->clients') && ! str_contains($source, '$this->clients->client(') && ! str_contains($source, 'new TheStreamsHeld(')) {
            continue;
        }

        preg_match_all('/public function (\w+)\(/', $source, $methods);

        foreach ($methods[1] as $method) {
            if ($method !== '__construct') {
                $expected[] = sprintf('%s::%s', basename($file, '.php'), $method);
            }
        }
    }

    $asked = [...array_keys(EveryAdapterCallThatReads::all()), ...array_keys(adapterCallsThatAskNothing())];
    sort($expected);
    sort($asked);

    expect($expected)->not->toBe([], 'no adapter opens a client, so this rule read nothing')
        ->and($asked)->toBe($expected);
});
