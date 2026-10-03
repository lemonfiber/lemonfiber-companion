<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\ADownloadLetGo;
use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowLettingItGoIsGoing;
use Modules\Kernel\Api\HowTheOfferToLetGoIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\StoppingSeeding;
use Modules\Kernel\Api\WhatLettingItGoCosts;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Releasers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatStopsSeeding;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The StoppingSeeding contract, run against the adapter and against the fake.
//
// Four questions of one port: asking what it would cost, what that came to,
// the yes, and what became of it. `PuttingBackContractTest`'s shape, with the
// offer arriving as a job too.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a download is let go on. */
function aStackThatSeeds(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers about letting a download go, changed where a case says.
 *
 * @param  array<mixed>      $download the download's own fields that differ
 * @param  array<mixed>      $changed  the offer's own fields that differ
 * @param  array<mixed>|null $gone     what became of it, where the offer was answered
 * @return array<string, mixed>
 */
function whatAStackSaysOfLettingItGo(array $download = [], array $changed = [], ?array $gone = null): array
{
    return ['api_version' => 1, 'kind' => 'stop-seeding', 'data' => [
        'rehearsed' => false,
        'agreement' => 'stop-seeding-show-season1-4000',
        'download' => [
            'bytes' => 4_000,
            'consequence' => 'Your ratio on it stops growing',
            'name' => 'Show.Season1',
            'standing' => ['standing' => 'seeding', 'ratio' => 80],
            ...$download,
        ],
        'goes' => 'The copy in the downloads tree goes with it',
        'gone' => $gone,
        ...$changed,
    ]];
}

/** The offer that payload stands for, as the fake is handed it. */
function theSameOfferToLetGo(): WhatLettingItGoCosts
{
    return WhatLettingItGoCosts::offered(
        ADownloadOnDisk::seeding('Show.Season1', 4_000, ARatio::inHundredths(80), 'Your ratio on it stops growing'),
        'The copy in the downloads tree goes with it',
        'stop-seeding-show-season1-4000',
    );
}

/**
 * Both ways of stopping seeding, each set up to say the same.
 *
 * @return array<string, Closure(): StoppingSeeding>
 */
function everyWayOfStoppingSeeding(MockResponse $answered, HowTheOfferToLetGoIsGoing $offer, HowLettingItGoIsGoing $became): array
{
    return [
        'the fake' => static fn(): StoppingSeeding => AStackThatStopsSeeding::offering($offer, $became),
        'the adapter' => static function () use ($answered): StoppingSeeding {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Releasers(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** The adapter, answered with one response. */
function aReleaserAnswered(MockResponse $answered): StoppingSeeding
{
    MockClient::destroyGlobal();
    MockClient::global([$answered]);

    return new Releasers(new PinnedClients(), SequencedEntropy::counting());
}

/** A download's standing and ratio, as a line. */
function whereTheDownloadLetGoStands(ADownloadOnDisk $download): string
{
    return $download->ratio(
        seeding: static fn(ARatio $ratio): TheWordCarriedOut => $ratio->either(
            read: static fn(string $read): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s at %s', $download->stands()->value, $read)),
            none: static fn(): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s, no ratio', $download->stands()->value)),
        ),
        notSeeding: static fn(): TheWordCarriedOut => new TheWordCarriedOut($download->stands()->value),
    )->said;
}

/** What asking what it would cost came to, as a line. */
function howAskingWhatItCostsWent(StoppingSeeding $stopping): string
{
    return $stopping->whatItWouldCost(aStackThatSeeds(), Session::of('a-session-not-a-secret'), ADownloadHeld::named('Show.Season1'))->either(
        started: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What the offer came to, every part folded to one line. */
function whatTheOfferToLetGoSaid(StoppingSeeding $stopping): string
{
    return $stopping->whatTheOfferCameTo(aStackThatSeeds(), Session::of('a-session-not-a-secret'), Job::named(AStackThatStopsSeeding::THE_OFFER))->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('still running'),
        offering: static fn(WhatLettingItGoCosts $offer): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s|%d|%s|%s|%s|%s',
            $offer->download()->name(),
            $offer->download()->bytes(),
            whereTheDownloadLetGoStands($offer->download()),
            $offer->download()->consequence(),
            $offer->goes(),
            $offer->agreement(),
        )),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What the yes came to, as a line. */
function howStoppingSeedingWasAgreed(StoppingSeeding $stopping): string
{
    return $stopping->stop(aStackThatSeeds(), Session::of('a-session-not-a-secret'), theSameOfferToLetGo())->either(
        started: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What became of the yes, every part folded to one line. */
function whatBecameOfLettingItGo(StoppingSeeding $stopping): string
{
    return $stopping->whatBecameOf(aStackThatSeeds(), Session::of('a-session-not-a-secret'), Job::named(AStackThatStopsSeeding::THE_JOB))->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('still running'),
        done: static fn(ADownloadLetGo $gone): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s|%d|%s',
            $gone->name(),
            $gone->bytes(),
            $gone->wasRehearsed()->value,
        )),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What a stack answers a request it took on with, naming the work. */
function aStopTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'stop-seeding', 'job' => $job]]), 202);
}

it('comes away from asking what it would cost with the job the stack named', function (): void {
    foreach (everyWayOfStoppingSeeding(aStopTakenOn(AStackThatStopsSeeding::THE_OFFER), HowTheOfferToLetGoIsGoing::stillRunning(), HowLettingItGoIsGoing::stillRunning()) as $which => $build) {
        expect(howAskingWhatItCostsWent($build()))->toBe(sprintf('following %s', AStackThatStopsSeeding::THE_OFFER), $which);
    }
});

it('comes away from asking with the obstacle rather than a job where the stack would not take it on', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"nothing holds it"}', 422), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [aStopTakenOn(' '), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        expect(howAskingWhatItCostsWent(aReleaserAnswered($answered)))->toBe($why->kind()->name)
            ->and(howAskingWhatItCostsWent(AStackThatStopsSeeding::met($why)))->toBe($why->kind()->name);
    }
});

it('reads every part of the offer: the download, where it stands, its ratio, what it costs, what goes and its name', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfLettingItGo()));

    foreach (everyWayOfStoppingSeeding($finished, HowTheOfferToLetGoIsGoing::offering(theSameOfferToLetGo()), HowLettingItGoIsGoing::stillRunning()) as $which => $build) {
        expect(whatTheOfferToLetGoSaid($build()))
            ->toBe('Show.Season1|4000|seeding at 0.80|Your ratio on it stops growing|The copy in the downloads tree goes with it|stop-seeding-show-season1-4000', $which);
    }
});

it('reads each standing as the stack gave it, and a cost it did not state as nothing', function (array $download, string $said): void {
    expect(whatTheOfferToLetGoSaid(aReleaserAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfLettingItGo($download))))))->toBe($said);
})->with([
    'never imported' => [['standing' => ['standing' => 'never_imported']], 'Show.Season1|4000|never_imported|Your ratio on it stops growing|The copy in the downloads tree goes with it|stop-seeding-show-season1-4000'],
    'left alone' => [['standing' => ['standing' => 'left_alone']], 'Show.Season1|4000|left_alone|Your ratio on it stops growing|The copy in the downloads tree goes with it|stop-seeding-show-season1-4000'],
    'seeding, at nought' => [['standing' => ['standing' => 'seeding', 'ratio' => 0]], 'Show.Season1|4000|seeding at 0.00|Your ratio on it stops growing|The copy in the downloads tree goes with it|stop-seeding-show-season1-4000'],
    'seeding with nothing downloaded to divide by' => [['standing' => ['standing' => 'seeding', 'ratio' => 4_294_967_295]], 'Show.Season1|4000|seeding, no ratio|Your ratio on it stops growing|The copy in the downloads tree goes with it|stop-seeding-show-season1-4000'],
    'a cost said as null' => [['consequence' => null], 'Show.Season1|4000|seeding at 0.80||The copy in the downloads tree goes with it|stop-seeding-show-season1-4000'],
    'nothing occupied' => [['bytes' => 0], 'Show.Season1|0|seeding at 0.80|Your ratio on it stops growing|The copy in the downloads tree goes with it|stop-seeding-show-season1-4000'],
]);

it('reads an offer that leaves the cost out altogether as saying nothing', function (): void {
    $payload = ['api_version' => 1, 'kind' => 'stop-seeding', 'data' => [
        'agreement' => 'stop-seeding-show-season1-4000',
        'download' => ['bytes' => 4_000, 'name' => 'Show.Season1', 'standing' => ['standing' => 'seeding', 'ratio' => 80]],
        'goes' => 'The copy in the downloads tree goes with it',
    ]];

    expect(whatTheOfferToLetGoSaid(aReleaserAnswered(MockResponse::make((string) json_encode($payload)))))
        ->toBe('Show.Season1|4000|seeding at 0.80||The copy in the downloads tree goes with it|stop-seeding-show-season1-4000');
});

it('an offer this app cannot read is a stack that did not answer, never a shorter offer', function (array $download, array $changed): void {
    expect(whatTheOfferToLetGoSaid(aReleaserAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfLettingItGo($download, $changed))))))
        ->toEqual(KindOfObstacle::StackDidNotAnswer->name);
})->with([
    'a blank agreement' => [[], ['agreement' => ' ']],
    'no agreement' => [[], ['agreement' => null]],
    'a blank account of what goes' => [[], ['goes' => ' ']],
    'a download that is not one' => [[], ['download' => 'Show.Season1']],
    'a download named nothing' => [['name' => ' '], []],
    'a download of no size' => [['bytes' => 'large'], []],
    'a download below nothing' => [['bytes' => -1], []],
    'a blank cost' => [['consequence' => ' '], []],
    'a standing that is not one' => [['standing' => 'seeding'], []],
    'a standing this app has no word for' => [['standing' => ['standing' => 'cross_seeded']], []],
    'a seeding download with no ratio' => [['standing' => ['standing' => 'seeding']], []],
]);

it('an answer that is not an offer at all is a stack that did not answer', function (): void {
    expect(whatTheOfferToLetGoSaid(aReleaserAnswered(MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'stop-seeding', 'data' => 'nothing'])))))
        ->toEqual(KindOfObstacle::StackDidNotAnswer->name);
});

it('an offer still being worked out is its own answer', function (): void {
    foreach (everyWayOfStoppingSeeding(aStopTakenOn(AStackThatStopsSeeding::THE_OFFER), HowTheOfferToLetGoIsGoing::stillRunning(), HowLettingItGoIsGoing::stillRunning()) as $which => $build) {
        expect(whatTheOfferToLetGoSaid($build()))->toBe('still running', $which);
    }
});

it('an offer the stack no longer has a job for is ended, not unreachable and not running', function (MockResponse $forgotten): void {
    foreach (everyWayOfStoppingSeeding($forgotten, HowTheOfferToLetGoIsGoing::ended(), HowLettingItGoIsGoing::ended()) as $which => $build) {
        expect(whatTheOfferToLetGoSaid($build()))->toBe('ended', $which);
    }
})->with([
    'let go of' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'stop-seeding', 'job' => AStackThatStopsSeeding::THE_OFFER]]))],
    'never known' => [MockResponse::make('{"error":"no such job"}', 404)],
]);

it('asking after an offer tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"nothing holds it"}', 422), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfStoppingSeeding($answered, HowTheOfferToLetGoIsGoing::met($why), HowLettingItGoIsGoing::met($why)) as $which => $build) {
            expect(whatTheOfferToLetGoSaid($build()))->toBe($why->kind()->name, $which);
        }
    }
});

it('comes away from a yes with the job the stack named', function (): void {
    foreach (everyWayOfStoppingSeeding(aStopTakenOn(AStackThatStopsSeeding::THE_JOB), HowTheOfferToLetGoIsGoing::stillRunning(), HowLettingItGoIsGoing::stillRunning()) as $which => $build) {
        expect(howStoppingSeedingWasAgreed($build()))->toBe(sprintf('following %s', AStackThatStopsSeeding::THE_JOB), $which);
    }
});

it('comes away from a refused yes with the obstacle rather than a job', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"another offer"}', 422), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [aStopTakenOn(' '), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        expect(howStoppingSeedingWasAgreed(aReleaserAnswered($answered)))->toBe($why->kind()->name)
            ->and(howStoppingSeedingWasAgreed(AStackThatStopsSeeding::offeringButRefusing(theSameOfferToLetGo(), $why)))->toBe($why->kind()->name);
    }
});

it('reads what became of the download, and whether it was only rehearsed', function (bool $rehearsed, WhetherItWasRehearsed $said): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfLettingItGo(gone: ['bytes' => 4_000, 'name' => 'Show.Season1', 'rehearsed' => $rehearsed])));
    $report = HowLettingItGoIsGoing::done(ADownloadLetGo::reported('Show.Season1', 4_000, $said));

    foreach (everyWayOfStoppingSeeding($finished, HowTheOfferToLetGoIsGoing::stillRunning(), $report) as $which => $build) {
        expect(whatBecameOfLettingItGo($build()))->toBe(sprintf('Show.Season1|4000|%s', $said->value), $which);
    }
})->with([
    'let go' => [false, WhetherItWasRehearsed::CarriedOut],
    'rehearsed' => [true, WhetherItWasRehearsed::Rehearsed],
]);

it('a finished yes that does not say what became of the download is a stack that did not answer', function (?array $gone): void {
    expect(whatBecameOfLettingItGo(aReleaserAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfLettingItGo(gone: $gone))))))
        ->toEqual(KindOfObstacle::StackDidNotAnswer->name);
})->with([
    'said as nothing' => [null],
    'without saying whether it was rehearsed' => [['bytes' => 4_000, 'name' => 'Show.Season1']],
    'naming nothing' => [['bytes' => 4_000, 'name' => ' ', 'rehearsed' => false]],
    'without a name at all' => [['bytes' => 4_000, 'rehearsed' => false]],
    'of a size below nothing' => [['bytes' => -1, 'name' => 'Show.Season1', 'rehearsed' => false]],
]);

it('stopping still running is its own answer', function (): void {
    foreach (everyWayOfStoppingSeeding(aStopTakenOn(AStackThatStopsSeeding::THE_JOB), HowTheOfferToLetGoIsGoing::stillRunning(), HowLettingItGoIsGoing::stillRunning()) as $which => $build) {
        expect(whatBecameOfLettingItGo($build()))->toBe('still running', $which);
    }
});

it('stopping the stack no longer has a job for is ended, not unreachable and not running', function (MockResponse $forgotten): void {
    foreach (everyWayOfStoppingSeeding($forgotten, HowTheOfferToLetGoIsGoing::ended(), HowLettingItGoIsGoing::ended()) as $which => $build) {
        expect(whatBecameOfLettingItGo($build()))->toBe('ended', $which);
    }
})->with([
    'let go of' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'stop-seeding', 'job' => AStackThatStopsSeeding::THE_JOB]]))],
    'never known' => [MockResponse::make('{"error":"no such job"}', 404)],
]);

it('asking after stopping tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"still held"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfStoppingSeeding($answered, HowTheOfferToLetGoIsGoing::met($why), HowLettingItGoIsGoing::met($why)) as $which => $build) {
            expect(whatBecameOfLettingItGo($build()))->toBe($why->kind()->name, $which);
        }
    }
});

it('the fake remembers the download asked about, the offer read, the offer agreed to and the handle followed', function (): void {
    $stopping = AStackThatStopsSeeding::offering(HowTheOfferToLetGoIsGoing::offering(theSameOfferToLetGo()), HowLettingItGoIsGoing::stillRunning());
    howAskingWhatItCostsWent($stopping);
    whatTheOfferToLetGoSaid($stopping);
    howStoppingSeedingWasAgreed($stopping);
    whatBecameOfLettingItGo($stopping);

    expect(array_map(static fn(ADownloadHeld $download): string => $download->name(), $stopping->asked()))->toBe(['Show.Season1'])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $stopping->read()))->toBe([AStackThatStopsSeeding::THE_OFFER])
        ->and(array_map(static fn(WhatLettingItGoCosts $offer): string => $offer->agreement(), $stopping->agreed()))->toBe(['stop-seeding-show-season1-4000'])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $stopping->followed()))->toBe([AStackThatStopsSeeding::THE_JOB]);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('StopSeedingEnvelope', whatAStackSaysOfLettingItGo()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('StopSeedingEnvelope', whatAStackSaysOfLettingItGo(gone: ['bytes' => 4_000, 'name' => 'Show.Season1', 'rehearsed' => true])))
        ->toBe([]);
});
