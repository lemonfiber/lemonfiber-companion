<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AProblemListed;
use Modules\Kernel\Api\AReleaseListed;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ReadingNews;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheNewsOfAStack;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Kernel\Api\WhatTheStackListed;
use Modules\Sdk\Api\Newsreaders;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatListsWhatIsNew;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The ReadingNews contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `WelcomingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked what it lists. */
function aStackToAskForNews(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with, the requests unread. */
function theSameNews(): TheNewsOfAStack
{
    return new TheNewsOfAStack(
        WhatTheStackListed::these(
            AReleaseListed::versioned('0.18.0', WhatAReleaseDelivers::said('Plugins, part three')),
            AReleaseListed::versioned('0.17.2', WhatAReleaseDelivers::saidNothing()),
        ),
        WhatTheStackListed::unread(),
        WhatTheStackListed::these(new AProblemListed(Check::of('service.sonarr'), Instant::atEpochSeconds(1_759_400_000), 'Sonarr is stopped')),
    );
}

/**
 * The payload a stack sends for that, or with these requests, these kinds unread and this onset.
 *
 * @param list<array<string, mixed>> $requests
 * @param list<string>               $unread
 *
 * @return array<string, mixed>
 */
function whatAStackListsAsNew(array $requests = [], array $unread = ['requests'], string $onset = '1759400000'): array
{
    return [
        'api_version' => 1,
        'kind' => 'news-items',
        'data' => [
            'updates' => [
                ['version' => '0.18.0', 'delivers' => 'Plugins, part three'],
                ['version' => '0.17.2', 'delivers' => null],
            ],
            'requests' => $requests,
            'problems' => [['check' => 'service.sonarr', 'onset' => $onset, 'summary' => 'Sonarr is stopped']],
            'unread' => $unread,
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): ReadingNews>
 */
function everyWayOfAskingForNews(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): ReadingNews => $why instanceof Obstacle
            ? AStackThatListsWhatIsNew::listingNothing()->cannotBeReached(aStackToAskForNews()->id(), $why)
            : AStackThatListsWhatIsNew::listingNothing()->lists(aStackToAskForNews()->id(), theSameNews()),
        'the adapter' => static function () use ($answered): ReadingNews {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Newsreaders(new PinnedClients());
        },
    ];
}

/** Everything the reading says, folded to lines, so two answers can be compared. */
function everythingTheNewsSays(ReadingNews $reading): string
{
    return $reading->newsOn(aStackToAskForNews(), Session::of('a-session-not-a-secret'))->either(
        found: static function (TheNewsOfAStack $news): TheWordCarriedOut {
            $lines = [sprintf('updates %s', $news->releases()->wasRead() ? 'read' : 'unread')];

            foreach ($news->releases() as $release) {
                $lines[] = sprintf('%s|%s', $release->version(), $release->delivers()->either(
                    said: static fn(string $prose): TheWordCarriedOut => new TheWordCarriedOut($prose),
                    saidNothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'),
                )->said);
            }

            $lines[] = sprintf('requests %s', $news->requests()->wasRead() ? 'read' : 'unread');

            foreach ($news->requests() as $request) {
                $lines[] = sprintf('%d|%s|%s', $request->id()->number(), $request->title(), $request->by());
            }

            $lines[] = sprintf('problems %s', $news->problems()->wasRead() ? 'read' : 'unread');

            foreach ($news->problems() as $problem) {
                $lines[] = sprintf('%s|%d|%s', $problem->check()->shown(), $problem->onset()->epochSeconds(), $problem->summary());
            }

            return new TheWordCarriedOut(implode("\n", $lines));
        },
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('comes away with each kind newest first, and a kind the stack could not read as unread', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackListsAsNew()));

    foreach (everyWayOfAskingForNews($answered) as $which => $make) {
        expect(everythingTheNewsSays($make()))->toBe(
            "updates read\n0.18.0|Plugins, part three\n0.17.2|-\nrequests unread\nproblems read\nservice.sonarr|1759400000|Sonarr is stopped",
            $which,
        );
    }
});

it('reads a request by its number, its title and who asked', function (): void {
    $payload = whatAStackListsAsNew([['number' => 12, 'title' => 'Dune', 'by' => 'Anna'], ['number' => 7, 'title' => null, 'by' => 'Bram']], []);
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode($payload))]);

    expect(everythingTheNewsSays(new Newsreaders(new PinnedClients())))->toContain("requests read\n12|Dune|Anna\n7||Bram");
});

it('answers a stack that did not answer the same way through both', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    foreach (everyWayOfAskingForNews(MockResponse::make('not json at all'), $why) as $which => $make) {
        expect(everythingTheNewsSays($make()))->toBe(KindOfObstacle::StackDidNotAnswer->value, $which);
    }
});

it('refuses an onset that is not a number of seconds, rather than guessing when', function (): void {
    $payload = whatAStackListsAsNew(onset: 'yesterday');
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode($payload))]);

    expect(everythingTheNewsSays(new Newsreaders(new PinnedClients())))->toBe(KindOfObstacle::StackDidNotAnswer->value);
});

it('asks the news endpoint, and nothing else', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackListsAsNew()))]);

    everythingTheNewsSays(new Newsreaders(new PinnedClients()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toEndWith('/api/news')
        ->and($sent?->query()->all())->toBe([]);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('NewsItemsEnvelope', whatAStackListsAsNew()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
