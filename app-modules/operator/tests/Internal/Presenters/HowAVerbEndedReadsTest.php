<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\APortHeld;
use Modules\Kernel\Api\AServiceLeftOut;
use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhereAServiceEndedUp;
use Modules\Kernel\Api\WhereTheServicesEndedUp;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\Presenters\HowAVerbEndedReads;
use Modules\Operator\Internal\ViewModels\ADiffLineAsShown;
use Modules\Operator\Internal\ViewModels\AnEditAsShown;
use Modules\Operator\Internal\ViewModels\APortHeldAsShown;
use Modules\Operator\Internal\ViewModels\AServiceLeftOutAsShown;
use Modules\Operator\Internal\ViewModels\AServiceNotBackAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\HowTheVerbWent;

/** Everything a state with no report to draw holds, beside how the reading went and which of the three it is. */
function aVerbWithNoReport(HowTheReadingWent $went, bool $wasAsked = true, bool $isWorking = false, bool $hasEnded = false): HowTheVerbWent
{
    return new HowTheVerbWent(
        went: $went,
        wasAsked: $wasAsked,
        isWorking: $isWorking,
        hasEnded: $hasEnded,
        wasRehearsed: false,
        cameToSaid: null,
        because: '',
        amountsToSaid: null,
        namesWhatDidNotComeBack: false,
        notBack: [],
        leftOutSaid: null,
        leftOut: [],
        portsHeld: [],
        editsKept: [],
    );
}

/** A restart that left Sonarr failed beside a healthy Jellyfin, with qBittorrent left out and a port held. */
function aRestartThatLeftOneBehind(WhetherItWasRehearsed $was): WhatTheVerbCameTo
{
    return WhatTheVerbCameTo::reported(
        $was,
        WhereTheServicesEndedUp::of(
            WhereAServiceEndedUp::as('Jellyfin', HowAServiceRuns::Healthy),
            WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Failed),
        ),
        TheServicesLeftOut::of(AServiceLeftOut::needing(ServiceId::called('qbittorrent'), 'qBittorrent', WhatItWouldNeed::Usenet, Forms::these(Form::called('hunt')))),
        ThePortsHeld::of(APortHeld::of(8989, 'sonarr', 'media-server')),
        TheStackEdits::these(AStackEdit::at('compose.yaml', "- image: mine\n+ image: ours\n")),
    )->amountingTo(HowTheStackIsRunning::Partial);
}

it('holds nothing of a report in every state that has none', function (): void {
    $reads = new HowAVerbEndedReads();

    expect($reads->notAsked())->toEqual(aVerbWithNoReport(HowTheReadingWent::itCameBack(), wasAsked: false))
        ->and($reads->signedOut())->toEqual(aVerbWithNoReport(HowTheReadingWent::theSessionEnded()))
        ->and($reads->met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)))->toEqual(aVerbWithNoReport(HowTheReadingWent::somethingStopped(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))
        ->and($reads->running())->toEqual(aVerbWithNoReport(HowTheReadingWent::itCameBack(), isWorking: true))
        ->and($reads->ended())->toEqual(aVerbWithNoReport(HowTheReadingWent::itCameBack(), hasEnded: true));
});

it('draws a restart that left one behind whole, naming it with where it stood', function (): void {
    expect(new HowAVerbEndedReads()->done(aRestartThatLeftOneBehind(WhetherItWasRehearsed::CarriedOut), WhatToDoWithIt::Restart))->toEqual(new HowTheVerbWent(
        went: HowTheReadingWent::itCameBack(),
        wasAsked: true,
        isWorking: false,
        hasEnded: false,
        wasRehearsed: false,
        cameToSaid: 'health.came_to.not_everything_back',
        because: '',
        amountsToSaid: 'health.running.partial',
        namesWhatDidNotComeBack: true,
        notBack: [new AServiceNotBackAsShown('Sonarr', 'health.service.failed')],
        leftOutSaid: 'health.came_to.left_out',
        leftOut: [new AServiceLeftOutAsShown('qBittorrent', 'health.rehearsal.needs.usenet', ['hunt'])],
        portsHeld: [new APortHeldAsShown('8989', 'sonarr', 'media-server')],
        editsKept: [new AnEditAsShown('compose.yaml', [
            new ADiffLineAsShown('stacks.edits.theirs', 'image: mine'),
            new ADiffLineAsShown('stacks.edits.lemonfibers', 'image: ours'),
        ])],
    ));
});

it('words a rehearsal as what would happen, and names nothing as not back', function (): void {
    $read = new HowAVerbEndedReads()->done(aRestartThatLeftOneBehind(WhetherItWasRehearsed::Rehearsed), WhatToDoWithIt::Restart);

    expect($read->wasRehearsed)->toBeTrue()
        ->and($read->cameToSaid)->toBe('health.came_to.rehearsed')
        ->and($read->leftOutSaid)->toBe('health.came_to.would_be_left_out')
        ->and($read->namesWhatDidNotComeBack)->toBeFalse();
});

it('says a declined start with its reason, and names nothing as not back', function (): void {
    $declined = WhatTheVerbCameTo::declined(
        WhetherItWasRehearsed::CarriedOut,
        WhereTheServicesEndedUp::of(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Stopped)),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
        'Autostart was never asked for.',
        TheStackEdits::none(),
    );
    $read = new HowAVerbEndedReads()->done($declined, WhatToDoWithIt::Start);

    expect($read->cameToSaid)->toBe('health.came_to.declined')
        ->and($read->because)->toBe('Autostart was never asked for.')
        ->and($read->namesWhatDidNotComeBack)->toBeFalse();
});

it('says a fetch was carried out, apart from a stop, and does not judge it by what came back', function (): void {
    $read = new HowAVerbEndedReads()->done(aRestartThatLeftOneBehind(WhetherItWasRehearsed::CarriedOut), WhatToDoWithIt::Pull);

    expect($read->cameToSaid)->toBe('health.came_to.fetched')
        ->and($read->namesWhatDidNotComeBack)->toBeFalse();
});

it('says a stop was carried out, and does not judge it by what came back', function (): void {
    $read = new HowAVerbEndedReads()->done(aRestartThatLeftOneBehind(WhetherItWasRehearsed::CarriedOut), WhatToDoWithIt::Stop);

    expect($read->cameToSaid)->toBe('health.came_to.stopped')
        ->and($read->namesWhatDidNotComeBack)->toBeFalse();
});

it('says a start that brought everything back did, with nothing to name, and says nothing for a report that does not say what they amount to', function (): void {
    $everything = WhatTheVerbCameTo::reported(
        WhetherItWasRehearsed::CarriedOut,
        WhereTheServicesEndedUp::of(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Running)),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
        TheStackEdits::none(),
    );
    $complete = new HowAVerbEndedReads()->done($everything->amountingTo(HowTheStackIsRunning::Active), WhatToDoWithIt::Start);
    $unsaid = new HowAVerbEndedReads()->done($everything, WhatToDoWithIt::Start);

    expect($complete->cameToSaid)->toBe('health.came_to.everything_back')
        ->and($complete->namesWhatDidNotComeBack)->toBeFalse()
        ->and($unsaid->cameToSaid)->toBe('health.came_to.not_everything_back')
        ->and($unsaid->amountsToSaid)->toBe('')
        ->and($unsaid->namesWhatDidNotComeBack)->toBeTrue()
        ->and($unsaid->notBack)->toBe([]);
});
