<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\Unreachable;
use Modules\Dx\Internal\WhatTheWireWouldAnswer;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\Decided;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Undoing;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhatToSet;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Api\Adjustments;
use Modules\Sdk\Api\Advisers;
use Modules\Sdk\Api\Archivists;
use Modules\Sdk\Api\Arrangements;
use Modules\Sdk\Api\Copyists;
use Modules\Sdk\Api\Doorkeepers;
use Modules\Sdk\Api\Explainers;
use Modules\Sdk\Api\Followers;
use Modules\Sdk\Api\Heralds;
use Modules\Sdk\Api\Inspectors;
use Modules\Sdk\Api\Keepers;
use Modules\Sdk\Api\Keyholders;
use Modules\Sdk\Api\Lookouts;
use Modules\Sdk\Api\Menders;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Quartermasters;
use Modules\Sdk\Api\Questions;
use Modules\Sdk\Api\Recorders;
use Modules\Sdk\Api\Rehearsers;
use Modules\Sdk\Api\Requests;
use Modules\Sdk\Api\Scrollbacks;
use Modules\Sdk\Api\Shelves;
use Modules\Sdk\Api\Stalls;
use Modules\Sdk\Api\Storekeepers;
use Modules\Sdk\Api\Supervisors;
use Modules\Sdk\Api\Surveyors;
use Modules\Sdk\Api\TheirOwn;
use Modules\Sdk\Api\Upkeepers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\Tree;

// A malformed answer reaches a screen as an obstacle, whichever adapter read it,
// and so does a stack that could not be asked at all.
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
// answer exists, and that one has a single right answer: the obstacle for a
// stack that did not answer.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack every adapter here is pointed at. */
function aStackWhoseAnswersAreSpoiled(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.43:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

function theSessionSpoiledAnswersArriveOn(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** A confirmation that names a repair in the offer it came from. */
function aConfirmationToSpoilTheAnswerTo(): Confirmed
{
    $repair = Repair::offered(
        check: Check::of('storage.one-filesystem'),
        does: 'Move the library onto the larger disk',
        effects: Effects::of('Downloads pause while it moves'),
        undoing: Undoing::Possible,
    );
    $offer = Offer::of('an-agreement', Repairs::of($repair));

    return Confirmed::against($repair, $offer, Reading::live($offer));
}

/** An update a reading offered, moving one service. */
function anUpdateToSpoilTheAnswerTo(): TakingAnUpdate
{
    return TakingAnUpdate::offeredBy(Upkeep::reported(
        AgainstThePins::UpdatesAvailable,
        Releases::none(),
        Services::these(ServiceId::called('sonarr')),
        Services::none(),
        HowServicesTookIt::none(),
    ));
}

/**
 * Every adapter call that reads an answer, by what it asks.
 *
 * @return array<string, Closure(): object>
 */
function everyAdapterCallThatReads(): array
{
    $stack = aStackWhoseAnswersAreSpoiled();
    $session = theSessionSpoiledAnswersArriveOn();
    $clients = new PinnedClients();
    $entropy = SequencedEntropy::counting();

    return [
        'Adjustments::wouldBe' => static fn(): object
            => new Adjustments($clients)->wouldBe($stack, $session, WhatToSet::to('LIBRARY_PATH', '/data/films')),
        'Adjustments::agreedTo' => static fn(): object
            => new Adjustments($clients)->agreedTo($stack, $session, WhatToSet::to('LIBRARY_PATH', '/data/films')),
        'Advisers::advisedBy' => static fn(): object => new Advisers($clients)->advisedBy($stack, $session),
        'Archivists::declaredOn' => static fn(): object => new Archivists($clients)->declaredOn($stack, $session),
        'Arrangements::asItStands' => static fn(): object => new Arrangements($clients)->asItStands($stack, $session),
        'Copyists::copiesOn' => static fn(): object => new Copyists($clients)->copiesOn($stack, $session),
        'Doorkeepers::frontDoorOf' => static fn(): object => new Doorkeepers($clients)->frontDoorOf($stack, $session),
        'Explainers::glossaryOn' => static fn(): object => new Explainers($clients)->glossaryOn($stack, $session),
        'Followers::tracedOn' => static fn(): object
            => new Followers($clients)->tracedOn($stack, $session, WhatToFollow::called('sonarr')),
        'Heralds::toldAbout' => static fn(): object => new Heralds($clients)->toldAbout($stack, $session),
        'Inspectors::checkedOn' => static fn(): object => new Inspectors($clients)->checkedOn($stack, $session),
        'Keepers::keptRunningOn' => static fn(): object => new Keepers($clients)->keptRunningOn($stack, $session),
        'Keyholders::heldOn' => static fn(): object => new Keyholders($clients)->heldOn($stack, $session),
        'Lookouts::leaving' => static fn(): object => new Lookouts($clients)->leaving($stack, $session),
        'Menders::wouldPutRight' => static fn(): object => new Menders($clients, $entropy)->wouldPutRight($stack, $session),
        'Menders::agreeTo' => static fn(): object
            => new Menders($clients, $entropy)->agreeTo($stack, $session, aConfirmationToSpoilTheAnswerTo()),
        'Menders::whatWasDoneAbout' => static fn(): object
            => new Menders($clients, $entropy)->whatWasDoneAbout($stack, $session, Job::named('a-job')),
        'Menders::whatBecameOf' => static fn(): object
            => new Menders($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
        'Quartermasters::rationedOn' => static fn(): object => new Quartermasters($clients)->rationedOn($stack, $session),
        'Questions::about' => static fn(): object => new Questions($clients)->about($stack, $session),
        'Recorders::recordedOn' => static fn(): object => new Recorders($clients)->recordedOn($stack, $session),
        'Rehearsers::whatStarting' => static fn(): object
            => new Rehearsers($clients)->whatStarting($stack, $session, Form::called('media')),
        'Requests::askedOf' => static fn(): object => new Requests($clients, $entropy)->askedOf($stack, $session),
        'Requests::decided' => static fn(): object
            => new Requests($clients, $entropy)->decided($stack, $session, Decided::toApprove(RequestId::numbered(1))),
        'Scrollbacks::saidBy' => static fn(): object
            => new Scrollbacks($clients)->saidBy($stack, $session, ServiceId::called('sonarr'), HowManyLines::of(3)),
        'Shelves::theShelfOf' => static fn(): object
            => new Shelves($clients)->theShelfOf($stack, $session, Whose::member('robin')),
        'Stalls::stoppedOn' => static fn(): object => new Stalls($clients)->stoppedOn($stack, $session),
        'Storekeepers::storedOn' => static fn(): object => new Storekeepers($clients)->storedOn($stack, $session),
        'Supervisors::running' => static fn(): object => new Supervisors($clients, $entropy)->running($stack, $session),
        'Supervisors::told' => static fn(): object => new Supervisors($clients, $entropy)->told(
            $stack,
            $session,
            AgreedTo::theService(WhatToDoWithIt::Stop, ServiceId::called('sonarr')),
        ),
        'Surveyors::measuredOn' => static fn(): object => new Surveyors($clients)->measuredOn($stack, $session),
        'TheirOwn::toHandOver' => static fn(): object => new TheirOwn($clients)->toHandOver($stack, $session),
        'TheirOwn::whatTheyAsked' => static fn(): object => new TheirOwn($clients)->whatTheyAsked($stack, $session),
        'Upkeepers::standing' => static fn(): object => new Upkeepers($clients)->standing($stack, $session),
        'Upkeepers::take' => static fn(): object
            => new Upkeepers($clients)->take($stack, $session, anUpdateToSpoilTheAnswerTo()),
        'Upkeepers::whatBecameOf' => static fn(): object
            => new Upkeepers($clients)->whatBecameOf($stack, $session, Job::named('a-job')),
    ];
}

/**
 * The path whose answer a call is given, where it is not the one it asked.
 *
 * The stand-in answers a change to a setting as work, which leaves nothing
 * here for the reader to refuse, so the change is given the listing, reviewed.
 * The stand-in answers every job as a repair's, which an update's reader
 * refuses as the wrong kind, so an update's job is given the update's reading.
 */
function theAnswerACallIsGiven(string $which, string $asked): string
{
    return match (true) {
        str_starts_with($which, 'Adjustments::') => Api::CONFIG_ENDPOINT,
        $which === 'Upkeepers::whatBecameOf' => Api::UPDATE_ENDPOINT,
        default => $asked,
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
 * A list of envelopes for the scrollback, which is one document a line, and a
 * single envelope for every other path. What the request asked picks between
 * the envelopes one path answers with, as it does for the stand-in.
 *
 * @return list<array<mixed>>
 */
function theEnvelopesAPathSends(string $endpoint, string ...$asked): array
{
    $body = WhatTheWireWouldAnswer::to($endpoint, 200, ...$asked)->body()->all();

    if (is_array($body)) {
        return [$body];
    }

    $lines = [];

    foreach (explode("\n", trim(is_string($body) ? $body : '')) as $line) {
        $read = json_decode($line, associative: true, flags: JSON_THROW_ON_ERROR);
        $lines[] = is_array($read) ? $read : [];
    }

    return $lines;
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
                theEnvelopesAPathSends($endpoint, ...whatARequestAsked($asked)),
            );

            return $endpoint === Api::LOGS_ENDPOINT
                ? MockResponse::make(implode("\n", array_map(
                    static fn(array $envelope): string => (string) json_encode($envelope),
                    $envelopes,
                )))
                : MockResponse::make($envelopes[0]);
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

            foreach (theEnvelopesAPathSends($endpoint, ...whatARequestAsked($asked)) as $envelope) {
                foreach (everyPlaceToSpoil(theDataIn($envelope)) as $at) {
                    $paths[] = $at;
                }
            }

            return WhatTheWireWouldAnswer::to($endpoint, 200, ...whatARequestAsked($asked));
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
 * What one call answered where nothing answered it, as a word a failure can name.
 *
 * @param Closure(): object $ask
 */
function whatACallAnsweredToNothing(Closure $ask): string
{
    answerNothingAtAll();

    try {
        $met = theObstacleIn($ask());

        return $met instanceof Obstacle ? $met->value : 'what was read';
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
    foreach (everyAdapterCallThatReads() as $which => $ask) {
        yield $which => [$which, $ask];
    }
});

it('answers a stack that could not be asked as one that did not answer', function (string $which, Closure $ask): void {
    expect(whatACallAnsweredToNothing($ask))->toBe(Obstacle::StackDidNotAnswer->value, sprintf(
        '%s did not answer a stack nothing answered for with the obstacle for one. An adapter '
        . 'catches what the SDK raises when nothing answers and answers with that obstacle, '
        . 'beside every other failure it answers the same way.',
        $which,
    ));
})->with(static function (): Generator {
    foreach (everyAdapterCallThatReads() as $which => $ask) {
        yield $which => [$which, $ask];
    }
});

it('asks every adapter call that reads a stack', function (): void {
    // The list above is written out, because each call needs its own
    // arguments. This holds it to the adapters: every public method of a class
    // in `Modules\Sdk\Api` that opens a client is one of the calls asked.
    $expected = [];

    foreach (Tree::filesUnder(Tree::at('app-modules/sdk/src/Api'), '.php') as $file) {
        $source = (string) file_get_contents($file);

        if (! str_contains($source, '$this->clients->client(')) {
            continue;
        }

        preg_match_all('/public function (\w+)\(/', $source, $methods);

        foreach ($methods[1] as $method) {
            if ($method !== '__construct') {
                $expected[] = sprintf('%s::%s', basename($file, '.php'), $method);
            }
        }
    }

    $asked = array_keys(everyAdapterCallThatReads());
    sort($expected);
    sort($asked);

    expect($expected)->not->toBe([], 'no adapter opens a client, so this rule read nothing')
        ->and($asked)->toBe($expected);
});
