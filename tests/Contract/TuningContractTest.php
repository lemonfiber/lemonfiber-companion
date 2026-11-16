<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Http\ActRequest;
use Modules\Kernel\Api\AChannel;
use Modules\Kernel\Api\AChannelsGuide;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ALiveStream;
use Modules\Kernel\Api\AnAiring;
use Modules\Kernel\Api\AProgramme;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheChannels;
use Modules\Kernel\Api\TheGuide;
use Modules\Kernel\Api\Tuning;
use Modules\Kernel\Api\WhatRenewingCameTo;
use Modules\Kernel\Api\WhatStoppingCameTo;
use Modules\Kernel\Api\WhatTuningCameTo;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Api\Broadcasters;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\ATunerThatAnswered;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The Tuning contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack live TV is asked of. */
function aStackWithATuner(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The door's certificate a stream is pinned to. */
function theDoorATunerServesAt(): string
{
    return str_repeat('c', Fingerprint::CHARACTERS);
}

/** Where the tuner's own stream is served, its pass in it. */
const TUNED_DIRECT = 'https://192.168.1.20:8920/media/live/s1/stream.ts?lfpass=p.1893456300.m';

/** Where a transcoded playlist of it is served, its pass in it. */
const TUNED_TRANSCODED = 'https://192.168.1.20:8920/media/live/s1/main.m3u8?lfpass=p.1893456300.m';

/** The two channels both implementations list: one with what is on now, one the guide says nothing of. */
function theSameChannels(): TheChannels
{
    return TheChannels::these(
        AChannel::showing(ChannelId::called('c1'), 'NPO 1', '1', AProgramme::of('The news', WhenItSays::at(Instant::atEpochSeconds(1893456000)), '6')),
        AChannel::withNothingSaid(ChannelId::called('c2'), 'Radio', ''),
    );
}

/** The guide both implementations answer: a channel with a gap between its programmes, and one whose guide holds nothing. */
function theSameGuide(): TheGuide
{
    return TheGuide::of(
        AChannelsGuide::of(
            ChannelId::called('c1'),
            'NPO 1',
            '1',
            WhenItSays::at(Instant::atEpochSeconds(1893470400)),
            AnAiring::of('The news', Instant::atEpochSeconds(1893454200), Instant::atEpochSeconds(1893456000), '6'),
            AnAiring::of('The quiz', Instant::atEpochSeconds(1893463200), Instant::atEpochSeconds(1893466800), ''),
        ),
        AChannelsGuide::of(ChannelId::called('c2'), 'Radio', '', WhenItSays::unsaid()),
    );
}

/** The live stream both implementations open. */
function theSameLiveStream(): ALiveStream
{
    return ALiveStream::opened('s1', TUNED_DIRECT, TUNED_TRANSCODED, Fingerprint::of(theDoorATunerServesAt()), Instant::atEpochSeconds(1893456300));
}

/**
 * The `channels` payload a stack sends for those channels.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackListsOnItsChannels(array $changed = []): array
{
    return [
        'api_version' => 1,
        'kind' => 'channels',
        'data' => [
            'id' => 'u1',
            'member' => 'ana',
            'rehearsed' => false,
            'channels' => [
                ['id' => 'c1', 'name' => 'NPO 1', 'number' => '1', 'now' => ['title' => 'The news', 'ends' => 1893456000, 'certificate' => '6']],
                ['id' => 'c2', 'name' => 'Radio', 'number' => null, 'now' => null],
            ],
            ...$changed,
        ],
    ];
}

/**
 * The `guide` payload a stack sends for that guide.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackGuides(array $changed = []): array
{
    return [
        'api_version' => 1,
        'kind' => 'guide',
        'data' => [
            'id' => 'u1',
            'member' => 'ana',
            'rehearsed' => false,
            'channels' => [
                ['id' => 'c1', 'name' => 'NPO 1', 'number' => '1', 'guide_until' => 1893470400, 'programmes' => [
                    ['title' => 'The news', 'starts' => 1893454200, 'ends' => 1893456000, 'certificate' => '6'],
                    ['title' => 'The quiz', 'starts' => 1893463200, 'ends' => 1893466800, 'certificate' => null],
                ]],
                ['id' => 'c2', 'name' => 'Radio', 'number' => null, 'guide_until' => null, 'programmes' => []],
            ],
            ...$changed,
        ],
    ];
}

/**
 * The `tuned` payload a stack sends for that stream.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackTunedTo(array $changed = []): array
{
    return [
        'api_version' => 1,
        'kind' => 'tuned',
        'data' => [
            'channel' => 'c1',
            'member' => 'ana',
            'rehearsed' => false,
            'stream' => 's1',
            'direct' => TUNED_DIRECT,
            'transcoded' => TUNED_TRANSCODED,
            'fingerprint' => theDoorATunerServesAt(),
            'lapses' => 1893456300,
            ...$changed,
        ],
    ];
}

/** The adapter, answering with these. */
function aBroadcasterAnswering(MockResponse ...$answered): Tuning
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Broadcasters(new PinnedClients(), SequencedEntropy::counting());
}

/** What the channels came to, folded to text. */
function everythingOnTheChannels(Tuning $tuning, ?Whose $whose = null): string
{
    return $tuning->theChannelsOf(aStackWithATuner(), Session::of('a-session-not-a-secret'), $whose ?? Whose::member('ana'))->either(
        told: static function (TheChannels $channels): TheWordCarriedOut {
            $said = [];

            foreach ($channels as $channel) {
                $said[] = sprintf(
                    '%s "%s" #%s: %s',
                    $channel->id()->named(),
                    $channel->name(),
                    $channel->number(),
                    $channel->now(
                        showing: static fn(AProgramme $now): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
                            '%s until %s (%s)',
                            $now->title(),
                            $now->ends()->either(at: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut((string) $at->epochSeconds()), unsaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('?'))->said,
                            $now->certificate(),
                        )),
                        nothingSaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing said'),
                    )->said,
                );
            }

            return new TheWordCarriedOut(implode('; ', $said));
        },
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/** What the guide came to, folded to text. */
function everythingInTheGuide(Tuning $tuning, ?Whose $whose = null): string
{
    return $tuning->theGuideOf(aStackWithATuner(), Session::of('a-session-not-a-secret'), $whose ?? Whose::member('ana'))->either(
        told: static function (TheGuide $guide): TheWordCarriedOut {
            $said = [];

            foreach ($guide as $channel) {
                $airings = array_map(
                    static fn(AnAiring $airing): string => sprintf('%s %d-%d (%s)', $airing->title(), $airing->starts()->epochSeconds(), $airing->ends()->epochSeconds(), $airing->certificate()),
                    iterator_to_array($channel->airings(), preserve_keys: false),
                );
                $said[] = sprintf(
                    '%s "%s" #%s until %s: %s',
                    $channel->id()->named(),
                    $channel->name(),
                    $channel->number(),
                    $channel->until()->either(at: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut((string) $at->epochSeconds()), unsaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('?'))->said,
                    implode(', ', $airings),
                );
            }

            return new TheWordCarriedOut(implode('; ', $said));
        },
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/** What tuning came to, folded to text. */
function whatTuningCameTo(WhatTuningCameTo $came): string
{
    return $came->either(
        tuned: static fn(ALiveStream $stream): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s at [%s] [%s] pinned %s until %d',
            $stream->stream(),
            $stream->directForThePlayer(),
            $stream->transcodedForThePlayer(),
            $stream->door()->forThePlayer(),
            $stream->lapses()->epochSeconds(),
        )),
        cannot: static fn(Sentence $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('cannot: %s', $why->shown())),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/** What renewing came to, folded to text. */
function whatRenewingCameTo(WhatRenewingCameTo $came): string
{
    return $came->either(
        renewed: static fn(Instant $lapses): TheWordCarriedOut => new TheWordCarriedOut(sprintf('until %d', $lapses->epochSeconds())),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/** What stopping came to, folded to text. */
function whatStoppingCameTo(WhatStoppingCameTo $came): string
{
    return $came->either(
        stopped: static fn(): TheWordCarriedOut => new TheWordCarriedOut('stopped'),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('comes away with each channel, its number, and what is on it now until when, or that nothing is said', function (): void {
    $said = [
        'the fake' => everythingOnTheChannels(ATunerThatAnswered::listing(theSameChannels())),
        'the adapter' => everythingOnTheChannels(aBroadcasterAnswering(MockResponse::make((string) json_encode(whatAStackListsOnItsChannels())))),
    ];

    expect($said['the adapter'])->toBe($said['the fake'])
        ->and($said['the fake'])->toBe('c1 "NPO 1" #1: The news until 1893456000 (6); c2 "Radio" #: nothing said');
});

it('asks for the channels as the member', function (): void {
    $asked = null;
    MockClient::destroyGlobal();
    MockClient::global(['*' => static function (PendingRequest $request) use (&$asked): MockResponse {
        $asked = [$request->getRequest()->resolveEndpoint(), $request->query()->all()];

        return MockResponse::make((string) json_encode(whatAStackListsOnItsChannels()));
    }]);

    everythingOnTheChannels(new Broadcasters(new PinnedClients(), SequencedEntropy::counting()));

    expect($asked)->toBe(['/api/channels', ['member' => 'ana']]);
});

it('comes away with each channel\'s programmes across the window, gaps left as gaps, and where its guide ends', function (): void {
    $said = [
        'the fake' => everythingInTheGuide(ATunerThatAnswered::listing(theSameChannels())->guiding(theSameGuide())),
        'the adapter' => everythingInTheGuide(aBroadcasterAnswering(MockResponse::make((string) json_encode(whatAStackGuides())))),
    ];

    expect($said['the adapter'])->toBe($said['the fake'])
        ->and($said['the fake'])->toBe('c1 "NPO 1" #1 until 1893470400: The news 1893454200-1893456000 (6), The quiz 1893463200-1893466800 (); c2 "Radio" # until ?: ');
});

it('asks for the guide as the member', function (): void {
    $asked = null;
    MockClient::destroyGlobal();
    MockClient::global(['*' => static function (PendingRequest $request) use (&$asked): MockResponse {
        $asked = [$request->getRequest()->resolveEndpoint(), $request->query()->all()];

        return MockResponse::make((string) json_encode(whatAStackGuides()));
    }]);

    everythingInTheGuide(new Broadcasters(new PinnedClients(), SequencedEntropy::counting()));

    expect($asked)->toBe(['/api/guide', ['member' => 'ana']]);
});

it('reads a guide with no channels as an answer', function (): void {
    expect(everythingInTheGuide(aBroadcasterAnswering(MockResponse::make((string) json_encode(whatAStackGuides(['channels' => []]))))))
        ->toBe('')
        ->and(everythingInTheGuide(ATunerThatAnswered::listing(theSameChannels())))->toBe('');
});

it('reads no channels as an answer, which is a house without live TV', function (): void {
    expect(everythingOnTheChannels(aBroadcasterAnswering(MockResponse::make((string) json_encode(whatAStackListsOnItsChannels(['channels' => []]))))))
        ->toBe('')
        ->and(everythingOnTheChannels(ATunerThatAnswered::listing(TheChannels::these())))->toBe('');
});

it('asks the operator nothing, who has no account to watch on', function (): void {
    foreach (['the fake' => ATunerThatAnswered::listing(theSameChannels()), 'the adapter' => aBroadcasterAnswering()] as $which => $tuning) {
        $session = Session::of('a-session-not-a-secret');
        $operator = Whose::theOperator();

        expect(everythingOnTheChannels($tuning, $operator))->toBe(KindOfObstacle::NotForThisAccount->value, $which)
            ->and(everythingInTheGuide($tuning, $operator))->toBe(KindOfObstacle::NotForThisAccount->value, $which)
            ->and(whatTuningCameTo($tuning->tune(aStackWithATuner(), $session, $operator, ChannelId::called('c1'))))->toBe(KindOfObstacle::NotForThisAccount->value, $which)
            ->and(whatRenewingCameTo($tuning->renew(aStackWithATuner(), $session, $operator, theSameLiveStream())))->toBe(KindOfObstacle::NotForThisAccount->value, $which)
            ->and(whatStoppingCameTo($tuning->stop(aStackWithATuner(), $session, $operator, theSameLiveStream())))->toBe(KindOfObstacle::NotForThisAccount->value, $which);
    }
});

it('opens a channel at both addresses, pinned, until the lapse, and renews and stops it', function (): void {
    $fake = ATunerThatAnswered::listing(theSameChannels())
        ->tuning(WhatTuningCameTo::tuned(theSameLiveStream()))
        ->renewing(WhatRenewingCameTo::renewedUntil(Instant::atEpochSeconds(1893456600)));
    $adapter = aBroadcasterAnswering(
        MockResponse::make((string) json_encode(whatAStackTunedTo())),
        MockResponse::make((string) json_encode(whatAStackTunedTo(['lapses' => 1893456600]))),
        MockResponse::make((string) json_encode(whatAStackTunedTo(['direct' => null, 'transcoded' => null, 'fingerprint' => null, 'lapses' => null]))),
    );
    $session = Session::of('a-session-not-a-secret');
    $ana = Whose::member('ana');

    foreach (['the fake' => $fake, 'the adapter' => $adapter] as $which => $tuning) {
        expect(whatTuningCameTo($tuning->tune(aStackWithATuner(), $session, $ana, ChannelId::called('c1'))))
            ->toBe(sprintf('s1 at [%s] [%s] pinned %s until 1893456300', TUNED_DIRECT, TUNED_TRANSCODED, theDoorATunerServesAt()), $which)
            ->and(whatRenewingCameTo($tuning->renew(aStackWithATuner(), $session, $ana, theSameLiveStream())))->toBe('until 1893456600', $which)
            ->and(whatStoppingCameTo($tuning->stop(aStackWithATuner(), $session, $ana, theSameLiveStream())))->toBe('stopped', $which);
    }

    expect($fake->tuned())->toBe(['c1'])
        ->and($fake->renewed())->toBe(['s1'])
        ->and($fake->stopped())->toBe(['s1']);
});

it('sends the channel to open, and the stream to renew and to stop', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackTunedTo()));
    $tuning = aBroadcasterAnswering($answered, $answered, $answered);
    $session = Session::of('a-session-not-a-secret');
    $ana = Whose::member('ana');
    $sent = [];

    foreach ([
        static fn(): object => $tuning->tune(aStackWithATuner(), $session, $ana, ChannelId::called('c1')),
        static fn(): object => $tuning->renew(aStackWithATuner(), $session, $ana, theSameLiveStream()),
        static fn(): object => $tuning->stop(aStackWithATuner(), $session, $ana, theSameLiveStream()),
    ] as $press) {
        $press();
        $last = MockClient::getGlobal()?->getLastRequest();
        $sent[] = $last instanceof ActRequest ? [$last->resolveEndpoint(), $last->body()->all()] : ['', null];
    }

    expect($sent)->toBe([
        ['/api/actions/tune', ['id' => 'c1', 'stream' => null, 'stop' => null]],
        ['/api/actions/tune', ['id' => null, 'stream' => 's1', 'stop' => null]],
        ['/api/actions/tune', ['id' => null, 'stream' => 's1', 'stop' => true]],
    ]);
});

it('says the core\'s own words where it locates nothing, as away from home', function (): void {
    $away = ['code' => 'PLAY-4', 'said' => 'Live TV plays only at home.', 'remedy' => 'Connect to the home network.'];

    expect(whatTuningCameTo(aBroadcasterAnswering(MockResponse::make((string) json_encode(whatAStackTunedTo(['direct' => null, 'transcoded' => null, 'unlocated' => $away]))))->tune(aStackWithATuner(), Session::of('a-session-not-a-secret'), Whose::member('ana'), ChannelId::called('c1'))))
        ->toBe('cannot: Live TV plays only at home.')
        ->and(whatTuningCameTo(ATunerThatAnswered::listing(theSameChannels())->tuning(WhatTuningCameTo::cannot(Sentence::of('Live TV plays only at home.')))->tune(aStackWithATuner(), Session::of('a-session-not-a-secret'), Whose::member('ana'), ChannelId::called('c1'))))
        ->toBe('cannot: Live TV plays only at home.');
});

it('plays from whichever address is served, where only one is', function (array $changed, string $direct, string $transcoded): void {
    expect(whatTuningCameTo(aBroadcasterAnswering(MockResponse::make((string) json_encode(whatAStackTunedTo($changed))))->tune(aStackWithATuner(), Session::of('a-session-not-a-secret'), Whose::member('ana'), ChannelId::called('c1'))))
        ->toBe(sprintf('s1 at [%s] [%s] pinned %s until 1893456300', $direct, $transcoded, theDoorATunerServesAt()));
})->with([
    'only the tuner\'s own' => [['transcoded' => null], TUNED_DIRECT, ''],
    'only the transcoded playlist' => [['direct' => null], '', TUNED_TRANSCODED],
]);

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];
    $session = Session::of('a-session-not-a-secret');
    $ana = Whose::member('ana');

    foreach ($table as [$answered, $why]) {
        foreach (['the fake' => static fn(): Tuning => ATunerThatAnswered::met($why), 'the adapter' => static fn(): Tuning => aBroadcasterAnswering($answered, $answered, $answered, $answered, $answered)] as $which => $make) {
            $tuning = $make();
            $label = sprintf('%s / %s', $which, $why->kind()->value);

            expect(everythingOnTheChannels($tuning))->toBe($why->kind()->value, $label)
                ->and(everythingInTheGuide($tuning))->toBe($why->kind()->value, $label)
                ->and(whatTuningCameTo($tuning->tune(aStackWithATuner(), $session, $ana, ChannelId::called('c1'))))->toBe($why->kind()->value, $label)
                ->and(whatRenewingCameTo($tuning->renew(aStackWithATuner(), $session, $ana, theSameLiveStream())))->toBe($why->kind()->value, $label)
                ->and(whatStoppingCameTo($tuning->stop(aStackWithATuner(), $session, $ana, theSameLiveStream())))->toBe($why->kind()->value, $label);
        }
    }
});

// `channels` is stood in for and not judged: each case is a payload the contract does not describe, which no stack sends.
it('refuses channels it cannot read, rather than leaving one out', function (array $changed): void {
    expect(everythingOnTheChannels(aBroadcasterAnswering(MockResponse::make((string) json_encode(whatAStackListsOnItsChannels($changed))))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'no channels' => [['channels' => null]],
    'a channel that is not an object' => [['channels' => ['c1']]],
    'a channel with no id' => [['channels' => [['id' => ' ', 'name' => 'NPO 1']]]],
    'a channel with no name' => [['channels' => [['id' => 'c1']]]],
    'a number that is not text' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'number' => 1]]]],
    'a programme that is not an object' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'now' => 'The news']]]],
    'a programme with no title' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'now' => ['ends' => 1]]]]],
    'an end before the epoch' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'now' => ['title' => 'The news', 'ends' => -1]]]]],
    'an end that is not a moment' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'now' => ['title' => 'The news', 'ends' => 'soon']]]]],
    'a certificate that is not text' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'now' => ['title' => 'The news', 'certificate' => 6]]]]],
]);

// `guide` is stood in for and not judged, for the same reason.
it('refuses a guide it cannot read, rather than drawing a gap the core did not leave', function (array $changed): void {
    expect(everythingInTheGuide(aBroadcasterAnswering(MockResponse::make((string) json_encode(whatAStackGuides($changed))))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'no channels' => [['channels' => null]],
    'a channel that is not an object' => [['channels' => ['c1']]],
    'a channel with no id' => [['channels' => [['id' => ' ', 'name' => 'NPO 1', 'programmes' => []]]]],
    'a channel with no name' => [['channels' => [['id' => 'c1', 'programmes' => []]]]],
    'a number that is not text' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'number' => 1, 'programmes' => []]]]],
    'no programmes' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1']]]],
    'an end of the guide that is not a moment' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'guide_until' => 'later', 'programmes' => []]]]],
    'a programme that is not an object' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'programmes' => ['The news']]]]],
    'a programme with no title' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'programmes' => [['starts' => 1, 'ends' => 2]]]]]],
    'a programme with no start' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'programmes' => [['title' => 'The news', 'ends' => 2]]]]]],
    'a programme with no end' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'programmes' => [['title' => 'The news', 'starts' => 1]]]]]],
    'a start before the epoch' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'programmes' => [['title' => 'The news', 'starts' => -1, 'ends' => 2]]]]]],
    'a certificate that is not text' => [['channels' => [['id' => 'c1', 'name' => 'NPO 1', 'programmes' => [['title' => 'The news', 'starts' => 1, 'ends' => 2, 'certificate' => 6]]]]]],
]);

// `tuned` is stood in for and not judged, for the same reason.
it('refuses a stream it cannot hand a player, rather than playing from somewhere the core did not say', function (array $changed): void {
    expect(whatTuningCameTo(aBroadcasterAnswering(MockResponse::make((string) json_encode(whatAStackTunedTo($changed))))->tune(aStackWithATuner(), Session::of('a-session-not-a-secret'), Whose::member('ana'), ChannelId::called('c1'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'no stream to renew it by' => [['stream' => null]],
    'nowhere to play it' => [['direct' => null, 'transcoded' => null]],
    'an address off the door' => [['direct' => 'http://192.168.1.20:8920/stream.ts?lfpass=p']],
    'a transcoded address off the door' => [['transcoded' => 'ftp://192.168.1.20/main.m3u8']],
    'no door to pin' => [['fingerprint' => null]],
    'a door that is not a fingerprint' => [['fingerprint' => 'abc']],
    'no lapse' => [['lapses' => null]],
    'a lapse before the epoch' => [['lapses' => -1]],
    'an address that is not text' => [['direct' => 7]],
    'an unlocated that says nothing' => [['unlocated' => ['code' => 'PLAY-4', 'said' => '', 'remedy' => '']]],
]);

it('refuses a renewal it cannot read, rather than playing on past the lapse', function (array $changed): void {
    expect(whatRenewingCameTo(aBroadcasterAnswering(MockResponse::make((string) json_encode(whatAStackTunedTo($changed))))->renew(aStackWithATuner(), Session::of('a-session-not-a-secret'), Whose::member('ana'), theSameLiveStream())))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'no lapse' => [['lapses' => null]],
    'a lapse that is not a moment' => [['lapses' => 'later']],
    'a lapse before the epoch' => [['lapses' => -5]],
]);

it('refuses a stop it cannot read, rather than saying the stream was closed', function (array $answered): void {
    expect(whatStoppingCameTo(aBroadcasterAnswering(MockResponse::make((string) json_encode($answered)))->stop(aStackWithATuner(), Session::of('a-session-not-a-secret'), Whose::member('ana'), theSameLiveStream())))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'no payload' => [[...whatAStackTunedTo(), 'data' => null]],
    'a payload that is not a table' => [[...whatAStackTunedTo(), 'data' => 'closed']],
]);

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ChannelsEnvelope', whatAStackListsOnItsChannels()))
        ->toBe([], "The channels payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('GuideEnvelope', whatAStackGuides()))
        ->toBe([], "The guide payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('TunedEnvelope', whatAStackTunedTo()))
        ->toBe([], "The tuned payload this suite stands in for a stack with is not one a stack would send.\n");
});
