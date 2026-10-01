<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APortHeld;
use Modules\Kernel\Api\AServiceLeftOut;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheHoldSaysNothing;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhereAServiceEndedUp;
use Modules\Kernel\Api\WhereTheServicesEndedUp;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** A restart's report, with the services given and nothing left out. */
function aRestartReported(WhereAServiceEndedUp ...$services): WhatTheVerbCameTo
{
    return WhatTheVerbCameTo::reported(
        WhetherItWasRehearsed::CarriedOut,
        WhereTheServicesEndedUp::of(...$services),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
        TheStackEdits::none(),
    );
}

/** A restart's report that the stack declined to run, for the reason given. */
function aRestartDeclined(string $why, WhereAServiceEndedUp ...$services): WhatTheVerbCameTo
{
    return WhatTheVerbCameTo::declined(
        WhetherItWasRehearsed::CarriedOut,
        WhereTheServicesEndedUp::of(...$services),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
        $why,
        TheStackEdits::none(),
    );
}

/** What a report says those services amount to, or `unsaid`. */
function whatItAmountsTo(WhatTheVerbCameTo $report): string
{
    return $report->amountsTo(
        said: static fn(HowTheStackIsRunning $condition): TheWordCarriedOut => new TheWordCarriedOut($condition->value),
        unsaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unsaid'),
    )->said;
}

/** Whether a report ran, or the reason it declined. */
function whetherTheReportRan(WhatTheVerbCameTo $report): string
{
    return $report->whetherItRan(
        ran: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ran'),
        declined: static fn(string $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('declined: %s', $why)),
    )->said;
}

it('brought everything back only where the stack says active and names nothing short of up', function (): void {
    $up = WhereAServiceEndedUp::as('Jellyfin', HowAServiceRuns::Healthy);
    $down = WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Failed);

    expect(aRestartReported($up)->amountingTo(HowTheStackIsRunning::Active)->broughtEverythingBack())->toBeTrue()
        // Four of five is not a completed start, whatever the one word says.
        ->and(aRestartReported($up, $down)->amountingTo(HowTheStackIsRunning::Active)->broughtEverythingBack())->toBeFalse()
        // Nor is the stack's own word for less than all of it.
        ->and(aRestartReported($up)->amountingTo(HowTheStackIsRunning::Partial)->broughtEverythingBack())->toBeFalse()
        ->and(aRestartReported($up)->amountingTo(HowTheStackIsRunning::Degraded)->broughtEverythingBack())->toBeFalse()
        // And a report that does not say is not one that said yes.
        ->and(aRestartReported($up)->broughtEverythingBack())->toBeFalse();
});

it('names what did not come back and leaves what did', function (): void {
    $report = aRestartReported(
        WhereAServiceEndedUp::as('Jellyfin', HowAServiceRuns::Healthy),
        WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::CrashLooping),
    );

    $named = [];

    foreach ($report->whatDidNotComeBack() as $service) {
        $named[] = sprintf('%s:%s', $service->name(), $service->runs()->value);
    }

    expect($named)->toBe(['Sonarr:crash-looping']);
});

it('says what those services amount to only where the stack said', function (): void {
    expect(whatItAmountsTo(aRestartReported()))->toBe('unsaid')
        ->and(whatItAmountsTo(aRestartReported()->amountingTo(HowTheStackIsRunning::Partial)))->toBe('partial');
});

it('ran unless the stack declined, and a declined start carries its reason', function (): void {
    expect(whetherTheReportRan(aRestartReported()))->toBe('ran')
        ->and(whetherTheReportRan(aRestartDeclined('The stack was stopped on purpose.')))
        ->toBe('declined: The stack was stopped on purpose.');
});

it('refuses a declined start with no reason', function (string $blank): void {
    expect(fn(): WhatTheVerbCameTo => aRestartDeclined($blank))->toThrow(TheHoldSaysNothing::class);
})->with(['', '   ']);

it('keeps the reason a start was declined when it is told what the services amount to', function (): void {
    $report = aRestartDeclined('On battery.')->amountingTo(HowTheStackIsRunning::Inactive);

    expect(whatItAmountsTo($report))->toBe('inactive')
        ->and(whetherTheReportRan($report))->toBe('declined: On battery.');
});

it('carries whether it was rehearsed, what was left out and which ports are held, whichever way it was built', function (): void {
    $leftOut = TheServicesLeftOut::of(AServiceLeftOut::needing(ServiceId::called('qbittorrent'), 'qBittorrent', WhatItWouldNeed::Torrent, Forms::these(Form::called('hunt'))));
    $ports = ThePortsHeld::of(APortHeld::of(8096, 'jellyfin', 'media'));
    $reports = [
        WhatTheVerbCameTo::reported(WhetherItWasRehearsed::Rehearsed, WhereTheServicesEndedUp::of(), $leftOut, $ports, TheStackEdits::none())->amountingTo(HowTheStackIsRunning::Active),
        WhatTheVerbCameTo::declined(WhetherItWasRehearsed::Rehearsed, WhereTheServicesEndedUp::of(), $leftOut, $ports, 'Autostart was never asked for.', TheStackEdits::none())->amountingTo(HowTheStackIsRunning::Active),
    ];

    foreach ($reports as $report) {
        expect($report->was())->toBe(WhetherItWasRehearsed::Rehearsed)
            ->and($report->leftOut())->toBe($leftOut)
            ->and($report->portsHeld())->toBe($ports);
    }
});

it('keeps the services it waited for whichever way it was built', function (): void {
    $down = WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Absent);

    expect(aRestartReported($down)->amountingTo(HowTheStackIsRunning::Partial)->whatDidNotComeBack())->toHaveCount(1)
        ->and(aRestartDeclined('On battery.', $down)->whatDidNotComeBack())->toHaveCount(1);
});
