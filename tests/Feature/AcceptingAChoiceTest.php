<?php

declare(strict_types=1);

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItSaysUnderneath;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatAcceptingItCosts;
use Modules\Operator\Internal\ViewModels\WhatOneFindingSays;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatAccepts;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\WhatTheDeviceWouldDraw;

const THE_CHOICE = 'vpn.contained';

const WHAT_THE_CHOICE_COSTS = 'Your address is visible to everybody you share with.';

function aWarningAbout(string $check, Standing $standing, Conclusion $conclusion = Conclusion::Warned): Finding
{
    return Finding::of(
        Check::of($check),
        Category::Vpn,
        'Torrents run inside a VPN',
        $conclusion,
        WhatTheCheckSaid::wentWrong(Code::of('VPN-9'), WHAT_THE_CHOICE_COSTS, Remedies::of(Remedy::of('Add a VPN provider')), Severity::Warning, $standing, WhatItSaysUnderneath::none()),
        WhoPutItThere::bundled(),
    );
}

function aReportWith(Finding ...$findings): Report
{
    return Report::of(Overall::Degraded, Findings::of(...$findings));
}

function theAcceptingScreen(AStackThatWasAsked $asking, AStackThatAccepts $accepting, string $check = THE_CHOICE): WhatAcceptingItCosts
{
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new WhatAcceptingItCosts($asking, $accepting, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $check]);

    return $screen;
}

function anAcceptanceThatTakes(): AStackThatAccepts
{
    return AStackThatAccepts::answering(WhatBecameOfTheWork::answered(aReportWith(aWarningAbout(THE_CHOICE, Standing::Suppressed))));
}

it('offers accepting a warning on Health and leads to what it costs, and offers nothing for a failure', function (): void {
    $report = aReportWith(aWarningAbout(THE_CHOICE, Standing::Guided), aWarningAbout('vpn.egress', Standing::Remediable, Conclusion::Failed));
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying($report));
    $accepting = array_map(static fn(WhatOneFindingSays $row): string => $row->accepting, $screen->findings());

    expect($accepting)->toContain(THE_CHOICE, '')
        ->and(array_count_values($accepting)[THE_CHOICE])->toBe(1)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('health.choice.accept_this', ['title' => 'Torrents run inside a VPN']))
        ->and($screen->goes()->accepting(THE_CHOICE))->toBe(AStacksScreen::Accept->forTheStacksCheck(TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id(), Check::of(THE_CHOICE)));
});

it('draws an accepted warning on Health as accepted and still a warning, and offers accepting it no more', function (): void {
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(aReportWith(aWarningAbout(THE_CHOICE, Standing::Suppressed))));
    [$row] = $screen->findings();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($row->verdict)->toBe('health.choice.accepted')
        ->and($row->accepting)->toBe('')
        ->and($drawn->said())->toContain(__('health.choice.accepted'), WHAT_THE_CHOICE_COSTS)
        ->and($drawn->said())->not->toContain(__(Severity::Warning->saidOnTheScreen()));
});

it('says what the choice costs in the stack\'s words before anything is sent, and offers accepting it', function (): void {
    $accepting = anAcceptanceThatTakes();
    $drawn = WhatTheDeviceWouldDraw::by(theAcceptingScreen(AStackThatWasAsked::saying(aReportWith(aWarningAbout(THE_CHOICE, Standing::Guided))), $accepting));

    expect($drawn->said())->toContain('Torrents run inside a VPN', __('health.choice.costs'), WHAT_THE_CHOICE_COSTS, 'Add a VPN provider', __('health.choice.what_accepting_does'))
        ->and($drawn->offers())->toContain(__('health.choice.accept_it'))
        ->and($accepting->accepted())->toBe([]);
});

it('accepts the check the report warns about, follows the work, and then draws it accepted and still holding', function (): void {
    $accepting = anAcceptanceThatTakes();
    $screen = theAcceptingScreen(AStackThatWasAsked::saying(aReportWith(aWarningAbout(THE_CHOICE, Standing::Guided))), $accepting);
    $screen->accept();

    expect($accepting->accepted())->toBe([THE_CHOICE])
        ->and($screen->awaitsAnOutcome())->toBeTrue();

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->awaitsAnOutcome())->toBeFalse()
        ->and(array_map(static fn(Job $job): string => $job->shown(), $accepting->followed()))->toBe([AStackThatAccepts::THE_ACCEPTANCE])
        ->and($drawn->said())->toContain(__('health.choice.accepted'), __('health.choice.accepted_stays'), WHAT_THE_CHOICE_COSTS)
        ->and($drawn->offers())->not->toContain(__('health.choice.accept_it'));
});

it('offers nothing to accept for a failure, a check the report does not raise, or one already accepted', function (Report $report, string $check, string $said): void {
    $accepting = anAcceptanceThatTakes();
    $screen = theAcceptingScreen(AStackThatWasAsked::saying($report), $accepting, $check);
    $screen->accept();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->offers())->not->toContain(__('health.choice.accept_it'))
        ->and($drawn->said())->toContain(__($said))
        ->and($accepting->accepted())->toBe([]);
})->with([
    'a failure' => [aReportWith(aWarningAbout(THE_CHOICE, Standing::Remediable, Conclusion::Failed)), THE_CHOICE, 'health.choice.not_raised'],
    'a check not raised' => [aReportWith(aWarningAbout(THE_CHOICE, Standing::Guided)), 'disk.space', 'health.choice.not_raised'],
    'a passing check' => [aReportWith(Finding::of(Check::of(THE_CHOICE), Category::Vpn, 'Torrents run inside a VPN', Conclusion::Passed, WhatTheCheckSaid::nothingWrong(), WhoPutItThere::bundled())), THE_CHOICE, 'health.choice.not_raised'],
    'a check that could not say' => [aReportWith(Finding::of(Check::of(THE_CHOICE), Category::Vpn, 'Torrents run inside a VPN', Conclusion::Unverified, WhatTheCheckSaid::couldNotSay('the tunnel did not answer', Remedies::none()), WhoPutItThere::bundled())), THE_CHOICE, 'health.choice.not_raised'],
    'one already accepted' => [aReportWith(aWarningAbout(THE_CHOICE, Standing::Suppressed)), THE_CHOICE, 'health.choice.accepted_stays'],
]);

it('says so where the stack answered and its report does not show the warning accepted', function (): void {
    $accepting = AStackThatAccepts::answering(WhatBecameOfTheWork::answered(aReportWith(aWarningAbout(THE_CHOICE, Standing::Guided))));
    $screen = theAcceptingScreen(AStackThatWasAsked::saying(aReportWith(aWarningAbout(THE_CHOICE, Standing::Guided))), $accepting);
    $screen->accept();
    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.choice.not_taken'));
});

it('draws a refusal in the stack\'s own words, and an outcome the stack no longer has', function (): void {
    $why = ARefusalInItsWords::said('Nothing in this run warns about vpn.contained', '', WhatTheRefusalNamed::nothing(), 'Answer one of the warnings this run raised');
    $refused = theAcceptingScreen(AStackThatWasAsked::saying(aReportWith(aWarningAbout(THE_CHOICE, Standing::Guided))), AStackThatAccepts::answering(WhatBecameOfTheWork::refused($why)));
    $refused->accept();
    $refused->whileItRuns();

    $ended = theAcceptingScreen(AStackThatWasAsked::saying(aReportWith(aWarningAbout(THE_CHOICE, Standing::Guided))), AStackThatAccepts::answering(WhatBecameOfTheWork::ended()));
    $ended->accept();
    $ended->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($refused)->said())->toContain(__('health.choice.refused'), 'Nothing in this run warns about vpn.contained')
        ->and(WhatTheDeviceWouldDraw::by($ended)->said())->toContain(__('health.choice.no_outcome'));
});

it('says what stood in the way, and lets go of a session the stack refused', function (): void {
    $met = theAcceptingScreen(AStackThatWasAsked::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), anAcceptanceThatTakes());
    $refused = theAcceptingScreen(AStackThatWasAsked::saying(aReportWith(aWarningAbout(THE_CHOICE, Standing::Guided))), AStackThatAccepts::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))));
    $refused->accept();

    expect(WhatTheDeviceWouldDraw::by($met)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($refused->howItIsGoing()->went->isSignedIn)->toBeFalse();
});

it('asks the stack again for the warning when asked to look again', function (): void {
    $asking = AStackThatWasAsked::saying(aReportWith(aWarningAbout(THE_CHOICE, Standing::Guided)));
    $screen = theAcceptingScreen($asking, anAcceptanceThatTakes());
    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($asking->askings())->toBe(2);
});

it('says the session ended where it went while the acceptance was followed', function (): void {
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $screen = new WhatAcceptingItCosts(AStackThatWasAsked::saying(aReportWith(aWarningAbout(THE_CHOICE, Standing::Guided))), anAcceptanceThatTakes(), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => THE_CHOICE]);
    $screen->accept();
    $keychain->forget($stack->id());
    $screen->whileItRuns();

    $screen->again();

    expect($screen->howItIsGoing()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->awaitsAnOutcome())->toBeFalse();
});

it('is where the accept route goes', function (): void {
    $path = AStacksScreen::Accept->forTheStacksCheck(TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id(), Check::of(THE_CHOICE));

    expect(NativeRouter::resolve($path)['class'] ?? null)->toBe(WhatAcceptingItCosts::class)
        ->and(theAcceptingScreen(AStackThatWasAsked::saying(aReportWith()), anAcceptanceThatTakes())->render()->name())->toBe('operator::what-accepting-it-costs');
});
