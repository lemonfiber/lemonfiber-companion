<?php

declare(strict_types=1);

use Modules\Kernel\Api\ADownloadItWouldInterrupt;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\HowADownloadComes;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ProposedChange;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Kernel\Api\WhatItHoldsNow;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Kernel\Api\WhetherToWait;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\Screens\WhatThisStackIsSetTo;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatIsSet;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\AStackThatSaysWhatIsComingDown;
use Tests\Support\Fakes\AStackThatSaysWhatSetupSettled;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\AStackToldToChangeSomething;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\TheSettingsOfTheLoft;
use Tests\Support\TheThingScreenOfTheShed;
use Tests\Support\TheUpkeepScreenOfTheLoft;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatAMovedOfferSays;
use Tests\Support\WhatTheDeviceWouldDraw;

/**
 * The lines naming a film over Usenet at forty and a show by torrent at ninety-four.
 *
 * @return list<string>
 */
function theTwoDownloadsAsDrawn(): array
{
    return [
        Catalogue::words()->for('health.coming.heading'),
        Catalogue::words()->for('health.coming.line', ['name' => 'A film', 'progress' => '40', 'by' => Catalogue::words()->for('health.coming.by.usenet')]),
        Catalogue::words()->for('health.coming.line', ['name' => 'A show', 'progress' => '94', 'by' => Catalogue::words()->for('health.coming.by.torrent')]),
        Catalogue::words()->for('health.coming.interrupts'),
    ];
}

/**
 * The names of the forms or services a stop reaches.
 *
 * @param  iterable<Form|ServiceId> $reached
 * @return list<string>
 */
function namesIn(iterable $reached): array
{
    $names = [];

    foreach ($reached as $one) {
        $names[] = $one->named();
    }

    return $names;
}

/** The screen about one thing on the shed, read through its forms, asked about a stop of it. */
function aStopAskedAbout(AStackThatSaysWhatIsComingDown $comingDown, string $named = 'sonarr', ?AStackThatSupervises $supervising = null): WhatToDoWithThis
{
    $screen = TheThingScreenOfTheShed::theScreen($supervising ?? AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), $named, comingDown: $comingDown);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->render();
    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);

    return $screen;
}

/** A change of the library path the stack has staged, which interrupts a film coming down by torrent. */
function aChangeThatInterruptsAFilm(): WhereTheChangeStands
{
    return WhereTheChangeStands::at(
        ProposedChange::of('LIBRARY_PATH', '/data/films', WhatItHoldsNow::shown('/data/media'), Cost::Consequential),
        Stance::Pending,
        WhatAChangeComesTo::nothing()->interrupting(ADownloadItWouldInterrupt::of('A film', HowADownloadComes::Torrent, 40)),
    );
}

/** The settings screen with the library path open and a new one typed, asked what the change would come to. */
function aChangeAskedAbout(AStackToldToChangeSomething $adjusting, ?AKeychainInMemory $keychain = null, ?AStackThatSaysWhatSetupSettled $settled = null): WhatThisStackIsSetTo
{
    $screen = TheSettingsOfTheLoft::screen(AStackThatIsSet::to(Settings::of(Setting::called('LIBRARY_PATH', WhatASettingHolds::shown('/data/media'), WhoPutItThere::bundled()))), $adjusting, $keychain, settled: $settled);
    $screen->change('LIBRARY_PATH');
    $screen->__syncProperty('typed', '/data/films');
    $screen->wouldBe('LIBRARY_PATH');

    return $screen;
}

it('names each download a stop would interrupt, with how far along it is and its client, and offers letting them finish beside going ahead now', function (): void {
    $comingDown = AStackThatSaysWhatIsComingDown::twoDownloads();
    $drawn = WhatTheDeviceWouldDraw::by(aStopAskedAbout($comingDown));

    expect($drawn->said())->toContain(...theTwoDownloadsAsDrawn())
        ->and($drawn->offers())->toContain(__('health.coming.let_them_finish'), __('health.coming.go_ahead_now'))
        ->and($drawn->offers())->not->toContain(__('health.go_ahead'))
        ->and($comingDown->asked())->toHaveCount(1)
        ->and(namesIn($comingDown->asked()[0]->services()))->toBe(['sonarr'])
        ->and(namesIn($comingDown->asked()[0]->forms()))->toBe([]);
});

it('sends the stop with wait where they are let finish, and without where it goes ahead now, and follows it', function (string $letting, WhetherToWait $sent): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = aStopAskedAbout(AStackThatSaysWhatIsComingDown::twoDownloads(), supervising: $supervising);

    $screen->agree($letting);

    expect($supervising->whatItWasToldToDo())->toHaveCount(1)
        ->and($supervising->whatItWasToldToDo()[0]->waiting())->toBe($sent)
        ->and($screen->awaitsAnOutcome())->toBeTrue()
        ->and($screen->comingDown)->toBeNull();
})->with([
    'let them finish' => ['for_the_downloads', WhetherToWait::ForTheDownloads],
    'go ahead now' => ['go_ahead_now', WhetherToWait::GoAheadNow],
    'a word it does not know' => ['whenever', WhetherToWait::GoAheadNow],
]);

it('asks about the whole form where the stop is of a form', function (): void {
    $comingDown = AStackThatSaysWhatIsComingDown::nothing();
    aStopAskedAbout($comingDown, 'library');

    expect(namesIn($comingDown->asked()[0]->forms()))->toBe(['library'])
        ->and(namesIn($comingDown->asked()[0]->services()))->toBe([]);
});

it('adds nothing where nothing is coming down, and asks nothing before a restart', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(aStopAskedAbout(AStackThatSaysWhatIsComingDown::nothing()));
    $restarting = AStackThatSaysWhatIsComingDown::twoDownloads();
    $screen = TheThingScreenOfTheShed::theScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()), comingDown: $restarting);
    $screen->wouldYouLike(WhatToDoWithIt::Restart->value);

    expect($drawn->said())->not->toContain(__('health.coming.heading'), __('health.coming.unknown'))
        ->and($drawn->offers())->toContain(__('health.go_ahead'))
        ->and($restarting->asked())->toBe([])
        ->and($screen->comingDown)->toBeNull();
});

it('says it could not find out what is coming down, and still offers the stop', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = aStopAskedAbout(AStackThatSaysWhatIsComingDown::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), supervising: $supervising);
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->agree();

    expect($drawn->said())->toContain(__('health.coming.unknown'))
        ->and($drawn->offers())->toContain(__('health.go_ahead'))
        ->and($supervising->whatItWasToldToDo()[0]->waiting())->toBe(WhetherToWait::GoAheadNow);
});

it('asks again what a stop would interrupt where the stop agreed to was refused because what it would stop moved', function (): void {
    $comingDown = AStackThatSaysWhatIsComingDown::twoDownloads();
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())
        ->declaring(WhatAMachineRuns::libraryAndFull())
        ->whoseYesMoved(ARefusalInItsWords::said(WhatAMovedOfferSays::SUMMARY, WhatAMovedOfferSays::MEANING, WhatTheRefusalNamed::nothing()));
    $screen = aStopAskedAbout($comingDown, supervising: $supervising);

    $screen->agree('go_ahead_now');
    $screen->whileItSettles();
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->whileItSettles();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(WhatAMovedOfferSays::SUMMARY)
        ->and($supervising->whatItWasToldToDo())->toHaveCount(1)
        ->and($comingDown->asked())->toHaveCount(2)
        ->and(namesIn($comingDown->asked()[1]->services()))->toBe(['sonarr'])
        ->and($screen->comingDown)->not->toBeNull();
});

it('forgets what was coming down when the stop is put away', function (): void {
    $screen = aStopAskedAbout(AStackThatSaysWhatIsComingDown::twoDownloads());
    $screen->neverMind();

    expect($screen->comingDown)->toBeNull();
});

it('names what an update would interrupt inside the services it changes, and takes it with wait where they are let finish', function (): void {
    $keeping = AStackThatKeepsCurrent::with(TheUpkeepScreenOfTheLoft::anEveningWorthSpending());
    $comingDown = AStackThatSaysWhatIsComingDown::these(ADownloadItWouldInterrupt::inFlight('A film', HowADownloadComes::Usenet, 40));
    $screen = TheUpkeepScreenOfTheLoft::theUpkeepScreen($keeping, comingDown: $comingDown);
    $screen->answer();
    $screen->wouldYouLike();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    $screen->agree('for_the_downloads');

    expect(namesIn($comingDown->asked()[0]->services()))->toBe(['jellyfin', 'sonarr'])
        ->and($drawn->said())->toContain(__('health.coming.line', ['name' => 'A film', 'progress' => 40, 'by' => Catalogue::words()->for('health.coming.by.usenet')]))
        ->and($drawn->offers())->toContain(__('health.coming.let_them_finish'), __('health.coming.go_ahead_now'))
        ->and($keeping->taken())->toHaveCount(1)
        ->and($keeping->taken()[0]->waiting())->toBe(WhetherToWait::ForTheDownloads)
        ->and($screen->comingDown)->toBeNull();
});

it('takes an update at once where it goes ahead now, and forgets what was coming down when it is put away', function (): void {
    $keeping = AStackThatKeepsCurrent::with(TheUpkeepScreenOfTheLoft::anEveningWorthSpending());
    $screen = TheUpkeepScreenOfTheLoft::theUpkeepScreen($keeping, comingDown: AStackThatSaysWhatIsComingDown::twoDownloads());
    $screen->answer();
    $screen->wouldYouLike();
    $screen->neverMind();
    $forgotten = $screen->comingDown;
    $screen->wouldYouLike();
    $screen->agree('go_ahead_now');

    expect($forgotten)->toBeNull()
        ->and($keeping->taken()[0]->waiting())->toBe(WhetherToWait::GoAheadNow);
});

it('offers letting the downloads a change interrupts finish, and writes at once where it goes ahead now', function (): void {
    $adjusting = AStackToldToChangeSomething::saying(aChangeThatInterruptsAFilm());
    $screen = aChangeAskedAbout($adjusting);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    $screen->agree('LIBRARY_PATH', 'go_ahead_now');

    expect($drawn->said())->toContain(__('config.findings.interrupts.torrent', ['name' => 'A film', 'progress' => 40]), __('health.coming.interrupts'))
        ->and($drawn->offers())->toContain(__('health.coming.let_them_finish'), __('health.coming.go_ahead_now'))
        ->and($drawn->offers())->not->toContain(__('config.agree'))
        ->and($adjusting->writes())->toBe(1)
        ->and($adjusting->landings())->toBe(0);
});

it('lets them land, says it is waiting, and draws the change the stack made once they have', function (): void {
    $adjusting = AStackToldToChangeSomething::saying(aChangeThatInterruptsAFilm());
    $screen = aChangeAskedAbout($adjusting);

    $screen->agree('LIBRARY_PATH', 'for_the_downloads');
    $waiting = WhatTheDeviceWouldDraw::by($screen);
    $screen->whileTheyLand();
    $stillWaiting = $screen->awaitsAnOutcome();

    $adjusting->thenBecomes(HowAgreedWorkIsGoing::done(WhereTheChangeStands::at(
        ProposedChange::of('LIBRARY_PATH', '/data/films', WhatItHoldsNow::shown('/data/media'), Cost::Consequential),
        Stance::Applied,
        WhatAChangeComesTo::nothing(),
    )));
    $screen->whileTheyLand();

    expect($adjusting->landings())->toBe(1)
        ->and($adjusting->writes())->toBe(0)
        ->and($waiting->said())->toContain(__('config.landing.waiting'))
        ->and($waiting->offers())->not->toContain(__('health.coming.let_them_finish'))
        ->and($stillWaiting)->toBeTrue()
        ->and($screen->awaitsAnOutcome())->toBeFalse()
        ->and($screen->proposal()?->wroteSomething)->toBeTrue();
});

it('reads what setup settled again once a change that let the downloads finish has landed', function (): void {
    $settled = AStackThatSaysWhatSetupSettled::setUp();
    $adjusting = AStackToldToChangeSomething::saying(aChangeThatInterruptsAFilm());
    $screen = aChangeAskedAbout($adjusting, settled: $settled);
    $screen->agree('LIBRARY_PATH', 'for_the_downloads');
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $whileWaiting = $settled->askings();

    $adjusting->thenBecomes(HowAgreedWorkIsGoing::done(WhereTheChangeStands::at(
        ProposedChange::of('LIBRARY_PATH', '/data/films', WhatItHoldsNow::shown('/data/media'), Cost::Consequential),
        Stance::Applied,
        WhatAChangeComesTo::nothing(),
    )));
    $screen->whileTheyLand();
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect($settled->askings())->toBe($whileWaiting + 1);
});

it('says the change was not made where the stack stopped waiting or what it was agreed against moved', function (HowAgreedWorkIsGoing $became, string $said): void {
    $adjusting = AStackToldToChangeSomething::saying(aChangeThatInterruptsAFilm());
    $screen = aChangeAskedAbout($adjusting);
    $screen->agree('LIBRARY_PATH', 'for_the_downloads');
    $adjusting->thenBecomes($became);
    $screen->whileTheyLand();

    expect($screen->awaitsAnOutcome())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__($said));
})->with([
    'ended' => [HowAgreedWorkIsGoing::ended(WhyTheWorkEnded::LetGo), 'config.landing.ended'],
    'no outcome' => [HowAgreedWorkIsGoing::noOutcome(), 'config.landing.ended'],
    'moved' => [HowAgreedWorkIsGoing::moved(ARefusalInItsWords::said('The setting changed since it was read', '', WhatTheRefusalNamed::nothing())), 'config.landing.moved'],
]);

it('says what stood in the way where the waiting could not be started or followed', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $refused = aChangeAskedAbout(AStackToldToChangeSomething::met($why));
    $refused->__syncProperty('typed', '/data/films');
    $refused->agree('LIBRARY_PATH', 'for_the_downloads');

    $adjusting = AStackToldToChangeSomething::saying(aChangeThatInterruptsAFilm());
    $followed = aChangeAskedAbout($adjusting);
    $followed->agree('LIBRARY_PATH', 'for_the_downloads');
    $adjusting->thenBecomes(HowAgreedWorkIsGoing::met($why));
    $followed->whileTheyLand();

    expect($refused->awaitsAnOutcome())->toBeFalse()
        ->and($refused->proposal()?->went->cameBack())->toBeFalse()
        ->and($followed->awaitsAnOutcome())->toBeFalse()
        ->and($followed->proposal()?->went->cameBack())->toBeFalse();
});

it('waits on nothing where what is let finish is not the setting open or nothing was typed into it', function (string $key, ?string $typed): void {
    $adjusting = AStackToldToChangeSomething::saying(aChangeThatInterruptsAFilm());
    $screen = aChangeAskedAbout($adjusting);
    $screen->__syncProperty('typed', $typed);

    $screen->agree($key, 'for_the_downloads');

    expect($adjusting->landings())->toBe(0)
        ->and($adjusting->writes())->toBe(0)
        ->and($screen->awaitsAnOutcome())->toBeFalse()
        ->and($screen->proposal()?->key)->toBe('')
        ->and($screen->proposal()?->went->cameBack())->toBeTrue();
})->with([
    'another setting' => ['BIND', '/data/films'],
    'nothing typed' => ['LIBRARY_PATH', null],
]);

it('stops waiting and says the session ended where it went while the downloads land, and asks after nothing once nothing waits', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = aChangeAskedAbout(AStackToldToChangeSomething::saying(aChangeThatInterruptsAFilm()), $keychain);
    $screen->agree('LIBRARY_PATH', 'for_the_downloads');
    $keychain->forget(TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id());

    $screen->whileTheyLand();
    $ended = $screen->proposal();
    $screen->whileTheyLand();

    expect($ended?->went->isSignedIn)->toBeFalse()
        ->and($screen->awaitsAnOutcome())->toBeFalse()
        ->and($screen->proposal())->toBe($ended);
});
