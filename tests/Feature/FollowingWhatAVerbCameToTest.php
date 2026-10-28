<?php

declare(strict_types=1);

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\APortHeld;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AServiceLeftOut;
use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatAStartWaitsOn;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhereAServiceEndedUp;
use Modules\Kernel\Api\WhereTheServicesEndedUp;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowAStackEditReads;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatSaysWhatItWaitsOn;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\TheThingScreenOfTheShed;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatAMovedOfferSays;
use Tests\Support\WhatANeedSays;
use Tests\Support\WhatTheDeviceWouldDraw;

// What a start, a stop or a restart came to, followed from the screen it was
// sent from.
//
// A verb answers a handle, and the stack's report of what it did arrives only
// through that handle. A listing read afterwards says where the services stand
// and cannot say that a start was declined, that it was a rehearsal, or which
// of the services a restart waited for never came back — so the screen follows
// the handle and draws the report.

/**
 * A line of the catalogue, with what fills it.
 *
 * @param array<string, string> $with
 */
function whatTheCatalogueSays(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

/**
 * The line a frame draws straight after another, or empty where it draws none.
 *
 * @param list<string> $drawn
 */
function whatIsDrawnAfter(string $line, array $drawn): string
{
    $seen = false;

    foreach ($drawn as $said) {
        if ($seen) {
            return $said;
        }

        $seen = $said === $line;
    }

    return '';
}

it('follows a verb it sent until the stack reports, on the cadence it declares', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);

    // Just sent, it is running, and nothing has been asked after yet.
    expect($screen->whatItCameTo()->stands->isWorking())->toBeTrue()
        ->and($supervising->followed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())
        ->toContain(__('health.came_to.running'));

    $screen->whileItSettles();
    $screen->render();
    $screen->whatItCameTo();
    $screen->whileItSettles();
    $screen->render();
    $screen->whatItCameTo();

    expect($supervising->followed())->toHaveCount(2)
        ->and($supervising->followed()[0]->shown())->toBe(AStackThatSupervises::THE_JOB);
});

it('asks after the verb once a frame, however often the template reads it', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->again();
    $screen->render();
    $screen->whatItCameTo();
    $screen->whatItCameTo();

    expect($supervising->followed())->toHaveCount(1);
});

it('stops asking once the stack has reported', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done(
        TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy))->amountingTo(HowTheStackIsRunning::Active),
    ));
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    TheThingScreenOfTheShed::whatStartingSonarrDrew($screen);
    $screen->whileItSettles();
    $screen->whileItSettles();
    $screen->whatItCameTo();

    expect($supervising->followed())->toHaveCount(1);
});

it('names what a restart did not bring back, and does not call it a completed start', function (): void {
    $restarted = TheThingScreenOfTheShed::aVerbThatRan(
        WhereAServiceEndedUp::as('Jellyfin', HowAServiceRuns::Healthy),
        WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::CrashLooping),
        WhereAServiceEndedUp::as('Prowlarr', HowAServiceRuns::Absent),
    )->amountingTo(HowTheStackIsRunning::Degraded);

    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($restarted))));

    expect($drawn)->toContain(
        whatTheCatalogueSays('health.came_to.not_everything_back'),
        whatTheCatalogueSays('health.running.degraded'),
        whatTheCatalogueSays('health.came_to.not_back', ['name' => 'Sonarr', 'runs' => whatTheCatalogueSays('health.service.crash-looping')]),
        whatTheCatalogueSays('health.came_to.not_back', ['name' => 'Prowlarr', 'runs' => whatTheCatalogueSays('health.service.absent')]),
    );
    expect($drawn)->not->toContain(
        whatTheCatalogueSays('health.came_to.everything_back'),
        whatTheCatalogueSays('health.came_to.not_back', ['name' => 'Jellyfin', 'runs' => whatTheCatalogueSays('health.service.healthy')]),
    );
});

it('is not a completed start where the stack called it active and a service is still short of running', function (): void {
    $restarted = TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Starting))->amountingTo(HowTheStackIsRunning::Active);

    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($restarted))));

    expect($drawn)->toContain(
        whatTheCatalogueSays('health.came_to.not_everything_back'),
        whatTheCatalogueSays('health.came_to.not_back', ['name' => 'Sonarr', 'runs' => whatTheCatalogueSays('health.service.starting')]),
    );
});

it('says a report that does not say what the services amount to is not a completed start, and names nothing it did not name', function (): void {
    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done(TheThingScreenOfTheShed::aVerbThatRan()))));

    // Nothing stands in for what the stack did not say: the next line is
    // the one naming what did not come back.
    expect(whatIsDrawnAfter(whatTheCatalogueSays('health.came_to.not_everything_back'), $drawn))
        ->toBe(whatTheCatalogueSays('health.came_to.none_named'));
});

it('lets a stop the stack did not sum up stand on its own', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->whichCameTo(HowAgreedWorkIsGoing::done(TheThingScreenOfTheShed::aVerbThatRan()));
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->agree();
    $screen->whileItSettles();
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();

    expect(whatIsDrawnAfter(whatTheCatalogueSays('health.came_to.heading'), $drawn))->toBe(whatTheCatalogueSays('health.came_to.stopped'))
        ->and($screen->whatItCameTo()->amountsToSaid)->toBe('');
});

it('says a start that brought everything back did', function (): void {
    $started = TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy))->amountingTo(HowTheStackIsRunning::Active);

    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($started))));

    expect($drawn)->toContain(whatTheCatalogueSays('health.came_to.everything_back'), whatTheCatalogueSays('health.running.active'));
    expect($drawn)->not->toContain(whatTheCatalogueSays('health.came_to.not_everything_back'), whatTheCatalogueSays('health.came_to.none_named'));
});

it('shows the command the stack ran, under its heading', function (): void {
    $started = TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy))->amountingTo(HowTheStackIsRunning::Active);

    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($started))));

    expect(whatIsDrawnAfter(whatTheCatalogueSays('stacks.command.ran'), $drawn))->toBe('docker compose up -d')
        ->and($drawn)->not->toContain(whatTheCatalogueSays('stacks.command.will_run'));
});

it('says a start the stack declined, with the stack\'s reason, and names nothing as not back', function (): void {
    $declined = WhatTheVerbCameTo::declined(
        WhetherItWasRehearsed::CarriedOut,
        WhereTheServicesEndedUp::of(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Stopped)),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
        'This machine is on its battery.',
        TheStackEdits::none(),
    );

    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($declined))));

    expect($drawn)->toContain(whatTheCatalogueSays('health.came_to.declined', ['why' => 'This machine is on its battery.']));
    expect($drawn)->not->toContain(
        whatTheCatalogueSays('health.came_to.not_everything_back'),
        whatTheCatalogueSays('health.came_to.not_back', ['name' => 'Sonarr', 'runs' => whatTheCatalogueSays('health.service.stopped')]),
        // It ran nothing, so there is no command to show for it.
        whatTheCatalogueSays('stacks.command.ran'),
        whatTheCatalogueSays('stacks.command.will_run'),
    );
});

it('labels a rehearsal as one, in the tense of what would happen, and names nothing as not back', function (): void {
    $rehearsed = WhatTheVerbCameTo::reported(
        WhetherItWasRehearsed::Rehearsed,
        WhereTheServicesEndedUp::of(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Stopped)),
        TheServicesLeftOut::of(AServiceLeftOut::needing(ServiceId::called('qbittorrent'), 'qBittorrent', WhatItWouldNeed::Torrent, Forms::these(Form::called('library')))),
        ThePortsHeld::of(),
        TheStackEdits::none(),
        TheCommandLine::of('docker', 'compose', 'up', '-d'),
    );

    $screen = TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($rehearsed)));
    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew($screen);

    expect($screen->whatItCameTo()->wasRehearsed)->toBeTrue();
    expect(whatIsDrawnAfter(whatTheCatalogueSays('stacks.command.will_run'), $drawn))->toBe('docker compose up -d');
    expect($drawn)->toContain(
        whatTheCatalogueSays('health.came_to.a_rehearsal'),
        whatTheCatalogueSays('health.came_to.rehearsed'),
        whatTheCatalogueSays('health.came_to.would_be_left_out', ['name' => 'qBittorrent', 'needs' => WhatANeedSays::of(WhatItWouldNeed::Torrent)]),
    );
    expect($drawn)->not->toContain(
        whatTheCatalogueSays('health.came_to.left_out', ['name' => 'qBittorrent', 'needs' => WhatANeedSays::of(WhatItWouldNeed::Torrent)]),
        whatTheCatalogueSays('health.came_to.not_everything_back'),
        whatTheCatalogueSays('health.came_to.not_back', ['name' => 'Sonarr', 'runs' => whatTheCatalogueSays('health.service.stopped')]),
        whatTheCatalogueSays('stacks.command.ran'),
    );
});

it('does not judge a stop by what came back', function (): void {
    // Leaving services down is what a stop is for, so what it left down is
    // not named as having failed to come back.
    $stopped = TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Stopped))->amountingTo(HowTheStackIsRunning::Inactive);
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->whichCameTo(HowAgreedWorkIsGoing::done($stopped));
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->agree();
    $screen->whileItSettles();
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();

    expect($screen->whatItCameTo()->namesWhatDidNotComeBack)->toBeFalse();
    expect($drawn)->toContain(whatTheCatalogueSays('health.came_to.stopped'), whatTheCatalogueSays('health.running.inactive'));
    expect($drawn)->not->toContain(
        whatTheCatalogueSays('health.came_to.not_everything_back'),
        whatTheCatalogueSays('health.came_to.not_back', ['name' => 'Sonarr', 'runs' => whatTheCatalogueSays('health.service.stopped')]),
    );
});

it('names what the plan left out with what each needed, and what holds each port it wanted', function (): void {
    $started = WhatTheVerbCameTo::reported(
        WhetherItWasRehearsed::CarriedOut,
        WhereTheServicesEndedUp::of(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Running)),
        TheServicesLeftOut::of(AServiceLeftOut::needing(ServiceId::called('qbittorrent'), 'qBittorrent', WhatItWouldNeed::Torrent, Forms::these(Form::called('library')))),
        ThePortsHeld::of(APortHeld::of(8989, 'sonarr', 'media-server')),
        TheStackEdits::none(),
        TheCommandLine::of('docker', 'compose', 'up', '-d'),
    )->amountingTo(HowTheStackIsRunning::Active);

    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($started))));

    expect($drawn)->toContain(__('health.came_to.left_out', ['name' => 'qBittorrent', 'needs' => WhatANeedSays::of(WhatItWouldNeed::Torrent)]))
        ->toContain(__('health.came_to.port_held', ['port' => '8989', 'wanted_by' => 'sonarr', 'held_by' => 'media-server']))
        ->toContain(__('health.came_to.everything_back'));
});

it('says the stack has no outcome for a verb any more, rather than that it failed', function (): void {
    $screen = TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::ended()));
    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew($screen);

    expect($screen->whatItCameTo()->stands->hasEnded())->toBeTrue()
        ->and($screen->whatItCameTo()->stands->isWorking())->toBeFalse()
        ->and($drawn)->toContain(__('health.came_to.no_outcome'));
});

it('says nothing about a verb where none was sent, and asks after none', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    expect($screen->whatItCameTo()->stands->wasAsked())->toBeFalse()
        ->and($supervising->followed())->toBe([]);
    expect(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(whatTheCatalogueSays('health.came_to.heading'));
});

it('does not ask after a handle it holds without the verb it was sent for', function (): void {
    // A route parameter fills the public property of the same name when the
    // screen mounts, so a handle can be held that this screen never sent. A
    // report is judged against the verb it answers, and with no verb there is
    // nothing to judge it by.
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $screen = TheThingScreenOfTheShed::theScreen($supervising);
    $screen->setParams(['stack' => TheThingScreenOfTheShed::theMachine()->id()->stored(), 'service' => 'sonarr', 'took' => AStackThatSupervises::THE_JOB]);
    $screen->mountComponent();

    expect($screen->took)->toBe(AStackThatSupervises::THE_JOB)
        ->and($screen->whatItCameTo()->stands->wasAsked())->toBeFalse()
        ->and($supervising->followed())->toBe([]);
});

it('says what stood in the way of a verb that could not be delivered', function (): void {
    $supervising = AStackThatSupervises::withButRefusing(
        WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Stopped, HowTheStackIsRunning::Partial),
        Obstacle::of(KindOfObstacle::StackDidNotAnswer),
    );
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);

    expect($screen->whatItCameTo()->stands->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($screen->whatItCameTo()->stands->isWorking())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($supervising->followed())->toBe([]);
});

it('offers the same verb again where other work held the stack, and sends it only when tapped', function (): void {
    $supervising = AStackThatSupervises::withButRefusing(
        WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Stopped, HowTheStackIsRunning::Partial),
        Obstacle::of(KindOfObstacle::StackIsBusy),
    );
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('connection.try_again'))
        ->and($supervising->whatItWasToldToDo())->toHaveCount(1);

    $screen->tryAgain();

    expect($supervising->whatItWasToldToDo())->toHaveCount(2)
        ->and($supervising->whatItWasToldToDo()[1])->toEqual($supervising->whatItWasToldToDo()[0]);
});

it('does not offer a verb again where anything but other work stood in its way', function (): void {
    $supervising = AStackThatSupervises::withButRefusing(
        WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Stopped, HowTheStackIsRunning::Partial),
        Obstacle::of(KindOfObstacle::StackDidNotAnswer),
    );
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->tryAgain();

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('connection.try_again'))
        ->and($supervising->whatItWasToldToDo())->toHaveCount(1);
});

it('says what stood in the way of asking after it, and lets go of a session refused there', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))), keychain: $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->whileItSettles();
    $screen->render();

    expect($screen->whatItCameTo()->stands->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(TheThingScreenOfTheShed::theMachine()->id()))->toBeFalse();
});

it('keeps the session where asking after a verb could not reach the machine', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork))), keychain: $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->whileItSettles();
    $screen->render();

    expect($screen->whatItCameTo()->stands->went->met)->toEqual(KindOfObstacle::DeviceHasNoNetwork->said())
        ->and($keychain->isHolding(TheThingScreenOfTheShed::theMachine()->id()))->toBeTrue();
});

it('says the session ended where it ended between sending a verb and asking after it', function (): void {
    $keychain = AKeychainInMemory::working();
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $screen = TheThingScreenOfTheShed::theScreen($supervising, keychain: $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $keychain->forget(TheThingScreenOfTheShed::theMachine()->id());
    $screen->whileItSettles();
    $screen->render();

    expect($screen->whatItCameTo()->stands->went->isSignedIn)->toBeFalse()
        ->and($screen->whatItCameTo()->stands->wasAsked())->toBeTrue()
        ->and($supervising->followed())->toBe([]);
});

it('says the session ended where a verb was agreed to on a device no longer signed in', function (): void {
    $keychain = AKeychainInMemory::working();
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = TheThingScreenOfTheShed::theScreen($supervising, keychain: $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $keychain->forget(TheThingScreenOfTheShed::theMachine()->id());
    $screen->agree();

    expect($screen->whatItCameTo()->stands->went->isSignedIn)->toBeFalse()
        ->and($screen->whatItCameTo()->stands->wasAsked())->toBeTrue()
        ->and($supervising->whatItWasToldToDo())->toBe([]);
});

it('asks after the verb again, and reads the machine again, when asked to', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)));
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->whileItSettles();
    $screen->render();
    $screen->whatItCameTo();
    $screen->answer();
    $askings = $supervising->askings();

    // As the frames draw it: what is running on the first, and what the verb
    // came to on the next.
    $screen->again();
    $screen->render();
    $screen->answer();
    $screen->whatItCameTo();
    $screen->render();
    $screen->whatItCameTo();

    expect($supervising->followed())->toHaveCount(2)
        ->and($supervising->askings())->toBe($askings + 1);
});

it('does not ask after an earlier verb once a later one could not be sent', function (): void {
    // The handle names the verb sent last. A later verb that never reached the
    // stack leaves nothing to ask after, and asking after the one before it
    // would report that verb's outcome as this one's.
    $keychain = AKeychainInMemory::working();
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = TheThingScreenOfTheShed::theScreen($supervising, keychain: $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Restart->value);
    $screen->agree();
    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $keychain->forget(TheThingScreenOfTheShed::theMachine()->id());
    $screen->agree();
    $keychain->keep(TheThingScreenOfTheShed::theMachine()->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    // What was asked after before this point is each question's rehearsal; the
    // restart's own handle is the one that must not be asked after from here on.
    $askedAfterSoFar = count($supervising->followed());
    $screen->again();

    expect($screen->whatItCameTo()->stands->wasAsked())->toBeFalse()
        ->and($supervising->whatItWasToldToDo())->toHaveCount(1)
        ->and($supervising->followed())->toHaveCount($askedAfterSoFar);
});

it('a verb that has finished is not polled for, and neither is a standing listing', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::ended());
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    // The listing drawn before the verb was known to have ended is read
    // once more, and then neither is asked after again.
    TheThingScreenOfTheShed::whatStartingSonarrDrew($screen);
    $screen->whileItSettles();
    $screen->render();
    $screen->answer();
    $askings = $supervising->askings();
    $followed = count($supervising->followed());
    $screen->whileItSettles();
    $screen->render();
    $screen->answer();
    $screen->whatItCameTo();

    expect($supervising->askings())->toBe($askings)
        ->and($supervising->followed())->toHaveCount($followed);
});

it('draws what the stack says a start is waiting for, in place of its own sentence, newest first', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $hearing = AStackThatSaysWhatItWaitsOn::saying(
        WhatAStartWaitsOn::saying('Waiting for the database'),
        WhatAStartWaitsOn::saying('Waiting for sonarr to answer'),
    );
    $screen = TheThingScreenOfTheShed::theScreen($supervising, hearing: $hearing);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->whileItSettles();

    $first = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($first)->toContain('Waiting for the database')
        ->and($first)->not->toContain(__('health.came_to.running'));

    $screen->whileItSettles();
    $newest = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($newest)->toContain('Waiting for sonarr to answer')
        ->and($newest)->not->toContain('Waiting for the database');
});

it('keeps the last line where a wake heard nothing new, or could not hear the stream', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $hearing = AStackThatSaysWhatItWaitsOn::saying(
        WhatAStartWaitsOn::saying('Waiting for the database'),
        WhatAStartWaitsOn::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
    );
    $screen = TheThingScreenOfTheShed::theScreen($supervising, hearing: $hearing);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->whileItSettles();
    $screen->whileItSettles();
    $screen->whileItSettles();

    expect($screen->waitsOn)->toBe('Waiting for the database');
});

it('lets go of a session the stream refused while a start runs, keeping the last line', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $keychain = AKeychainInMemory::working();
    $hearing = AStackThatSaysWhatItWaitsOn::saying(
        WhatAStartWaitsOn::saying('Waiting for the database'),
        WhatAStartWaitsOn::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)),
    );
    $screen = TheThingScreenOfTheShed::theScreen($supervising, keychain: $keychain, hearing: $hearing);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->whileItSettles();
    $screen->whileItSettles();

    expect($keychain->isHolding(TheThingScreenOfTheShed::theMachine()->id()))->toBeFalse()
        ->and($screen->waitsOn)->toBe('Waiting for the database');
});

it('listens for what a start waits on only while a start or a restart it sent runs, and lets go otherwise', function (): void {
    $running = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $hearing = AStackThatSaysWhatItWaitsOn::saying(WhatAStartWaitsOn::saying('Waiting for the database'));
    $screen = TheThingScreenOfTheShed::theScreen($running, hearing: $hearing);

    $screen->whileItSettles();
    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->agree();
    $screen->whileItSettles();

    expect($hearing->asked())->toBe(0)
        ->and($hearing->lettingsGo())->toBe(2)
        ->and($screen->waitsOn)->toBe('');
});

it('lets go rather than listening where the verb it sent comes back from the device emptied', function (): void {
    // What was sent is the screen's public state, so it comes back from the
    // device on the next request and can come back empty. A running start is
    // then one this screen can no longer say it sent, and it listens to nothing.
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $hearing = AStackThatSaysWhatItWaitsOn::saying(WhatAStartWaitsOn::saying('Waiting for the database'));
    $screen = TheThingScreenOfTheShed::theScreen($supervising, hearing: $hearing);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->sent = null;
    $screen->whileItSettles();

    expect($hearing->asked())->toBe(0)
        ->and($hearing->lettingsGo())->toBe(1)
        ->and($screen->waitsOn)->toBe('');
});

it('clears the last line when another verb is sent', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $hearing = AStackThatSaysWhatItWaitsOn::saying(WhatAStartWaitsOn::saying('Waiting for the database'));
    $screen = TheThingScreenOfTheShed::theScreen($supervising, hearing: $hearing);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->whileItSettles();
    $screen->wouldYouLike(WhatToDoWithIt::Start->value);

    expect($screen->waitsOn)->toBe('');
});

it('keeps the line it has where the session went between the sending and the listening', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $keychain = AKeychainInMemory::working();
    $hearing = AStackThatSaysWhatItWaitsOn::saying(WhatAStartWaitsOn::saying('Waiting for the database'));
    $screen = TheThingScreenOfTheShed::theScreen($supervising, keychain: $keychain, hearing: $hearing);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $keychain->forget(TheThingScreenOfTheShed::theMachine()->id());
    $screen->whileItSettles();

    expect($hearing->asked())->toBe(0)
        ->and($screen->waitsOn)->toBe('');
});

it('lets go of what a start waits on when the screen stops, keeping the last line', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $hearing = AStackThatSaysWhatItWaitsOn::saying(WhatAStartWaitsOn::saying('Waiting for the database'));
    $screen = TheThingScreenOfTheShed::theScreen($supervising, hearing: $hearing);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->whileItSettles();
    $screen->stop();

    expect($hearing->lettingsGo())->toBe(1)
        ->and($screen->waitsOn)->toBe('Waiting for the database');
});

it('says a file the operator edited is kept, with what lemonfiber would change in it and what the marks mean', function (): void {
    $started = WhatTheVerbCameTo::reported(
        WhetherItWasRehearsed::CarriedOut,
        WhereTheServicesEndedUp::of(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy)),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
        TheStackEdits::these(
            AStackEdit::at('compose.yaml', "- image: mine\n+ image: ours\n"),
            AStackEdit::at('env/sonarr.env', ''),
        ),
        TheCommandLine::of('docker', 'compose', 'up', '-d'),
    );

    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($started))));

    expect($drawn)->toContain(
        whatTheCatalogueSays('stacks.edits.heading'),
        whatTheCatalogueSays('stacks.edits.kept', ['path' => 'compose.yaml']),
        whatTheCatalogueSays('stacks.edits.would_change'),
        '- image: mine',
        '+ image: ours',
        whatTheCatalogueSays('stacks.edits.legend'),
        whatTheCatalogueSays('stacks.edits.kept', ['path' => 'env/sonarr.env']),
    );
    // The file whose difference no line shows says only that it is kept: the
    // legend is said once, under the file that has lines.
    expect(array_count_values($drawn)[whatTheCatalogueSays('stacks.edits.legend')])->toBe(1);
});

it('says nothing about edited files where the stack left none', function (): void {
    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done(
        TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy)),
    ))));

    expect($drawn)->not->toContain(whatTheCatalogueSays('stacks.edits.heading'));
});

it('marks a diff\'s lines with the marks its legend names, in every language', function (string $locale): void {
    app()->setLocale($locale);
    $theirs = explode(' ', whatTheCatalogueSays(HowAStackEditReads::THEIRS, ['line' => 'x']))[0];
    $lemonfibers = explode(' ', whatTheCatalogueSays(HowAStackEditReads::LEMONFIBERS, ['line' => 'x']))[0];

    expect(whatTheCatalogueSays('stacks.edits.legend'))->toContain(sprintf(' %s ', $theirs))
        ->and(whatTheCatalogueSays('stacks.edits.legend'))->toContain(sprintf(' %s ', $lemonfibers))
        ->and($theirs)->not->toBe($lemonfibers);
})->with(['en', 'nl']);

/** What the stack's rehearsal of restarting Sonarr reported. */
function aRehearsedRestartOfSonarr(): WhatTheVerbCameTo
{
    return WhatTheVerbCameTo::reported(
        WhetherItWasRehearsed::Rehearsed,
        WhereTheServicesEndedUp::of(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Stopped)),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
        TheStackEdits::none(),
        TheCommandLine::of('docker', 'compose', 'restart', 'sonarr'),
    );
}

/**
 * Ask to restart Sonarr, which needs a yes, and draw the question.
 *
 * @return list<string>
 */
function whatAskingToRestartSonarrDrew(WhatToDoWithThis $screen): array
{
    $screen->wouldYouLike(WhatToDoWithIt::Restart->value);

    return WhatTheDeviceWouldDraw::by($screen)->said();
}

it('shows the command a yes will run before the yes, from the stack\'s own rehearsal of it', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done(aRehearsedRestartOfSonarr()));
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $drawn = whatAskingToRestartSonarrDrew($screen);

    expect(whatIsDrawnAfter(whatTheCatalogueSays('stacks.command.will_run'), $drawn))->toBe('docker compose restart sonarr')
        ->and($supervising->whatItWasAskedToRehearse())->toEqual([AgreedTo::theService(WhatToDoWithIt::Restart, ServiceId::called('sonarr'))])
        // Rehearsing is not the yes: nothing is carried out until it is given.
        ->and($supervising->whatItWasToldToDo())->toBe([]);
});

it('shows the command once the stack has finished working out the rehearsal, and nothing before', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $drawn = whatAskingToRestartSonarrDrew($screen);

    expect($drawn)->not->toContain(whatTheCatalogueSays('stacks.command.will_run'))
        ->and($drawn)->not->toContain(whatTheCatalogueSays('stacks.command.unread'))
        ->and($screen->rehearsalOfTheYes)->toBe(AStackThatSupervises::THE_JOB)
        ->and($screen->asking())->not->toBeNull();
});

it('asks the question without the command where the stack would not rehearse it, and says it could not be read', function (Underway|HowAgreedWorkIsGoing $refused): void {
    $supervising = $refused instanceof Underway
        ? AStackThatSupervises::withButRefusing(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Stopped, HowTheStackIsRunning::Partial), Obstacle::of(KindOfObstacle::StackDidNotAnswer))
        : TheThingScreenOfTheShed::aStoppedSonarrThatCameTo($refused);
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $drawn = whatAskingToRestartSonarrDrew($screen);

    expect($drawn)->not->toContain(whatTheCatalogueSays('stacks.command.will_run'))
        ->and($drawn)->toContain(whatTheCatalogueSays('stacks.command.unread'))
        ->and($screen->asking())->not->toBeNull()
        ->and($screen->rehearsalOfTheYes)->toBeNull();
})->with([
    'refused' => [Underway::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))],
    'forgotten' => [HowAgreedWorkIsGoing::ended()],
    'met asking after it' => [HowAgreedWorkIsGoing::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))],
]);

it('never shows, before the yes, a command from a report that was not a rehearsal, and says the rehearsal could not be read', function (): void {
    $carriedOut = WhatTheVerbCameTo::reported(
        WhetherItWasRehearsed::CarriedOut,
        WhereTheServicesEndedUp::of(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Running)),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
        TheStackEdits::none(),
        TheCommandLine::of('docker', 'compose', 'restart', 'sonarr'),
    );
    $screen = TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($carriedOut)));

    $drawn = whatAskingToRestartSonarrDrew($screen);

    expect($drawn)->not->toContain(whatTheCatalogueSays('stacks.command.will_run'))
        ->and($drawn)->toContain(whatTheCatalogueSays('stacks.command.unread'));
});

it('shows no command before the yes where the rehearsal says the verb would run nothing', function (): void {
    $declined = WhatTheVerbCameTo::declined(
        WhetherItWasRehearsed::Rehearsed,
        WhereTheServicesEndedUp::of(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Stopped)),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
        'another run is already working on this stack',
        TheStackEdits::none(),
    );
    $screen = TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($declined)));

    $drawn = whatAskingToRestartSonarrDrew($screen);

    expect($drawn)->not->toContain(whatTheCatalogueSays('stacks.command.will_run'))
        ->and($drawn)->not->toContain(whatTheCatalogueSays('stacks.command.unread'))
        ->and($screen->willRun)->toBe('')
        ->and($screen->asking())->not->toBeNull();
});

it('puts the rehearsal away with the question, whichever way it is answered', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done(aRehearsedRestartOfSonarr()));
    $agreed = TheThingScreenOfTheShed::theScreen($supervising);
    $declined = TheThingScreenOfTheShed::theScreen($supervising);

    whatAskingToRestartSonarrDrew($agreed);
    $agreed->agree();
    whatAskingToRestartSonarrDrew($declined);
    $declined->neverMind();

    expect($agreed->willRun)->toBe('')
        ->and($declined->willRun)->toBe('')
        ->and($supervising->whatItWasToldToDo())->toEqual([AgreedTo::theService(WhatToDoWithIt::Restart, ServiceId::called('sonarr'))]);
});

it('puts away that a rehearsal could not be read with the question it was about', function (): void {
    $screen = TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::ended()));

    whatAskingToRestartSonarrDrew($screen);
    $unreadWhileAsked = $screen->willRunUnread;
    $screen->neverMind();

    expect($unreadWhileAsked)->toBeTrue()
        ->and($screen->willRunUnread)->toBeFalse();
});

it('rehearses nothing for a start, which asks no question', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());

    TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen($supervising));

    expect($supervising->whatItWasAskedToRehearse())->toBe([]);
});

/** The name the stack's rehearsal gives the restart it would carry out. */
const THE_RESTART_OFFERED_HERE = '9b1e4f20';

/** A stopped Sonarr whose rehearsal of a restart names what it offers, and which refuses the yes because that moved. */
function aSonarrWhoseRestartMoved(): AStackThatSupervises
{
    return TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done(aRehearsedRestartOfSonarr()->offering(AnOffer::named(THE_RESTART_OFFERED_HERE))))
        ->whoseYesMoved(ARefusalInItsWords::said(WhatAMovedOfferSays::SUMMARY, WhatAMovedOfferSays::MEANING, WhatTheRefusalNamed::nothing()));
}

/**
 * Agree to the restart asked about, and draw what following it came to.
 *
 * @return list<string>
 */
function whatAgreeingToRestartSonarrDrew(WhatToDoWithThis $screen): array
{
    $screen->agree();
    $screen->whileItSettles();

    return WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();
}

it('carries the name the rehearsal gave what it offers back with the yes', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done(aRehearsedRestartOfSonarr()->offering(AnOffer::named(THE_RESTART_OFFERED_HERE))));
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    whatAskingToRestartSonarrDrew($screen);
    $screen->agree();

    expect($supervising->whatItWasToldToDo())->toEqual([
        AgreedTo::theService(WhatToDoWithIt::Restart, ServiceId::called('sonarr'))->quoting(AnOffer::named(THE_RESTART_OFFERED_HERE)),
    ]);
});

it('asks again, under the stack\'s own words, where the restart agreed to was refused because what it would restart moved', function (): void {
    $supervising = aSonarrWhoseRestartMoved();
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    whatAskingToRestartSonarrDrew($screen);
    whatAgreeingToRestartSonarrDrew($screen);
    // The frame that heard the refusal put the question back; the screen's
    // cadence draws it on the next.
    $screen->whileItSettles();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(WhatAMovedOfferSays::SUMMARY)
        ->and($drawn)->toContain(WhatAMovedOfferSays::MEANING)
        ->and($drawn)->toContain(whatTheCatalogueSays('health.about_to', ['what' => 'sonarr']))
        // The question as it stands now was rehearsed afresh, under no name
        // the first rehearsal gave, and the yes was sent once.
        ->and($supervising->whatItWasAskedToRehearse())->toEqual([
            AgreedTo::theService(WhatToDoWithIt::Restart, ServiceId::called('sonarr')),
            AgreedTo::theService(WhatToDoWithIt::Restart, ServiceId::called('sonarr')),
        ])
        ->and($supervising->whatItWasToldToDo())->toHaveCount(1);
});

it('puts what the stack said about a moved restart away once the question is answered again', function (string $answer): void {
    $screen = TheThingScreenOfTheShed::theScreen(aSonarrWhoseRestartMoved());
    whatAskingToRestartSonarrDrew($screen);
    whatAgreeingToRestartSonarrDrew($screen);
    $before = $screen->movedOn;

    $answer === 'agreeing' ? $screen->agree() : $screen->neverMind();

    expect($before)->not->toBeNull()
        ->and($screen->movedOn)->toBeNull();
})->with(['agreeing', 'never minding']);

it('offers nothing to put back a file the operator edited: the outcome offers what it would with no edit at all', function (): void {
    $starting = static fn(TheStackEdits $edits): WhatTheVerbCameTo => WhatTheVerbCameTo::reported(
        WhetherItWasRehearsed::CarriedOut,
        WhereTheServicesEndedUp::of(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy)),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
        $edits,
        TheCommandLine::of('docker', 'compose', 'up', '-d'),
    );
    $edited = TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($starting(TheStackEdits::these(AStackEdit::at('compose.yaml', "- image: mine\n+ image: ours\n"))))));
    $untouched = TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($starting(TheStackEdits::none()))));

    $saidWhereEdited = TheThingScreenOfTheShed::whatStartingSonarrDrew($edited);
    TheThingScreenOfTheShed::whatStartingSonarrDrew($untouched);

    expect($saidWhereEdited)->toContain(whatTheCatalogueSays('stacks.edits.kept', ['path' => 'compose.yaml']))
        ->and(WhatTheDeviceWouldDraw::onTheSecondFrame($edited)->offers())->not->toBe([])->toBe(WhatTheDeviceWouldDraw::onTheSecondFrame($untouched)->offers());
});
