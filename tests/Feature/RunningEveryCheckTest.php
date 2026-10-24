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
use Modules\Kernel\Api\WhatARunCovers;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowARunOfChecksReads;
use Modules\Operator\Internal\Screens\WhatRunningChecksDisturbs;
use Modules\Operator\Internal\ViewModels\ADisturbanceAsShown;
use Modules\Operator\Internal\ViewModels\ARunOffered;
use Modules\Operator\Internal\ViewModels\WhatOneFindingSays;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
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

/** A finding the stack left unverified because checking it disturbs something, bounded as a case says. */
function aFindingThatDisturbs(string $check, Category $family, string $title, string $disturbs, ?int $seconds): Finding
{
    $disturbing = $seconds === null
        ? ACheckThatDisturbs::unbounded($disturbs, $check)
        : ACheckThatDisturbs::boundedTo($disturbs, $check, $seconds);

    return Finding::of(Check::of($check), $family, $title, Conclusion::Unverified, WhatTheCheckSaid::couldNotSay('Checking it disturbs something', Remedies::none()), WhoPutItThere::bundled())
        ->disturbing($disturbing);
}

/** A finding that passed, which disturbs nothing to check. */
function aFindingThatPassed(string $check, Category $family, string $title): Finding
{
    return Finding::of(Check::of($check), $family, $title, Conclusion::Passed, WhatTheCheckSaid::nothingWrong(), WhoPutItThere::bundled());
}

/** What the stack reports before a run: the killswitch and a live search left unverified, and the disk fine. */
function aReportBeforeTheRun(): Report
{
    return Report::of(Overall::Degraded, Findings::of(
        aFindingThatDisturbs('vpn.killswitch', Category::Vpn, 'Traffic stops when the tunnel drops', 'The tunnel goes down while traffic is tested', 30),
        aFindingThatDisturbs('providers.search', Category::Providers, 'An indexer answers a search', 'A search of each indexer is spent', null),
        aFindingThatPassed('storage.room', Category::Storage, 'There is room on the disk'),
    ));
}

/** What the stack reports once the run came back: every check concluded. */
function aReportAfterTheRun(): Report
{
    return Report::of(Overall::Healthy, Findings::of(
        aFindingThatPassed('vpn.killswitch', Category::Vpn, 'Traffic stops when the tunnel drops'),
        aFindingThatPassed('providers.search', Category::Providers, 'An indexer answers a search'),
        aFindingThatPassed('storage.room', Category::Storage, 'There is room on the disk'),
    ));
}

/** What the VPN family is called on the screen. */
function theVpnFamilyAsSaid(): string
{
    $said = __(Category::Vpn->saidOnTheScreen());

    return is_string($said) ? $said : '';
}

/** The screen, for every check or for the family a case names. Named for this file (`G10`). */
function theScreenRunningChecks(AStackThatWasAsked $asking, AStackThatRunsChecks $running, ?Category $family = null, ?AStackThatSpeaksUp $stream = null, ?FrozenClock $clock = null, bool $signedIn = true): WhatRunningChecksDisturbs
{
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatRunningChecksDisturbs($asking, $running, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening($stream, $clock));
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $family instanceof Category ? $family->value : WhatARunCovers::EVERY]);

    return $screen;
}

it('offers running every check on Health, and a family\'s checks while that family is read', function (): void {
    $health = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(aReportBeforeTheRun()));
    $everyOffer = __('health.run_every.offer_every');
    $familyOffer = __('health.run_every.offer_family', ['family' => theVpnFamilyAsSaid()]);
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id();

    expect(WhatTheDeviceWouldDraw::by($health)->offers())->toContain($everyOffer)
        ->and(WhatTheDeviceWouldDraw::by($health)->offers())->not->toContain($familyOffer)
        ->and(array_map(static fn(ARunOffered $run): string => $run->covers->word(), $health->runsOffered()))->toBe([WhatARunCovers::EVERY]);

    $health->read(Category::Vpn->value);

    expect(WhatTheDeviceWouldDraw::by($health)->offers())->toContain($everyOffer)
        ->and(WhatTheDeviceWouldDraw::by($health)->offers())->toContain($familyOffer)
        ->and(array_map(static fn(ARunOffered $run): string => $run->covers->word(), $health->runsOffered()))->toBe([WhatARunCovers::EVERY, 'vpn'])
        ->and($health->goes()->runningChecks(WhatARunCovers::theFamily(Category::Vpn)))->toBe(AStacksScreen::RunChecks->forTheStacksChecks($stack, WhatARunCovers::theFamily(Category::Vpn)))
        ->and(NativeRouter::resolve($health->goes()->runningChecks(WhatARunCovers::theFamily(Category::Vpn))))->not->toBeNull()
        ->and(NativeRouter::resolve($health->goes()->runningChecks(WhatARunCovers::everyCheck())))->not->toBeNull();
});

it('offers nothing to run on a route that names no run this app knows', function (): void {
    $running = AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended());
    $asking = AStackThatWasAsked::saying(aReportBeforeTheRun());
    $screen = theScreenRunningChecks($asking, $running);
    $screen->setParams(['stack' => TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id()->stored(), 'service' => 'gluetun']);
    $screen->run();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($running->ran())->toBe([])
        ->and($asking->askings())->toBe(1)
        ->and($drawn->said())->toContain(__(HowARunOfChecksReads::UNKNOWN))
        ->and($drawn->offers())->not->toContain(__('health.run_every.run_it'));
});

it('lists every check a run of all would run that disturbs something, with the stack\'s bound or that it gave none, before anything is sent', function (): void {
    $running = AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended());
    $screen = theScreenRunningChecks(AStackThatWasAsked::saying(aReportBeforeTheRun()), $running);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(array_map(static fn(ADisturbanceAsShown $one): string => $one->title, $screen->answer()->disturbing))->toBe(['Traffic stops when the tunnel drops', 'An indexer answers a search'])
        ->and($drawn->said())->toContain(__(HowARunOfChecksReads::EVERY), __('health.run_every.what'), 'The tunnel goes down while traffic is tested', __('health.for_at_most', ['seconds' => 30]), 'A search of each indexer is spent', __('health.disturbs.no_bound'), __('health.run_every.not_a_rehearsal'))
        ->and($drawn->said())->not->toContain(__('health.run_every.disturbs_nothing'))
        ->and($drawn->offers())->toContain(__('health.run_every.run_it'))
        ->and($screen->ranAgo()->said)->toBe('')
        ->and($running->ran())->toBe([]);
});

it('lists only the family\'s checks that disturb something for a family\'s run, under the family', function (): void {
    $screen = theScreenRunningChecks(AStackThatWasAsked::saying(aReportBeforeTheRun()), AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended()), Category::Vpn);

    expect(array_map(static fn(ADisturbanceAsShown $one): string => $one->title, $screen->answer()->disturbing))->toBe(['Traffic stops when the tunnel drops'])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(HowARunOfChecksReads::FAMILY, ['family' => theVpnFamilyAsSaid()]));
});

it('says a run that checks nothing that disturbs disturbs nothing, and still offers it', function (): void {
    $screen = theScreenRunningChecks(AStackThatWasAsked::saying(aReportBeforeTheRun()), AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended()), Category::Storage);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('health.run_every.disturbs_nothing'))
        ->and($drawn->offers())->toContain(__('health.run_every.run_it'));
});

it('names each download the stack says is moving as one the run interrupts', function (): void {
    $summary = WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing())->moving(DownloadsMoving::these('Dune (2021)')));
    $screen = theScreenRunningChecks(AStackThatWasAsked::saying(aReportBeforeTheRun()), AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended()), stream: AStackThatSpeaksUp::holdingOpen($summary));
    $screen->listen();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.disturbs.interrupts'), 'Dune (2021)');
});

it('runs what the route covers, follows it to its end, and draws what it came back with and how long ago', function (?Category $family, string $sent, array $found): void {
    $running = AStackThatRunsChecks::answering(WhatBecameOfTheWork::answered(aReportAfterTheRun()));
    $screen = theScreenRunningChecks(AStackThatWasAsked::saying(aReportBeforeTheRun()), $running, $family, clock: FrozenClock::at(Instant::atEpochSeconds(1_790_000_000)));
    $screen->run();

    expect($running->ran())->toBe([$sent])
        ->and($screen->awaitsAnOutcome())->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.run_every.working'));

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->awaitsAnOutcome())->toBeFalse()
        ->and(array_map(static fn(Job $job): string => $job->shown(), $running->followed()))->toBe([AStackThatRunsChecks::THE_RUN])
        ->and($screen->ranAt)->toBe(1_790_000_000)
        ->and(array_map(static fn(WhatOneFindingSays $one): string => $one->title, $screen->answer()->found))->toBe($found)
        ->and($drawn->said())->toContain(__('health.run_every.found'), __(Conclusion::Passed->saidOnTheScreen()), __('health.disturbs.ran', ['ago' => trans_choice('health.ago.minutes', 0)]))
        ->and($screen->answer()->disturbing)->not->toBe([]);
})->with([
    'every check' => [null, AStackThatRunsChecks::EVERY_CHECK, ['Traffic stops when the tunnel drops', 'An indexer answers a search', 'There is room on the disk']],
    'a family' => [Category::Vpn, 'vpn', ['Traffic stops when the tunnel drops']],
]);

it('says a run that came back with no findings found none', function (): void {
    $screen = theScreenRunningChecks(AStackThatWasAsked::saying(aReportBeforeTheRun()), AStackThatRunsChecks::sayingAtOnce(WhatBecameOfTheWork::answered(Report::of(Overall::Healthy, Findings::of()))));
    $screen->run();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.run_every.found_nothing'));
});

it('sends the yes once, however often it is tapped while the stack runs it', function (): void {
    $running = AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended());
    $screen = theScreenRunningChecks(AStackThatWasAsked::saying(aReportBeforeTheRun()), $running);
    $screen->run();
    $screen->run();

    expect($running->ran())->toBe([AStackThatRunsChecks::EVERY_CHECK]);
});

it('says the stack did not run them, in its own words, and that it has no outcome where it has none', function (): void {
    $refusal = ARefusalInItsWords::said('The tunnel is already down', '', WhatTheRefusalNamed::nothing(), 'Bring the tunnel up first');
    $refused = theScreenRunningChecks(AStackThatWasAsked::saying(aReportBeforeTheRun()), AStackThatRunsChecks::sayingAtOnce(WhatBecameOfTheWork::refused($refusal)));
    $refused->run();
    $ended = theScreenRunningChecks(AStackThatWasAsked::saying(aReportBeforeTheRun()), AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended()));
    $ended->run();
    $ended->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($refused)->said())->toContain(__('health.run_every.refused'), 'The tunnel is already down')
        ->and(WhatTheDeviceWouldDraw::by($ended)->said())->toContain(__('health.run_every.no_outcome'));
});

it('says what stood in the way of a run, and lets a refused session go', function (): void {
    $stopped = theScreenRunningChecks(AStackThatWasAsked::saying(aReportBeforeTheRun()), AStackThatRunsChecks::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $stopped->run();

    expect($stopped->howItIsGoing()->went->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($stopped)->offers())->not->toContain(__('health.run_every.run_it'));
});

it('runs nothing where the stack could not be read, or the session has ended', function (AStackThatWasAsked $asking, bool $signedIn): void {
    $running = AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended());
    $screen = theScreenRunningChecks($asking, $running, signedIn: $signedIn);
    $screen->run();

    expect($running->ran())->toBe([])
        ->and($screen->answer()->went->cameBack())->toBeFalse();
})->with([
    'a stack that did not answer' => [AStackThatWasAsked::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), true],
    'a refused session' => [AStackThatWasAsked::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), true],
    'no session' => [AStackThatWasAsked::saying(aReportBeforeTheRun()), false],
]);

it('asks the stack again only when told to', function (): void {
    $asking = AStackThatWasAsked::saying(aReportBeforeTheRun());
    $screen = theScreenRunningChecks($asking, AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended()));
    $screen->answer();
    $screen->answer();

    expect($asking->askings())->toBe(1);

    $screen->again();
    $screen->answer();

    expect($asking->askings())->toBe(2);
});

it('renders its own view', function (): void {
    expect(theScreenRunningChecks(AStackThatWasAsked::saying(aReportBeforeTheRun()), AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended()))->render()->name())
        ->toBe(WhatRunningChecksDisturbs::TEMPLATE);
});
