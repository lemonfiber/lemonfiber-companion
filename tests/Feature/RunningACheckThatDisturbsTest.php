<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\DownloadsMoving;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatRunningACheckDisturbs;
use Modules\Operator\Internal\ViewModels\WhatOneFindingSays;
use Modules\Stacks\Api\AStacksScreen;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatRunsChecks;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\WhatTheDeviceWouldDraw;

const THE_KILLSWITCH = 'vpn.killswitch';

const WHAT_IT_DISTURBS = 'The tunnel goes down while traffic is tested';

/** The killswitch finding, left unverified because checking it disturbs the tunnel, bounded as a case says. */
function aKillswitchLeftUnverified(?int $seconds = 30): Finding
{
    $check = $seconds === null
        ? ACheckThatDisturbs::unbounded(WHAT_IT_DISTURBS, 'vpn')
        : ACheckThatDisturbs::boundedTo(WHAT_IT_DISTURBS, 'vpn', $seconds);

    return Finding::of(
        Check::of(THE_KILLSWITCH),
        Category::Vpn,
        'Traffic stops when the tunnel drops',
        Conclusion::Unverified,
        WhatTheCheckSaid::couldNotSay('Checking it takes the tunnel down', Remedies::none()),
        WhoPutItThere::bundled(),
    )->disturbing($check);
}

/** The killswitch finding as the run left it: passed. */
function aKillswitchThatHeld(): Finding
{
    return Finding::of(Check::of(THE_KILLSWITCH), Category::Vpn, 'Traffic stops when the tunnel drops', Conclusion::Passed, WhatTheCheckSaid::nothingWrong(), WhoPutItThere::bundled());
}

function aReportHolding(Finding ...$findings): Report
{
    return Report::of(Overall::Degraded, Findings::of(...$findings));
}

/** What the stack last said is moving, heard on the screen's own stream. */
function aSummaryWithDownloadsMoving(string ...$names): WhatWasHeard
{
    return WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing())->moving(DownloadsMoving::these(...$names)));
}

function theRunningScreen(AStackThatWasAsked $asking, AStackThatRunsChecks $running, ?AStackThatSpeaksUp $stream = null, ?FrozenClock $clock = null, string $check = THE_KILLSWITCH): WhatRunningACheckDisturbs
{
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new WhatRunningACheckDisturbs($asking, $running, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening($stream, $clock));
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $check]);

    return $screen;
}

it('offers running a check that disturbs on Health, and leads to what it disturbs; a finding that waits on none offers nothing', function (): void {
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(aReportHolding(aKillswitchLeftUnverified(), aKillswitchThatHeld())));
    $runnable = array_map(static fn(WhatOneFindingSays $row): string => $row->runnable, $screen->findings());

    expect($runnable)->toBe([THE_KILLSWITCH, ''])
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('health.disturbs.run_this', ['title' => 'Traffic stops when the tunnel drops']))
        ->and($screen->goes()->running(THE_KILLSWITCH))->toBe(AStacksScreen::Run->forTheStacksCheck(TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id(), Check::of(THE_KILLSWITCH)));
});

it('says what running it disturbs and the bound the stack gives before anything is sent, never as a rehearsal', function (): void {
    $running = AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended());
    $drawn = WhatTheDeviceWouldDraw::by(theRunningScreen(AStackThatWasAsked::saying(aReportHolding(aKillswitchLeftUnverified(30))), $running));

    expect($drawn->said())->toContain('Traffic stops when the tunnel drops', __('health.disturbs.what'), WHAT_IT_DISTURBS, __('health.for_at_most', ['seconds' => 30]), __('health.disturbs.not_a_rehearsal'))
        ->and($drawn->said())->not->toContain(__('health.disturbs.interrupts'))
        ->and($drawn->offers())->toContain(__('health.disturbs.run_it'))
        ->and($running->ran())->toBe([]);
});

it('says the stack gives no bound where it gives none, and puts no figure of its own there', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theRunningScreen(AStackThatWasAsked::saying(aReportHolding(aKillswitchLeftUnverified(null))), AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended())));

    expect($drawn->said())->toContain(__('health.disturbs.no_bound'))
        ->and($drawn->said())->not->toContain(__('health.for_at_most', ['seconds' => 0]));
});

it('names each download the stack says is moving as one the run interrupts', function (): void {
    $screen = theRunningScreen(
        AStackThatWasAsked::saying(aReportHolding(aKillswitchLeftUnverified())),
        AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended()),
        AStackThatSpeaksUp::holdingOpen(aSummaryWithDownloadsMoving('Dune (2021)', 'Severance S02E03')),
    );
    $screen->listen();

    expect($screen->downloadsMoving())->toBe(['Dune (2021)', 'Severance S02E03'])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.disturbs.interrupts'), 'Dune (2021)', 'Severance S02E03');
});

it('runs the check the report offers, follows it to its end, and draws the finding again with how long ago it ran', function (): void {
    $running = AStackThatRunsChecks::answering(WhatBecameOfTheWork::answered(aReportHolding(aKillswitchThatHeld())));
    $clock = FrozenClock::at(Instant::atEpochSeconds(1_790_000_000));
    $screen = theRunningScreen(AStackThatWasAsked::saying(aReportHolding(aKillswitchLeftUnverified())), $running, clock: $clock);
    $screen->run();

    expect($running->ran())->toBe(['vpn'])
        ->and($screen->awaitsAnOutcome())->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.disturbs.working'));

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->awaitsAnOutcome())->toBeFalse()
        ->and(array_map(static fn(Job $job): string => $job->shown(), $running->followed()))->toBe([AStackThatRunsChecks::THE_RUN])
        ->and($screen->ranAt)->toBe(1_790_000_000)
        ->and($drawn->said())->toContain(__(Conclusion::Passed->saidOnTheScreen()), __('health.disturbs.ran', ['ago' => trans_choice('health.ago.minutes', 0)]))
        ->and($drawn->offers())->not->toContain(__('health.disturbs.run_it'));
});

it('runs nothing where the report offers nothing to run, or holds no such check', function (Report $report, string $check): void {
    $running = AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended());
    $screen = theRunningScreen(AStackThatWasAsked::saying($report), $running, check: $check);
    $screen->run();

    expect($running->ran())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('health.disturbs.run_it'));
})->with([
    'a finding that waits on nothing' => [aReportHolding(aKillswitchThatHeld()), THE_KILLSWITCH],
    'a check the report does not hold' => [aReportHolding(aKillswitchLeftUnverified()), 'disk.space'],
]);

it('says the stack did not run it, in its own words, and that it has no outcome where it has none', function (): void {
    $refusal = ARefusalInItsWords::said('The tunnel is already down', '', WhatTheRefusalNamed::nothing(), 'Bring the tunnel up first');
    $refused = theRunningScreen(AStackThatWasAsked::saying(aReportHolding(aKillswitchLeftUnverified())), AStackThatRunsChecks::sayingAtOnce(WhatBecameOfTheWork::refused($refusal)));
    $refused->run();
    $ended = theRunningScreen(AStackThatWasAsked::saying(aReportHolding(aKillswitchLeftUnverified())), AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended()));
    $ended->run();
    $ended->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($refused)->said())->toContain(__('health.disturbs.refused'), 'The tunnel is already down')
        ->and(WhatTheDeviceWouldDraw::by($ended)->said())->toContain(__('health.disturbs.no_outcome'));
});

it('says the stack is not reporting a check the route names', function (): void {
    expect(WhatTheDeviceWouldDraw::by(theRunningScreen(AStackThatWasAsked::saying(aReportHolding(aKillswitchThatHeld())), AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended()), check: 'disk.space'))->said())
        ->toContain(__('health.disturbs.not_found'));
});

it('runs a check the stack gives no bound for as it was shown, with no bound', function (): void {
    $running = AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended());
    theRunningScreen(AStackThatWasAsked::saying(aReportHolding(aKillswitchLeftUnverified(null))), $running)->run();

    expect($running->ran())->toBe(['vpn']);
});

it('sends the yes once, however often it is tapped while the stack runs it', function (): void {
    $running = AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended());
    $screen = theRunningScreen(AStackThatWasAsked::saying(aReportHolding(aKillswitchLeftUnverified())), $running);
    $screen->run();
    $screen->run();

    expect($running->ran())->toBe(['vpn']);
});

it('says what stood in the way of a run, and asks the stack again only when told to', function (): void {
    $asking = AStackThatWasAsked::saying(aReportHolding(aKillswitchLeftUnverified()));
    $screen = theRunningScreen($asking, AStackThatRunsChecks::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $screen->run();

    expect($screen->howItIsGoing()->went->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('health.disturbs.run_it'))
        ->and($asking->askings())->toBe(1);

    $screen->again();
    $screen->answer();

    expect($asking->askings())->toBe(2);
});
