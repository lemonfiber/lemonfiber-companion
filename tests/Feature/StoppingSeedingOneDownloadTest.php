<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\ADownloadLetGo;
use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\HowLettingItGoIsGoing;
use Modules\Kernel\Api\HowTheOfferToLetGoIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatLettingItGoCosts;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\LettingADownloadGo;
use Modules\Operator\Internal\ViewModels\ADownloadAsShown;
use Modules\Operator\Internal\ViewModels\ASizeAsShown;
use Modules\Operator\Internal\ViewModels\HowLettingItGoWent;
use Modules\Operator\Internal\ViewModels\WhatLettingItGoWouldShow;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatStopsSeeding;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Stopping seeding one download: what it costs first, as its own act; the
// yes, sent against that offer and nothing else; and the stack's report of
// it, a rehearsal labelled as one and never as room freed.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.

/**
 * One line of the catalogue, as text, for filling another.
 *
 * @param array<string, int|string> $with
 */
function aLineOfLettingGo(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

/** A size as the glass says it, for a sentence that takes one. */
function aSizeLetGo(string $key, int $bytes): string
{
    $big = HowBig::of($bytes);

    return aLineOfLettingGo($key, ['figure' => $big->figure, 'unit' => aLineOfLettingGo($big->said)]);
}

/** The machine a download is let go on. */
function theStackADownloadIsLetGoOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The screen, opened on a download, with a keychain holding whatever a case says. */
function theLettingGoScreen(
    AStackThatStopsSeeding $stopping,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    string $download = 'Some.Film.2024',
): LettingADownloadGo {
    $stack = theStackADownloadIsLetGoOn();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new LettingADownloadGo($stopping, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)));
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $download]);

    return $screen;
}

/** The stack's offer about one seeding download. */
function anOfferToLetTheFilmGo(?ADownloadOnDisk $download = null): WhatLettingItGoCosts
{
    return WhatLettingItGoCosts::offered(
        $download ?? ADownloadOnDisk::seeding('Some.Film.2024', 8_000_000_000, ARatio::inHundredths(125), 'Your ratio on that tracker stops growing'),
        'The copy in the downloads tree goes with it',
        'stop-seeding-some-film-2024',
    );
}

/** A stack that offers that, and says `$became` of the yes. */
function aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing $became, ?WhatLettingItGoCosts $offer = null): AStackThatStopsSeeding
{
    return AStackThatStopsSeeding::offering(HowTheOfferToLetGoIsGoing::offering($offer ?? anOfferToLetTheFilmGo()), $became);
}

/**
 * Every field of the offer as drawn, in one comparable row.
 *
 * @return array<string, mixed>
 */
function everythingTheOfferToLetGoShows(WhatLettingItGoWouldShow $shown): array
{
    return [
        'cameBack' => $shown->went->cameBack(),
        'isSignedIn' => $shown->went->isSignedIn,
        'met' => $shown->went->met,
        'namesADownload' => $shown->namesADownload,
        'isWorking' => $shown->isWorking,
        'hasEnded' => $shown->hasEnded,
        'download' => $shown->download instanceof ADownloadAsShown
            ? [$shown->download->name, $shown->download->standingSaid, $shown->download->ratioSaid, $shown->download->ratio, $shown->download->consequence]
            : null,
        'goes' => $shown->goes,
    ];
}

/**
 * What an offer with nothing in it says of every field, changed where a case says.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function nothingOfferedToLetGo(array $changed): array
{
    return [
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'namesADownload' => true,
        'isWorking' => false,
        'hasEnded' => false,
        'download' => null,
        'goes' => '',
        ...$changed,
    ];
}

/**
 * Every field of what became of the yes, in one comparable row.
 *
 * @return array<string, mixed>
 */
function everythingLettingGoShows(HowLettingItGoWent $went): array
{
    return [
        'cameBack' => $went->went->cameBack(),
        'isSignedIn' => $went->went->isSignedIn,
        'met' => $went->went->met,
        'isWorking' => $went->isWorking,
        'hasEnded' => $went->hasEnded,
        'wasRehearsed' => $went->wasRehearsed,
        'name' => $went->name,
        'size' => $went->size instanceof ASizeAsShown ? [$went->size->figure, $went->size->unit] : null,
    ];
}

/**
 * What a yes with no report says of every field, changed where a case says.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function nothingReportedOfLettingGo(array $changed): array
{
    return [
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'isWorking' => false,
        'hasEnded' => false,
        'wasRehearsed' => false,
        'name' => '',
        'size' => null,
        ...$changed,
    ];
}

it('asks what stopping seeding would cost first, and draws all of it as its own act before the yes', function (): void {
    $stopping = aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning());
    $screen = theLettingGoScreen($stopping);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheOfferToLetGoShows($screen->answer()))->toBe([
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'namesADownload' => true,
        'isWorking' => false,
        'hasEnded' => false,
        'download' => ['Some.Film.2024', 'stacks.room.standing.seeding', 'stacks.room.ratio', '1.25', 'Your ratio on that tracker stops growing'],
        'goes' => 'The copy in the downloads tree goes with it',
    ])
        ->and(array_map(static fn(ADownloadHeld $download): string => $download->name(), $stopping->asked()))->toBe(['Some.Film.2024'])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $stopping->read()))->toBe([AStackThatStopsSeeding::THE_OFFER])
        ->and($stopping->agreed())->toBe([])
        ->and($drawn->said())->toContain(__('stacks.let_go.what_it_costs'))
        ->and($drawn->said())->toContain(__('stacks.let_go.its_own_act'))
        ->and($drawn->said())->toContain('Some.Film.2024')
        ->and($drawn->said())->toContain(aSizeLetGo('stacks.room.takes', 8_000_000_000))
        ->and($drawn->said())->toContain(__('stacks.room.standing.seeding'))
        ->and($drawn->said())->toContain(__('stacks.room.ratio', ['ratio' => '1.25']))
        ->and($drawn->said())->toContain('Your ratio on that tracker stops growing')
        ->and($drawn->said())->toContain('The copy in the downloads tree goes with it')
        ->and($drawn->said())->not->toContain(__('stacks.let_go.a_rehearsal'))
        ->and($drawn->offers())->toBe([__('stacks.let_go.stop_it'), __('health.ask_again')]);
});

it('draws a download never imported with its standing and no ratio, and a seeding one with nothing to divide by in words', function (ADownloadOnDisk $download, array $shown, string $said): void {
    $screen = theLettingGoScreen(aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning(), anOfferToLetTheFilmGo($download)));

    expect(everythingTheOfferToLetGoShows($screen->answer())['download'])->toBe($shown)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__($said));
})->with([
    'never imported' => [ADownloadOnDisk::neverImported('Some.Film.2024', 1), ['Some.Film.2024', 'stacks.room.standing.never_imported', '', '', ''], 'stacks.room.standing.never_imported'],
    'no ratio' => [ADownloadOnDisk::seeding('Some.Film.2024', 1, ARatio::none()), ['Some.Film.2024', 'stacks.room.standing.seeding', 'stacks.room.no_ratio', '', ''], 'stacks.room.no_ratio'],
]);

it('says the stack is still working out the cost, on the cadence it reads again at, and reads the same asking again', function (): void {
    $stopping = AStackThatStopsSeeding::offering(HowTheOfferToLetGoIsGoing::stillRunning(), HowLettingItGoIsGoing::stillRunning());
    $screen = theLettingGoScreen($stopping);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheOfferToLetGoShows($screen->answer()))->toBe(nothingOfferedToLetGo(['isWorking' => true]))
        ->and($screen->isWorking())->toBeTrue()
        ->and($drawn->said())->toContain(__('stacks.let_go.working_it_out', ['download' => 'Some.Film.2024']))
        ->and($drawn->offers())->toBe([__('health.ask_again')]);

    $screen->whileItRuns();
    $screen->answer();
    $screen->again();
    $screen->answer();
    $screen->agree();

    expect($stopping->asked())->toHaveCount(1)
        ->and($stopping->read())->toHaveCount(3)
        ->and($stopping->agreed())->toBe([]);
});

it('says an asking the stack has no outcome for is gone, and asks afresh when asked again', function (): void {
    $stopping = AStackThatStopsSeeding::offering(HowTheOfferToLetGoIsGoing::ended(), HowLettingItGoIsGoing::stillRunning());
    $screen = theLettingGoScreen($stopping);

    expect(everythingTheOfferToLetGoShows($screen->answer()))->toBe(nothingOfferedToLetGo(['hasEnded' => true]))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.let_go.offer_ended', ['download' => 'Some.Film.2024']));

    $screen->again();
    $screen->answer();

    expect($stopping->asked())->toHaveCount(2);
});

it('offers nothing to agree to where the stack would not say what it would cost', function (Obstacle $why, AStackThatStopsSeeding $stopping): void {
    $screen = theLettingGoScreen($stopping);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheOfferToLetGoShows($screen->answer()))->toBe(nothingOfferedToLetGo(['cameBack' => false, 'met' => $why->said(), 'isWorking' => false]))
        ->and($drawn->offers())->not->toContain(__('stacks.let_go.stop_it'))
        ->and($drawn->said())->not->toContain(__('stacks.let_go.what_it_costs'));

    $screen->agree();

    expect($stopping->agreed())->toBe([])
        ->and($screen->wasAgreedTo())->toBeFalse();
})->with([
    'refusing to ask' => [Obstacle::of(KindOfObstacle::StackDidNotAnswer), AStackThatStopsSeeding::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))],
    'refusing to answer' => [Obstacle::of(KindOfObstacle::StackDidNotAnswer), AStackThatStopsSeeding::offering(HowTheOfferToLetGoIsGoing::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), HowLettingItGoIsGoing::stillRunning())],
]);

it('names no download where it was opened on none, and asks the stack nothing', function (mixed $named): void {
    $stopping = aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning());
    $screen = theLettingGoScreen($stopping);
    $screen->setParams(['stack' => theStackADownloadIsLetGoOn()->id()->stored(), 'service' => $named]);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheOfferToLetGoShows($screen->answer()))->toBe(nothingOfferedToLetGo(['namesADownload' => false]))
        ->and($stopping->asked())->toBe([])
        ->and($drawn->said())->toContain(__('stacks.let_go.names_no_download'))
        ->and($drawn->offers())->toBe([__('stacks.let_go.see_the_room')]);
})->with(['blank' => ['  '], 'not text' => [42]]);

it('asks for a session rather than an offer where this device holds none', function (): void {
    $stopping = aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning());
    $screen = theLettingGoScreen($stopping, signedIn: false);

    expect(everythingTheOfferToLetGoShows($screen->answer()))->toBe(nothingOfferedToLetGo(['cameBack' => false, 'isSignedIn' => false]))
        ->and($stopping->asked())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('connection.sign_in'));
});

it('lets go of a session the stack refused while it was asked what stopping would cost', function (AStackThatStopsSeeding $stopping): void {
    $keychain = AKeychainInMemory::working();
    $screen = theLettingGoScreen($stopping, $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackADownloadIsLetGoOn()->id()))->toBeFalse();
})->with([
    'asking' => [AStackThatStopsSeeding::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))],
    'reading the offer' => [AStackThatStopsSeeding::offering(HowTheOfferToLetGoIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), HowLettingItGoIsGoing::stillRunning())],
]);

it('stops seeding exactly the offer it showed, and says it is doing so on its cadence', function (): void {
    $stopping = aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning());
    $screen = theLettingGoScreen($stopping);
    $screen->answer();
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(array_map(static fn(WhatLettingItGoCosts $offer): string => $offer->agreement(), $stopping->agreed()))->toBe(['stop-seeding-some-film-2024'])
        ->and($screen->wasAgreedTo())->toBeTrue()
        ->and($screen->isWorking())->toBeTrue()
        ->and(everythingLettingGoShows($screen->done()))->toBe(nothingReportedOfLettingGo(['isWorking' => true]))
        ->and($drawn)->toContain(__('stacks.let_go.letting_go', ['download' => 'Some.Film.2024']))
        ->and($drawn)->not->toContain(__('stacks.let_go.what_it_costs'))
        ->and($stopping->followed())->toBe([]);

    $screen->whileItRuns();
    $screen->done();

    expect($stopping->followed())->toHaveCount(1)
        ->and($stopping->followed()[0]->shown())->toBe(AStackThatStopsSeeding::THE_JOB)
        ->and($stopping->agreed())->toHaveCount(1);
});

it('sends no yes before an offer has been read, and never on its cadence', function (): void {
    $stopping = aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning());
    $screen = theLettingGoScreen($stopping);
    $screen->agree();

    expect($stopping->agreed())->toBe([])
        ->and($screen->wasAgreedTo())->toBeFalse();

    $screen->answer();

    $screen->whileItRuns();
    $screen->answer();
    $screen->whileItRuns();
    $screen->answer();

    expect($stopping->agreed())->toBe([])
        ->and($stopping->read())->toHaveCount(1)
        ->and($stopping->followed())->toBe([]);
});

it('reports the download let go and the room it took', function (): void {
    $report = ADownloadLetGo::reported('Some.Film.2024', 8_000_000_000, WhetherItWasRehearsed::CarriedOut);
    $screen = theLettingGoScreen(aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::done($report)));
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $big = HowBig::of(8_000_000_000);

    expect(everythingLettingGoShows($screen->done()))->toBe(nothingReportedOfLettingGo(['name' => 'Some.Film.2024', 'size' => [$big->figure, $big->said]]))
        ->and($drawn->said())->toContain(__('stacks.let_go.let_go', ['download' => 'Some.Film.2024']))
        ->and($drawn->said())->toContain(aSizeLetGo('stacks.let_go.occupied', 8_000_000_000))
        ->and($drawn->said())->not->toContain(__('stacks.let_go.a_rehearsal'))
        ->and($drawn->offers())->toContain(__('stacks.let_go.see_the_room'));
});

it('labels a rehearsal as one and never reports it as room freed', function (): void {
    $report = ADownloadLetGo::reported('Some.Film.2024', 8_000_000_000, WhetherItWasRehearsed::Rehearsed);
    $screen = theLettingGoScreen(aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::done($report)));
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $big = HowBig::of(8_000_000_000);
    $labelled = array_search(__('stacks.let_go.a_rehearsal'), $drawn, strict: true);
    $said = array_search(__('stacks.let_go.rehearsed', ['download' => 'Some.Film.2024']), $drawn, strict: true);

    // Labelled before anything else about it is said.
    expect(everythingLettingGoShows($screen->done()))->toBe(nothingReportedOfLettingGo(['wasRehearsed' => true, 'name' => 'Some.Film.2024', 'size' => [$big->figure, $big->said]]))
        ->and($labelled)->toBeInt()
        ->and($said)->toBeInt()
        ->and(is_int($labelled) && is_int($said) && $labelled < $said)->toBeTrue()
        ->and($drawn)->toContain(aSizeLetGo('stacks.let_go.nothing_freed', 8_000_000_000))
        ->and($drawn)->not->toContain(__('stacks.let_go.let_go', ['download' => 'Some.Film.2024']))
        ->and($drawn)->not->toContain(aSizeLetGo('stacks.let_go.occupied', 8_000_000_000));
});

it('says stopping the stack has no outcome for is not known, rather than failed', function (): void {
    $screen = theLettingGoScreen(aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::ended()));
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingLettingGoShows($screen->done()))->toBe(nothingReportedOfLettingGo(['hasEnded' => true]))
        ->and($drawn->said())->toContain(__('stacks.let_go.no_outcome', ['download' => 'Some.Film.2024']))
        ->and($drawn->said())->toContain(__('stacks.let_go.no_outcome_action'))
        ->and($drawn->offers())->toContain(__('stacks.let_go.see_the_room'));
});

it('says what stood in the way of a yes the stack refused, and asks for the offer afresh', function (): void {
    $stopping = AStackThatStopsSeeding::offeringButRefusing(anOfferToLetTheFilmGo(), Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = theLettingGoScreen($stopping);
    $screen->answer();
    $screen->agree();

    expect(everythingLettingGoShows($screen->done()))->toBe(nothingReportedOfLettingGo(['cameBack' => false, 'met' => KindOfObstacle::StackDidNotAnswer->said()]))
        ->and($screen->isWorking())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));

    $screen->again();

    expect($screen->wasAgreedTo())->toBeFalse()
        ->and($screen->answer()->went->cameBack())->toBeTrue()
        ->and($stopping->asked())->toHaveCount(2)
        ->and($stopping->followed())->toBe([]);
});

it('asks after the same handle when asked again after a yes the stack took on', function (): void {
    $stopping = aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning());
    $screen = theLettingGoScreen($stopping);
    $screen->answer();
    $screen->agree();
    $screen->again();
    $screen->done();

    expect($screen->wasAgreedTo())->toBeTrue()
        ->and($stopping->followed())->toHaveCount(1)
        ->and($stopping->asked())->toHaveCount(1);
});

it('asks for the offer afresh when asked again before a yes', function (): void {
    $stopping = aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning());
    $screen = theLettingGoScreen($stopping);
    $screen->answer();
    $screen->again();
    $screen->agree();

    expect($stopping->agreed())->toBe([])
        ->and($screen->answer()->went->cameBack())->toBeTrue()
        ->and($stopping->asked())->toHaveCount(2);
});

it('lets go of a session refused while stopping or asking after it', function (bool $whileAsking): void {
    $keychain = AKeychainInMemory::working();
    $stopping = $whileAsking
        ? aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)))
        : AStackThatStopsSeeding::offeringButRefusing(anOfferToLetTheFilmGo(), Obstacle::of(KindOfObstacle::CredentialWasRefused));
    $screen = theLettingGoScreen($stopping, $keychain);
    $screen->answer();
    $screen->agree();

    if ($whileAsking) {
        $screen->whileItRuns();
    }

    expect($screen->done()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackADownloadIsLetGoOn()->id()))->toBeFalse();
})->with(['stopping' => [false], 'asking after' => [true]]);

it('asks for a session where it is gone by the time the yes is sent, or asked after', function (bool $afterTheYes): void {
    $keychain = AKeychainInMemory::working();
    $screen = theLettingGoScreen(aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning()), $keychain);
    $screen->answer();

    if ($afterTheYes) {
        $screen->agree();
    }

    $keychain->forget(theStackADownloadIsLetGoOn()->id());

    if (! $afterTheYes) {
        $screen->agree();
    }

    $screen->whileItRuns();

    expect(everythingLettingGoShows($screen->done()))->toBe(nothingReportedOfLettingGo(['cameBack' => false, 'isSignedIn' => false]));
})->with(['before the yes' => [false], 'after it' => [true]]);

it('has nothing to report for a yes nobody gave', function (): void {
    $stopping = aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning());
    $screen = theLettingGoScreen($stopping);

    expect(everythingLettingGoShows($screen->done()))->toBe(nothingReportedOfLettingGo(['hasEnded' => true]))
        ->and($stopping->followed())->toBe([]);
});

it('refuses a route parameter that is not text as naming a stack', function (): void {
    $screen = theLettingGoScreen(aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning()));
    $screen->setParams(['stack' => 42, 'service' => 'Some.Film.2024']);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('renders its own view, and the way back is a route', function (): void {
    $screen = theLettingGoScreen(aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning()));

    expect($screen->render()->name())->toBe('operator::letting-a-download-go')
        ->and(NativeRouter::resolve($screen->goes()->ofItself()->room()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->ofItself()->changing()->lettingGo('Some Film/2024')))->not->toBeNull();
});

it('asked again before anything was read, asks for the offer when the frame reads it, and only then', function (): void {
    $stopping = aStackOfferingToLetTheFilmGo(HowLettingItGoIsGoing::stillRunning());
    $screen = theLettingGoScreen($stopping);

    $screen->again();

    expect($stopping->asked())->toBe([]);

    $screen->answer();

    expect($stopping->asked())->toHaveCount(1);
});
