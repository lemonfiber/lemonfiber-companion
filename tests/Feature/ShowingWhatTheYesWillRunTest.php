<?php

declare(strict_types=1);

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhereAServiceEndedUp;
use Modules\Kernel\Api\WhereTheServicesEndedUp;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\TheLineTheYesRuns;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\TheThingScreenOfTheShed;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatAMovedOfferSays;

it('shows the command a yes will run before the yes, from the stack\'s own rehearsal of it', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done(TheThingScreenOfTheShed::aRehearsedRestartOfSonarr()));
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $drawn = TheThingScreenOfTheShed::whatAskingToRestartSonarrDrew($screen);

    expect(TheThingScreenOfTheShed::drawnAfter(Catalogue::line('stacks.command.will_run'), $drawn))->toBe('docker compose restart sonarr')
        ->and($supervising->whatItWasAskedToRehearse())->toEqual([AgreedTo::theService(WhatToDoWithIt::Restart, ServiceId::called('sonarr'))])
        // Rehearsing is not the yes: nothing is carried out until it is given.
        ->and($supervising->whatItWasToldToDo())->toBe([]);
});

it('shows the command once the stack has finished working out the rehearsal, and nothing before', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $drawn = TheThingScreenOfTheShed::whatAskingToRestartSonarrDrew($screen);

    expect($drawn)->not->toContain(Catalogue::line('stacks.command.will_run'))
        ->and($drawn)->not->toContain(Catalogue::line('stacks.command.unread'))
        ->and($screen->rehearsalOfTheYes)->toEqual(Job::named(AStackThatSupervises::THE_JOB))
        ->and($screen->asking())->not->toBeNull();
});

it('follows a rehearsal still being worked out on the screen\'s cadence, and shows the command once it is', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());
    $screen = TheThingScreenOfTheShed::theScreen($supervising);
    TheThingScreenOfTheShed::whatAskingToRestartSonarrDrew($screen);
    $supervising->fromNowOnComingTo(HowAgreedWorkIsGoing::done(TheThingScreenOfTheShed::aRehearsedRestartOfSonarr()));
    TheThingScreenOfTheShed::aWakeOf($screen);

    expect($screen->willRun?->command)->toBe('docker compose restart sonarr')
        ->and($supervising->followed())->toEqual([Job::named(AStackThatSupervises::THE_JOB), Job::named(AStackThatSupervises::THE_JOB)])
        ->and($screen->rehearsalOfTheYes)->toBeNull();
});

it('asks the question without the command where the stack would not rehearse it, and says it could not be read', function (Underway|HowAgreedWorkIsGoing $refused): void {
    $supervising = $refused instanceof Underway
        ? AStackThatSupervises::withButRefusing(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Stopped, HowTheStackIsRunning::Partial), Obstacle::of(KindOfObstacle::StackDidNotAnswer))
        : TheThingScreenOfTheShed::aStoppedSonarrThatCameTo($refused);
    $screen = TheThingScreenOfTheShed::theScreen($supervising);

    $drawn = TheThingScreenOfTheShed::whatAskingToRestartSonarrDrew($screen);

    expect($drawn)->not->toContain(Catalogue::line('stacks.command.will_run'))
        ->and($drawn)->toContain(Catalogue::line('stacks.command.unread'))
        ->and($screen->asking())->not->toBeNull()
        ->and($screen->rehearsalOfTheYes)->toBeNull();
})->with([
    'refused' => [Underway::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))],
    'forgotten' => [HowAgreedWorkIsGoing::noOutcome()],
    'met asking after it' => [HowAgreedWorkIsGoing::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))],
    'said to have moved, which a rehearsal cannot be' => [HowAgreedWorkIsGoing::moved(ARefusalInItsWords::said(WhatAMovedOfferSays::SUMMARY, WhatAMovedOfferSays::MEANING, WhatTheRefusalNamed::nothing()))],
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

    $drawn = TheThingScreenOfTheShed::whatAskingToRestartSonarrDrew($screen);

    expect($drawn)->not->toContain(Catalogue::line('stacks.command.will_run'))
        ->and($drawn)->toContain(Catalogue::line('stacks.command.unread'));
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

    $drawn = TheThingScreenOfTheShed::whatAskingToRestartSonarrDrew($screen);

    expect($drawn)->not->toContain(Catalogue::line('stacks.command.will_run'))
        ->and($drawn)->not->toContain(Catalogue::line('stacks.command.unread'))
        ->and($screen->willRun)->toBeNull()
        ->and($screen->asking())->not->toBeNull();
});

it('puts the rehearsal away with the question, whichever way it is answered', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done(TheThingScreenOfTheShed::aRehearsedRestartOfSonarr()));
    $agreed = TheThingScreenOfTheShed::theScreen($supervising);
    $declined = TheThingScreenOfTheShed::theScreen($supervising);

    TheThingScreenOfTheShed::whatAskingToRestartSonarrDrew($agreed);
    $agreed->agree();
    TheThingScreenOfTheShed::whatAskingToRestartSonarrDrew($declined);
    $declined->neverMind();

    expect($agreed->willRun)->toBeNull()
        ->and($declined->willRun)->toBeNull()
        ->and($supervising->whatItWasToldToDo())->toEqual([AgreedTo::theService(WhatToDoWithIt::Restart, ServiceId::called('sonarr'))]);
});

it('puts away that a rehearsal could not be read with the question it was about', function (): void {
    $screen = TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::noOutcome()));

    TheThingScreenOfTheShed::whatAskingToRestartSonarrDrew($screen);
    $unreadWhileAsked = $screen->willRun;
    $screen->neverMind();

    expect($unreadWhileAsked)->toEqual(TheLineTheYesRuns::unread())
        ->and($screen->willRun)->toBeNull();
});

it('rehearses nothing for a start, which asks no question', function (): void {
    $supervising = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::stillRunning());

    TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen($supervising));

    expect($supervising->whatItWasAskedToRehearse())->toBe([]);
});
