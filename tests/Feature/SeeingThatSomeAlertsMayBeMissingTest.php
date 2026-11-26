<?php

declare(strict_types=1);

use Modules\Design\View\Tone;
use Modules\Health\Api\AlertsThatMayBeMissing;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAlert;
use Modules\Kernel\Api\AnAlertsName;
use Modules\Kernel\Api\AnAlertsSort;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheAlerts;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhichWayItWent;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Modules\Wayfinding\Api\HearingEachStack;
use Modules\Wayfinding\Api\WhatEachStackSaidSoFar;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AJobThatSays;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\ANotifierInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\AStackThatSaysHowItRunsWithout;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\AStackThatWouldMend;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\MissingAlertsInMemory;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// A stack's health screen saying that some alerts raised while the app was
// away may be missing, after a wake marked it, until the operator opens it.

/** The moment the screens here are opened at. Named for this file (`G10`). */
const WHEN_THE_MARKED_STACK_WAS_OPENED = 1_790_000_000;

/** The stack a wake may have marked. */
function theStackAWakeMayHaveMarked(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The cellar'),
        Address::of('https://192.168.1.46:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** What remembers which stacks may be missing alerts, with the cellar marked where a case says. */
function whatRemembersTheCellar(bool $marked): AlertsThatMayBeMissing
{
    $missing = new AlertsThatMayBeMissing(ASealInMemory::working(), MissingAlertsInMemory::empty());

    if ($marked) {
        $missing->mayBeMissingFrom(theStackAWakeMayHaveMarked()->id(), Instant::atEpochSeconds(WHEN_THE_MARKED_STACK_WAS_OPENED - 60));
    }

    return $missing;
}

/** What a stream resumed in front of the operator hears from a stack too old to say whether every alert came back. */
function aResumedStreamThatSaysNothingOfItsReplay(): AStackThatSpeaksUp
{
    return AStackThatSpeaksUp::holdingOpen(
        WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()))
            ->alerting(TheAlerts::of(AnAlert::raised(WhatTheCoreDecided::toSay('disk.full'), WhichWayItWent::Onset, AnAlertsName::of('disk.full#1'), AnAlertsSort::of(KindOfNotification::Problem, Severity::Warning))))
            ->missingSomeAlerts(),
    );
}

/** The cellar's health screen, opened over what remembers the marks, the stack answering as `$asking` does and its stream as `$stream` does. */
function theCellarOpened(AlertsThatMayBeMissing $missing, ?Asking $asking = null, ?AStackThatSpeaksUp $stream = null, ?ANotifierInMemory $notifier = null): HowThisStackIs
{
    $stack = theStackAWakeMayHaveMarked();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $screen = new HowThisStackIs(
        $asking ?? AStackThatWasAsked::saying(Report::of(Overall::Healthy, Findings::of())),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(
            $stream ?? AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()))),
            FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_MARKED_STACK_WAS_OPENED)),
            notifier: $notifier,
            missing: $missing,
        ),
        AStackThatSaysHowItRunsWithout::bundled(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);
    $screen->mount();

    return $screen;
}

it('says some alerts may be missing on the stack a wake marked, for as long as the screen is open', function (): void {
    $screen = theCellarOpened(whatRemembersTheCellar(marked: true));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.alerts_may_be_missing'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.alerts_may_be_missing'));
});

it('clears the mark by opening the screen, so the next opening says nothing of it', function (): void {
    $missing = whatRemembersTheCellar(marked: true);
    theCellarOpened($missing);

    expect($missing->keepsAnythingOf(theStackAWakeMayHaveMarked()->id()))->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by(theCellarOpened($missing))->said())->not->toContain(__('health.alerts_may_be_missing'));
});

it('says nothing of missing alerts on a stack no wake marked', function (): void {
    expect(WhatTheDeviceWouldDraw::by(theCellarOpened(whatRemembersTheCellar(marked: false)))->said())->not->toContain(__('health.alerts_may_be_missing'));
});

it('says some alerts may be missing beside what stopped the screen reading the stack', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCellarOpened(whatRemembersTheCellar(marked: true), AStackThatWasAsked::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->said();

    expect($drawn)->toContain(__('health.alerts_may_be_missing'))
        ->and($drawn)->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));
});

it('says it at once on the open screen whose stream resumes without vouching for every alert, telling only the alerts it handed back', function (): void {
    $missing = whatRemembersTheCellar(marked: false);
    $notifier = ANotifierInMemory::allowed();
    $screen = theCellarOpened($missing, stream: aResumedStreamThatSaysNothingOfItsReplay(), notifier: $notifier);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.alerts_may_be_missing'))
        ->and($screen->someAlertsMayBeMissing)->toBeTrue()
        ->and($missing->keepsAnythingOf(theStackAWakeMayHaveMarked()->id()))->toBeFalse()
        ->and($notifier->told())->toHaveCount(1)
        ->and($notifier->told()[0])->toContain('disk.full');
});

it('says nothing of missing alerts on the open screen whose stream resumes vouching for every one', function (): void {
    $missing = whatRemembersTheCellar(marked: false);
    $screen = theCellarOpened($missing, stream: AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()))));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('health.alerts_may_be_missing'))
        ->and($missing->keepsAnythingOf(theStackAWakeMayHaveMarked()->id()))->toBeFalse();
});

it('draws the line calm, as a note about what the phone heard rather than about the stack', function (): void {
    $notice = WhatTheDeviceWouldDraw::tree(theCellarOpened(whatRemembersTheCellar(marked: true)));

    expect((string) json_encode($notice))->toContain((string) json_encode(Tone::Quiet->glyph()))
        ->and((string) json_encode($notice))->not->toContain((string) json_encode(Tone::Attention->glyph()));
});

it('marks the stack without telling of it where the list of stacks resumes its stream without vouching for every alert', function (): void {
    $stack = theStackAWakeMayHaveMarked();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $missing = whatRemembersTheCellar(marked: false);
    $notifier = ANotifierInMemory::allowed();

    new HearingEachStack(AroundThePhone::listening(aResumedStreamThatSaysNothingOfItsReplay(), FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_MARKED_STACK_WAS_OPENED)), notifier: $notifier, missing: $missing), $keychain)
        ->after(WhatEachStackSaidSoFar::nothingYet(), $stack);

    expect($missing->keepsAnythingOf($stack->id()))->toBeTrue()
        ->and($notifier->told())->toHaveCount(1)
        ->and($notifier->told()[0])->toContain('disk.full');
});

it('marks the stack from another of its screens whose stream resumes without vouching for every alert, saying nothing of it there', function (): void {
    $stack = theStackAWakeMayHaveMarked();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $missing = whatRemembersTheCellar(marked: false);
    $notifier = ANotifierInMemory::allowed();
    $listening = AroundThePhone::listening(aResumedStreamThatSaysNothingOfItsReplay(), FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_MARKED_STACK_WAS_OPENED)), notifier: $notifier, missing: $missing);
    $screen = new WhatWouldBePutRight(AStackThatWouldMend::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), $listening, AJobThatSays::nothing());
    $screen->setParams(['stack' => $stack->id()->stored()]);
    $screen->listen();

    expect($missing->keepsAnythingOf($stack->id()))->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('health.alerts_may_be_missing'))
        ->and($notifier->told())->toHaveCount(1)
        ->and(WhatTheDeviceWouldDraw::by(theCellarOpened($missing))->said())->toContain(__('health.alerts_may_be_missing'));
});
