<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\ALineOfTheAccount;
use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\AVolume;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\HowFreshAReadingIs;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\HowMuchRoomAVolumeHas;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheAccount;
use Modules\Kernel\Api\TheDownloadsOnDisk;
use Modules\Kernel\Api\TheVolumes;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatAVolumeHolds;
use Modules\Kernel\Api\WhatGettingItBackCosts;
use Modules\Kernel\Api\WhatItOccupies;
use Modules\Kernel\Api\WhereADownloadStands;
use Modules\Kernel\Api\WhereTheRoomStands;
use Modules\Kernel\Api\WhereTheRoomWent;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowFullThisMachineIs;
use Modules\Operator\Internal\ViewModels\ADownloadAsShown;
use Modules\Operator\Internal\ViewModels\ALineAsShown;
use Modules\Operator\Internal\ViewModels\ASizeAsShown;
use Modules\Operator\Internal\ViewModels\AVolumeAsShown;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatMeasuresItsRoom;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// How full this machine is, and where the room went.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The moment a network share's age is counted from. */
const THE_ROOM_IS_READ_AT = 1_790_150_000;

/** The machine this screen is about. */
function theStackWhoseRoomIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A data volume that will fill, a services volume nobody could read two hours ago, and one of each download. */
function aFillingLoft(bool $halted = false): WhereTheRoomWent
{
    return WhereTheRoomWent::measured(
        TheVolumes::of(
            AVolume::measured(WhatAVolumeHolds::Data, '/srv', HowMuchRoomAVolumeHas::counted(AnAmountOfRoom::of(40_000_000_000, 'free'), AnAmountOfRoom::of(4_000_000_000_000, 'limit'), 60_000_000_000, AnAmountOfRoom::of(0, 'projected')), WhereTheRoomStands::Warning, HowFreshAReadingIs::live()),
            AVolume::measured(WhatAVolumeHolds::Services, '', HowMuchRoomAVolumeHas::counted(AnAmountOfRoom::unread(), AnAmountOfRoom::unread(), 0, AnAmountOfRoom::unread()), WhereTheRoomStands::Unknown, HowFreshAReadingIs::asOf(Instant::atEpochSeconds(THE_ROOM_IS_READ_AT - 7_200))),
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
        halted: $halted,
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theRoomScreen(
    AStackThatMeasuresItsRoom $measuring,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): HowFullThisMachineIs {
    $stack = theStackWhoseRoomIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new HowFullThisMachineIs($measuring, $keychain, StacksInMemory::holding($stack), FrozenClock::at(Instant::atEpochSeconds(THE_ROOM_IS_READ_AT)));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A size as the glass says it, for a sentence that takes one. */
function roomSize(string $key, int $bytes): string
{
    $big = HowBig::of($bytes);
    $said = __($key, ['figure' => $big->figure, 'unit' => roomWords($big->said)]);

    return is_string($said) ? $said : throw new LogicException(sprintf('`%s` names a group of lines, not a sentence', $key));
}

/** One line of the catalogue, refused where the key names a group of them. */
function roomWords(string $key): string
{
    $said = __($key);

    return is_string($said) ? $said : throw new LogicException(sprintf('`%s` names a group of lines, not a unit', $key));
}

it('says where the machine stands, and where each volume does', function (): void {
    $answer = theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft()))->answer();

    expect($answer->went->cameBack())->toBeTrue()
        ->and($answer->standsSaid)->toBe(WhereTheRoomStands::Warning->saidOnTheScreen())
        ->and($answer->halted)->toBeFalse()
        ->and(array_map(static fn(AVolumeAsShown $v): array => [$v->holdsSaid, $v->point, $v->standsSaid], $answer->volumes))->toBe([
            [WhatAVolumeHolds::Data->saidOnTheScreen(), '/srv', WhereTheRoomStands::Warning->saidOnTheScreen()],
            [WhatAVolumeHolds::Services->saidOnTheScreen(), '', WhereTheRoomStands::Unknown->saidOnTheScreen()],
        ]);
});

it('N12-R10 — a volume that could not be read says so on the glass, and never reads as comfortable or full', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft())))->said();

    expect($drawn)->toContain(__(WhereTheRoomStands::Unknown->saidOnTheScreen()))
        ->and($drawn)->toContain(__('stacks.room.free_unread'))
        ->and($drawn)->not->toContain(__(WhereTheRoomStands::Ample->saidOnTheScreen()))
        ->and($drawn)->not->toContain(roomSize('stacks.room.free', 0));
});

it('says what is free, the limit, what is on its way and what will be left, and dates a share\'s figures', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft())))->said();

    expect($drawn)->toContain(roomSize('stacks.room.free', 40_000_000_000))
        ->and($drawn)->toContain(roomSize('stacks.room.limit', 4_000_000_000_000))
        ->and($drawn)->toContain(roomSize('stacks.room.committed', 60_000_000_000))
        ->and($drawn)->toContain(roomSize('stacks.room.projected', 0))
        ->and($drawn)->toContain(__('stacks.room.as_of', ['ago' => trans_choice(HowLongAgo::Hours->saidOnTheScreen(), 2)]));
});

it('says the stack has stopped new downloads where it has, and not otherwise', function (): void {
    $halted = WhatTheDeviceWouldDraw::by(theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft(halted: true))))->said();
    $running = WhatTheDeviceWouldDraw::by(theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft())))->said();

    expect($halted)->toContain(__('stacks.room.halted'))
        ->and($running)->not->toContain(__('stacks.room.halted'));
});

it('N12-R6 — shows the room by the stack\'s categories, a tree by its name, and both figures only where they differ', function (): void {
    $answer = theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft()))->answer();
    $drawn = WhatTheDeviceWouldDraw::by(theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft())))->said();

    expect(array_map(static fn(ALineAsShown $l): array => [$l->aboutSaid, $l->tree, !$l->unshared instanceof ASizeAsShown, $l->costsSaid], $answer->account))->toBe([
        [WhatALineIsAbout::Tree->saidOnTheScreen(), 'movies', false, WhatGettingItBackCosts::ByLosingContent->saidOnTheScreen()],
        [WhatALineIsAbout::Orphaned->saidOnTheScreen(), '', true, WhatGettingItBackCosts::TheEasyWin->saidOnTheScreen()],
    ])
        ->and($drawn)->toContain('movies')
        ->and($drawn)->toContain(__(WhatALineIsAbout::Orphaned->saidOnTheScreen()))
        ->and($drawn)->toContain(roomSize('stacks.room.unshared', 2_000_000_000_000))
        ->and($drawn)->not->toContain(roomSize('stacks.room.unshared', 30_000_000_000))
        ->and($drawn)->toContain(__(WhatGettingItBackCosts::TheEasyWin->saidOnTheScreen()));
});

it('N12-R1 — shows every download with where it stands, and never draws one standing as another', function (): void {
    $answer = theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft()))->answer();

    expect(array_map(static fn(ADownloadAsShown $d): array => [$d->name, $d->standingSaid], $answer->downloads))->toBe([
        ['Some.Show.S01E01', WhereADownloadStands::NeverImported->saidOnTheScreen()],
        ['Some.Film.2024', WhereADownloadStands::Seeding->saidOnTheScreen()],
        ['Old.Film.1999', WhereADownloadStands::Seeding->saidOnTheScreen()],
        ['Kept.Show.S02', WhereADownloadStands::LeftAlone->saidOnTheScreen()],
    ]);
});

it('N12-R2 — shows a seeding download\'s ratio, and says so in words where there is none', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft())))->said();

    expect($drawn)->toContain(__('stacks.room.ratio', ['ratio' => '1.25']))
        ->and($drawn)->toContain(__('stacks.room.no_ratio'))
        ->and(implode("\n", $drawn))->not->toContain('42949672');
});

it('N12-R3 — draws what removing a download costs inside that download\'s entry', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft())))->said();
    $film = array_search('Some.Film.2024', $drawn, strict: true);
    $next = array_search('Old.Film.1999', $drawn, strict: true);
    $cost = array_search('Your ratio on that tracker stops growing', $drawn, strict: true);

    expect($film)->toBeInt()
        ->and($next)->toBeInt()
        ->and($cost)->toBeInt()
        ->and(is_int($film) && is_int($next) && is_int($cost) && $film < $cost && $cost < $next)->toBeTrue();
});

it('N12-R4, N12-R9 — offers asking again and nothing else: nothing is selected or proposed', function (): void {
    $offers = WhatTheDeviceWouldDraw::by(theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft(halted: true))))->offers();

    expect($offers)->toBe([__('health.ask_again')]);
});

it('says so where no volume is watched, nothing takes room and no download is on the machine', function (): void {
    $empty = WhereTheRoomWent::measured(TheVolumes::of(), WhereTheRoomStands::Ample, TheAccount::of(), TheDownloadsOnDisk::of(), halted: false);
    $drawn = WhatTheDeviceWouldDraw::by(theRoomScreen(AStackThatMeasuresItsRoom::with($empty)))->said();

    expect($drawn)->toContain(__('stacks.room.no_volumes'))
        ->and($drawn)->toContain(__('stacks.room.nothing_accounted'))
        ->and($drawn)->toContain(__('stacks.room.no_downloads'));
});

it('N12-R10 — a stack that could not be asked is not a machine with room to spare', function (): void {
    $answer = theRoomScreen(AStackThatMeasuresItsRoom::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->went->isSignedIn)->toBeTrue()
        ->and($answer->standsSaid)->toBe('')
        ->and($answer->volumes)->toBe([])
        ->and($answer->account)->toBe([])
        ->and($answer->downloads)->toBe([]);
});

it('N1-R44 — a session that has ended is not a machine with room to spare', function (): void {
    $measuring = AStackThatMeasuresItsRoom::with(aFillingLoft());
    $answer = theRoomScreen($measuring, signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->volumes)->toBe([])
        ->and($measuring->askings())->toBe(0);
});

it('N3-R13 — a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theRoomScreen(AStackThatMeasuresItsRoom::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseRoomIsRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    $measuring = AStackThatMeasuresItsRoom::with(aFillingLoft());
    $screen = theRoomScreen($measuring);

    $screen->answer();
    $screen->answer();

    expect($measuring->askings())->toBe(1)
        ->and($measuring->wasGivenASession())->toBeTrue()
        ->and($measuring->askedAbout()?->id()->stored())->toBe(theStackWhoseRoomIsRead()->id()->stored());
});

it('N1-R3 — asking again asks the machine again', function (): void {
    $measuring = AStackThatMeasuresItsRoom::met(Obstacle::DeviceHasNoNetwork);
    $screen = theRoomScreen($measuring);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($measuring->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theRoomScreen(AStackThatMeasuresItsRoom::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way here and the way back are routes', function (): void {
    $screen = theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->ofItself()->room()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theRoomScreen(AStackThatMeasuresItsRoom::with(aFillingLoft()))->render()->name())->toBe('operator::how-full-this-machine-is');
});
