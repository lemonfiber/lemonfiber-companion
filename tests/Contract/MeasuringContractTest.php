<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\ALineOfTheAccount;
use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\AVolume;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFreshAReadingIs;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Measuring;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheAccount;
use Modules\Kernel\Api\TheDownloadsOnDisk;
use Modules\Kernel\Api\TheVolumes;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatAVolumeHolds;
use Modules\Kernel\Api\WhatGettingItBackCosts;
use Modules\Kernel\Api\WhatItOccupies;
use Modules\Kernel\Api\WhereTheRoomStands;
use Modules\Kernel\Api\WhereTheRoomWent;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Surveyors;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatMeasuresItsRoom;
use Tests\Support\WhatTheContractAccepts;

// The Measuring contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `StoringContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked how full it is. */
function aStackThatFills(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameRoom(): WhereTheRoomWent
{
    return WhereTheRoomWent::measured(
        TheVolumes::of(
            AVolume::measured(WhatAVolumeHolds::Data, '/srv', AnAmountOfRoom::of(40_000_000_000, 'free'), AnAmountOfRoom::of(4_000_000_000_000, 'limit'), 60_000_000_000, AnAmountOfRoom::of(0, 'projected'), WhereTheRoomStands::Warning, HowFreshAReadingIs::live()),
            AVolume::measured(WhatAVolumeHolds::Services, '', AnAmountOfRoom::unread(), AnAmountOfRoom::unread(), 0, AnAmountOfRoom::unread(), WhereTheRoomStands::Unknown, HowFreshAReadingIs::asOf(Instant::atEpochSeconds(1_790_100_000))),
        ),
        WhereTheRoomStands::Warning,
        TheAccount::of(
            ALineOfTheAccount::forTheTree('movies', WhatItOccupies::counted(2_000_000_000_000, 1_500_000_000_000), WhatGettingItBackCosts::ByLosingContent),
            ALineOfTheAccount::for(WhatALineIsAbout::Orphaned, WhatItOccupies::counted(30_000_000_000, 30_000_000_000), WhatGettingItBackCosts::TheEasyWin),
        ),
        TheDownloadsOnDisk::of(
            ADownloadOnDisk::neverImported('Some.Show.S01E01', 2_000_000_000),
            ADownloadOnDisk::seeding('Some.Film.2024', 8_000_000_000, ARatio::inHundredths(125), 'Your ratio on that tracker stops growing'),
            ADownloadOnDisk::seeding('Old.Film.1999', 4_000_000_000, ARatio::none(), 'Your ratio on that tracker stops growing'),
            ADownloadOnDisk::leftAlone('Kept.Show.S02', 1_000_000_000),
        ),
        halted: false,
    );
}

/**
 * The payload a stack sends for that.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsRoom(string $level = 'warning'): array
{
    return [
        'api_version' => 1,
        'kind' => 'space',
        'data' => [
            'volumes' => [
                ['role' => 'data', 'at' => '/srv/lemonfiber/data', 'point' => '/srv', 'free' => 40_000_000_000, 'limit' => 4_000_000_000_000, 'committed' => 60_000_000_000, 'projected' => 0, 'level' => 'warning', 'reading' => ['as' => 'live']],
                ['role' => 'services', 'at' => '/srv/lemonfiber/config', 'point' => '', 'free' => null, 'limit' => null, 'committed' => 0, 'projected' => null, 'level' => 'unknown', 'reading' => ['as' => 'as_of', 'at' => 1_790_100_000]],
            ],
            'level' => $level,
            'halted' => false,
            'consumption' => [
                ['category' => ['of' => 'tree', 'name' => 'movies'], 'reclaim' => 'by_losing_content', 'tally' => ['files' => 900, 'logical' => 2_000_000_000_000, 'physical' => 1_500_000_000_000, 'shared' => 300]],
                ['category' => ['of' => 'orphaned'], 'reclaim' => 'the_easy_win', 'tally' => ['files' => 12, 'logical' => 30_000_000_000, 'physical' => 30_000_000_000, 'shared' => 0]],
            ],
            'reclaimable' => [],
            'candidates' => [
                ['name' => 'Some.Show.S01E01', 'bytes' => 2_000_000_000, 'standing' => ['standing' => 'never_imported'], 'consequence' => null],
                ['name' => 'Some.Film.2024', 'bytes' => 8_000_000_000, 'standing' => ['standing' => 'seeding', 'ratio' => 125], 'consequence' => 'Your ratio on that tracker stops growing'],
                ['name' => 'Old.Film.1999', 'bytes' => 4_000_000_000, 'standing' => ['standing' => 'seeding', 'ratio' => 4_294_967_295], 'consequence' => 'Your ratio on that tracker stops growing'],
                ['name' => 'Kept.Show.S02', 'bytes' => 1_000_000_000, 'standing' => ['standing' => 'left_alone']],
            ],
            'outsized' => [],
            'interrupted' => [],
            'agreement' => 'space-3f9a',
            'reclaimed' => null,
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Measuring>
 */
function everyWayOfAskingHowFull(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Measuring => $why instanceof Obstacle
            ? AStackThatMeasuresItsRoom::met($why)
            : AStackThatMeasuresItsRoom::with(theSameRoom()),
        'the adapter' => static function () use ($answered): Measuring {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Surveyors(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheRoomTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** A figure a reading may not have, as text. */
function roomAmount(AnAmountOfRoom $amount): string
{
    return $amount->either(
        known: static fn(int $bytes): WhatTheRoomTurnedOutToSay => new WhatTheRoomTurnedOutToSay((string) $bytes),
        unread: static fn(): WhatTheRoomTurnedOutToSay => new WhatTheRoomTurnedOutToSay('unread'),
    )->said;
}

/** Everything the reading says, folded to lines, so two answers can be compared. */
function everythingTheRoomSays(Measuring $measuring): string
{
    return $measuring->measuredOn(aStackThatFills(), Session::of('a-session-not-a-secret'))->either(
        measured: static function (WhereTheRoomWent $room): WhatTheRoomTurnedOutToSay {
            $said = [sprintf('%s halted:%s', $room->stands()->value, $room->isHalted() ? 'yes' : 'no')];

            foreach ($room->volumes() as $volume) {
                $said[] = sprintf(
                    'volume %s at %s %s free %s of %s, %d coming, %s after; %s',
                    $volume->holds()->value,
                    $volume->point(),
                    $volume->stands()->value,
                    roomAmount($volume->free()),
                    roomAmount($volume->limit()),
                    $volume->committed(),
                    roomAmount($volume->projected()),
                    $volume->reading()->either(
                        live: static fn(): WhatTheRoomTurnedOutToSay => new WhatTheRoomTurnedOutToSay('live'),
                        asOf: static fn(Instant $at): WhatTheRoomTurnedOutToSay => new WhatTheRoomTurnedOutToSay(sprintf('as of %d', $at->epochSeconds())),
                    )->said,
                );
            }

            foreach ($room->account() as $line) {
                $said[] = sprintf('line %s %s %d/%d %s', $line->about()->value, $line->tree(), $line->occupies()->physical(), $line->occupies()->logical(), $line->costs()->value);
            }

            foreach ($room->downloads() as $download) {
                $said[] = sprintf(
                    'download %s %d %s %s %s',
                    $download->name(),
                    $download->bytes(),
                    $download->stands()->value,
                    $download->ratio(
                        seeding: static fn(ARatio $ratio): WhatTheRoomTurnedOutToSay => $ratio->either(
                            read: static fn(string $read): WhatTheRoomTurnedOutToSay => new WhatTheRoomTurnedOutToSay($read),
                            none: static fn(): WhatTheRoomTurnedOutToSay => new WhatTheRoomTurnedOutToSay('no ratio'),
                        ),
                        notSeeding: static fn(): WhatTheRoomTurnedOutToSay => new WhatTheRoomTurnedOutToSay('-'),
                    )->said,
                    $download->consequence(),
                );
            }

            return new WhatTheRoomTurnedOutToSay(implode(' | ', $said));
        },
        met: static fn(Obstacle $why): WhatTheRoomTurnedOutToSay => new WhatTheRoomTurnedOutToSay($why->value),
    )->said;
}

it('N12-R1, N12-R2, N12-R3, N12-R6, N12-R10 — comes away with the volumes, the account and each download', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfItsRoom()));

    foreach (everyWayOfAskingHowFull($answered) as $which => $make) {
        expect(everythingTheRoomSays($make()))->toBe(
            'warning halted:no'
            . ' | volume data at /srv warning free 40000000000 of 4000000000000, 60000000000 coming, 0 after; live'
            . ' | volume services at  unknown free unread of unread, 0 coming, unread after; as of 1790100000'
            . ' | line tree movies 1500000000000/2000000000000 by_losing_content'
            . ' | line orphaned  30000000000/30000000000 the_easy_win'
            . ' | download Some.Show.S01E01 2000000000 never_imported - '
            . ' | download Some.Film.2024 8000000000 seeding 1.25 Your ratio on that tracker stops growing'
            . ' | download Old.Film.1999 4000000000 seeding no ratio Your ratio on that tracker stops growing'
            . ' | download Kept.Show.S02 1000000000 left_alone - ',
            $which,
        );
    }
});

it('N1-R44 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingHowFull($answered, $why) as $which => $make) {
            expect(everythingTheRoomSays($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('N12-R10 — a level this app cannot read is an obstacle, never a comfortable disk', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsRoom('roomy')))]);

    expect(everythingTheRoomSays(new Surveyors(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('SpaceEnvelope', whatAStackSaysOfItsRoom()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
