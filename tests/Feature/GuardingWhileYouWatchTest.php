<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AGuardAskedFor;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\HowTheGuardIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\WhatTheGuardSaw;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\GuardingWhileYouWatch;
use Modules\Operator\Internal\ViewModels\HowTheGuardWent;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatGuards;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatTheDeviceWouldDraw;

// A guard on the data location, held while its screen asks: naming the
// forms, the question before it starts, the guard guarding, the four ways it
// ends, and letting it go when the screen is left.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.

/**
 * One line of the catalogue, as text, for finding it among what a screen drew.
 *
 * @param array<string, string> $with
 */
function aLineOfTheGuard(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

/** The machine a guard is started on. */
function theStackAGuardWatches(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The screen, with a stack it knows, declaring `library` and `full`, and a keychain holding whatever a case says. */
function theGuardScreen(
    AStackThatGuards $guarding,
    ?Supervising $supervising = null,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): GuardingWhileYouWatch {
    $stack = theStackAGuardWatches();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new GuardingWhileYouWatch($guarding, $supervising ?? AStackThatSupervises::with(WhatAMachineRuns::twoThings()), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A screen that has named both forms and agreed to guard them. */
function aGuardAgreedTo(AStackThatGuards $guarding, ?AKeychainInMemory $keychain = null): GuardingWhileYouWatch
{
    $screen = theGuardScreen($guarding, keychain: $keychain);
    $screen->choose('library');
    $screen->choose('full');
    $screen->wouldGuard();
    $screen->agree();

    return $screen;
}

/** What a guard saw once the data location went, stopping the forms or not. */
function whatAGuardSaw(bool $stopped): WhatTheGuardSaw
{
    return WhatTheGuardSaw::of(Forms::these(Form::called('library'), Form::called('full')), $stopped, 'The data root at /srv/media is gone.');
}

/** Ask after the guard the way the cadence does, and hand back what the frame then says. */
function theGuardAskedAfter(GuardingWhileYouWatch $screen): HowTheGuardWent
{
    $screen->whileItGuards();

    return $screen->lastGuard();
}

/**
 * The forms a guard was asked for, by name.
 *
 * @param list<AGuardAskedFor> $started
 * @return list<list<string>>
 */
function theFormsEachGuardWasFor(array $started): array
{
    return array_map(
        static fn(AGuardAskedFor $asked): array => array_map(static fn(Form $form): string => $form->named(), [...$asked->forms()]),
        $started,
    );
}

it('says, before any guard starts, that it lives only while this screen asks and is not hosted, and what one does', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theGuardScreen(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding())));

    expect($drawn->said())->toContain(aLineOfTheGuard('stacks.guard.lives_while_asked'))
        ->and($drawn->said())->toContain(aLineOfTheGuard('stacks.guard.not_hosted'))
        ->and($drawn->said())->toContain(aLineOfTheGuard('stacks.guard.would_do'))
        ->and($drawn->said())->toContain(aLineOfTheGuard('stacks.guard.not_said_before'))
        ->and($drawn->offers())->toContain(aLineOfTheGuard('stacks.guard.to_host_one'));
});

it('offers each form the stack declares to be named, and starts nothing until a guard is agreed to', function (): void {
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding());
    $screen = theGuardScreen($guarding);

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([
        aLineOfTheGuard('stacks.guard.to_host_one'),
        aLineOfTheGuard('stacks.guard.name', ['form' => 'library']),
        aLineOfTheGuard('stacks.guard.name', ['form' => 'full']),
        aLineOfTheGuard('health.ask_again'),
    ]);

    $screen->choose('library');
    $screen->wouldGuard();

    expect($guarding->started())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineOfTheGuard('stacks.guard.about_to', ['forms' => 'library']));
});

it('names a form and takes it back out, and names nothing the stack does not declare', function (): void {
    $screen = theGuardScreen(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding()));

    $screen->choose('full');
    $screen->choose('library');
    $screen->choose('nothing-by-that-name');

    expect($screen->naming)->toBe(['full', 'library'])
        ->and($screen->choosing()->named)->toBe('library, full')
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(aLineOfTheGuard('stacks.guard.start'));

    $screen->wouldGuard();

    expect(array_map(static fn(Form $form): string => $form->named(), [...$screen->asking?->forms() ?? Forms::none()]))->toBe(['library', 'full']);

    $screen->choose('full');

    expect($screen->naming)->toBe(['library']);
});

it('asks about no guard while no form is named', function (): void {
    $screen = theGuardScreen(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding()));

    $screen->wouldGuard();

    expect($screen->asking)->toBeNull()
        ->and($screen->choosing()->canStart)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(aLineOfTheGuard('stacks.guard.start'));
});

it('asks before it starts, naming the forms it would stop, and never mind starts nothing', function (): void {
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding());
    $screen = theGuardScreen($guarding);
    $screen->choose('library');
    $screen->choose('full');
    $screen->wouldGuard();

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(aLineOfTheGuard('stacks.guard.about_to', ['forms' => 'library, full']))
        ->and($drawn->said())->toContain(aLineOfTheGuard('stacks.guard.lives_while_asked'))
        ->and($drawn->offers())->toContain(aLineOfTheGuard('health.go_ahead'));

    $screen->neverMind();
    $screen->agree();

    expect($guarding->started())->toBe([])
        ->and($screen->asking)->toBeNull();
});

it('starts the guard for the forms named on a yes, and says it guards them while this screen asks', function (): void {
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding());
    $screen = aGuardAgreedTo($guarding);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(theFormsEachGuardWasFor($guarding->started()))->toBe([['library', 'full']])
        ->and($screen->took)->toBe(AStackThatGuards::THE_JOB)
        ->and($drawn->said())->toContain(aLineOfTheGuard('stacks.guard.guarding', ['forms' => 'library, full']))
        ->and($drawn->said())->toContain(aLineOfTheGuard('stacks.guard.lives_while_asked'))
        ->and($drawn->said())->toContain(aLineOfTheGuard('stacks.guard.not_hosted'))
        ->and($guarding->followed())->toBe([]);
});

it('asks after the guard on the declared cadence while it guards, by the name it was started under', function (): void {
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding());
    $screen = aGuardAgreedTo($guarding);

    theGuardAskedAfter($screen);
    theGuardAskedAfter($screen);

    $poll = new ReflectionMethod(GuardingWhileYouWatch::class, 'whileItGuards')->getAttributes(Poll::class)[0]->getArguments();

    expect($guarding->followed())->toHaveCount(2)
        ->and(array_map(static fn(Job $job): string => $job->shown(), $guarding->followed()))->toBe([AStackThatGuards::THE_JOB, AStackThatGuards::THE_JOB])
        ->and($poll)->toBe([HowOftenAScreenLooks::WhileWorkRuns->milliseconds()]);
});

it('a guard that saw the data location go and stopped the forms says so, with why it ended and each form', function (): void {
    $screen = aGuardAgreedTo(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::sawItGo(whatAGuardSaw(stopped: true))));
    theGuardAskedAfter($screen);
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(aLineOfTheGuard('stacks.guard.saw_it_go'))
        ->and($said)->toContain('The data root at /srv/media is gone.')
        ->and($said)->toContain(aLineOfTheGuard('stacks.guard.stopped_them'))
        ->and($said)->toContain('library')
        ->and($said)->toContain('full')
        ->and($said)->not->toContain(aLineOfTheGuard('stacks.guard.did_not_stop_them'))
        ->and($said)->not->toContain(aLineOfTheGuard('stacks.guard.guarding', ['forms' => 'library, full']));
});

it('a guard that could not stop the forms is never shown as having stopped them', function (): void {
    $screen = aGuardAgreedTo(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::sawItGo(whatAGuardSaw(stopped: false))));
    theGuardAskedAfter($screen);
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(aLineOfTheGuard('stacks.guard.did_not_stop_them'))
        ->and($said)->not->toContain(aLineOfTheGuard('stacks.guard.stopped_them'));
});

it('a guard that saw the data location go and named no forms says so', function (): void {
    $saw = WhatTheGuardSaw::of(Forms::none(), stopped: true, reason: 'A different volume is mounted at /srv/media.');
    $screen = aGuardAgreedTo(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::sawItGo($saw)));
    theGuardAskedAfter($screen);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineOfTheGuard('stacks.guard.named_no_forms'));
});

it('a guard that never started says so with the stack\'s reason', function (): void {
    $screen = aGuardAgreedTo(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::refused('No data location is configured, so there is nothing to guard.')));
    theGuardAskedAfter($screen);
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(aLineOfTheGuard('stacks.guard.did_not_start'))
        ->and($said)->toContain('No data location is configured, so there is nothing to guard.')
        ->and($said)->not->toContain(aLineOfTheGuard('stacks.guard.stopped_them'))
        ->and($said)->not->toContain(aLineOfTheGuard('stacks.guard.did_not_stop_them'));
});

it('a guard let go is told apart from one that saw the data location go and from one still guarding', function (): void {
    $screen = aGuardAgreedTo(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::ended()));
    $went = theGuardAskedAfter($screen);
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($went->isGuarding)->toBeFalse()
        ->and($said)->toContain(aLineOfTheGuard('stacks.guard.let_go'))
        ->and($said)->not->toContain(aLineOfTheGuard('stacks.guard.saw_it_go'))
        ->and($said)->not->toContain(aLineOfTheGuard('stacks.guard.guarding', ['forms' => 'library, full']));
});

it('a guard the stack no longer knows is told apart from one let go', function (): void {
    $screen = aGuardAgreedTo(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::unknown()));
    theGuardAskedAfter($screen);
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(aLineOfTheGuard('stacks.guard.unknown'))
        ->and($said)->not->toContain(aLineOfTheGuard('stacks.guard.let_go'));
});

it('asks after a guard that has ended no more, however often the cadence comes round', function (): void {
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::ended());
    $screen = aGuardAgreedTo($guarding);

    theGuardAskedAfter($screen);
    theGuardAskedAfter($screen);
    theGuardAskedAfter($screen);

    expect($guarding->followed())->toHaveCount(1);
});

it('lets the guard go by its name when the screen is left', function (): void {
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding());
    $screen = aGuardAgreedTo($guarding);
    theGuardAskedAfter($screen);

    $screen->unmount();

    expect(array_map(static fn(Job $job): string => $job->shown(), $guarding->letGoOf()))->toBe([AStackThatGuards::THE_JOB]);
});

it('lets nothing go when the screen is left before a guard started, or after it ended', function (): void {
    $never = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding());
    theGuardScreen($never)->unmount();

    $ended = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::ended());
    $screen = aGuardAgreedTo($ended);
    theGuardAskedAfter($screen);
    $screen->unmount();

    expect($never->letGoOf())->toBe([])
        ->and($ended->letGoOf())->toBe([]);
});

it('lets the guard go when the screen is left on a frame that has not asked after it yet', function (): void {
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding());
    $screen = aGuardAgreedTo($guarding);
    $screen->lastGuard = null;

    $screen->unmount();

    expect(array_map(static fn(Job $job): string => $job->shown(), $guarding->letGoOf()))->toBe([AStackThatGuards::THE_JOB]);
});

it('leaving the screen runs what the framework tears down on the way out, too', function (): void {
    $screen = theGuardScreen(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding()));
    $torn = [];
    $screen->registerCleanup(static function () use (&$torn): void {
        $torn[] = 'down';
    });

    $screen->unmount();

    expect($torn)->toBe(['down']);
});

it('a handle held without the guard it was for asks after nothing, and says none was asked for', function (): void {
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding());
    $screen = theGuardScreen($guarding);
    $screen->took = AStackThatGuards::THE_JOB;

    expect($screen->lastGuard()->wasAsked)->toBeFalse()
        ->and($guarding->followed())->toBe([]);
});

it('asks for the forms once a frame, however often the frame reads them', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theGuardScreen(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding()), $supervising);
    $screen->answer();
    $screen->answer();
    $screen->choosing();

    expect($supervising->askings())->toBe(1);
});

it('lets go through nothing when the session was let go before the screen was left', function (): void {
    $keychain = AKeychainInMemory::working();
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding());
    $screen = aGuardAgreedTo($guarding, $keychain);
    $keychain->forget(theStackAGuardWatches()->id());

    $screen->unmount();

    expect($guarding->letGoOf())->toBe([]);
});

it('a guard the stack could not be asked to start says what stood in the way, and offers asking again', function (): void {
    $screen = aGuardAgreedTo(AStackThatGuards::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->lastGuard()->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($drawn->said())->toContain(aLineOfTheGuard(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($drawn->offers())->toContain(aLineOfTheGuard('health.ask_again'));
});

it('a credential the stack refused while guarding signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = aGuardAgreedTo(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))), $keychain);

    expect(theGuardAskedAfter($screen)->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackAGuardWatches()->id()))->toBeFalse();
});

it('a session that ended before starting or while following says so, and asks nothing', function (): void {
    $keychain = AKeychainInMemory::working();
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding());
    $screen = theGuardScreen($guarding, keychain: $keychain);
    $screen->choose('library');
    $screen->wouldGuard();
    $screen->answer();
    $keychain->forget(theStackAGuardWatches()->id());

    $screen->agree();

    expect($screen->lastGuard()->went->isSignedIn)->toBeFalse()
        ->and($guarding->started())->toBe([]);

    $following = aGuardAgreedTo(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding()), $keychain = AKeychainInMemory::working());
    $keychain->forget(theStackAGuardWatches()->id());

    expect(theGuardAskedAfter($following)->went->isSignedIn)->toBeFalse();
});

it('names forms for another guard once the last has ended, and not while one guards', function (): void {
    $guarding = aGuardAgreedTo(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding()));
    $guarding->startOver();

    expect($guarding->took)->toBe(AStackThatGuards::THE_JOB);

    $ended = aGuardAgreedTo(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::ended()));
    theGuardAskedAfter($ended);

    expect(WhatTheDeviceWouldDraw::by($ended)->offers())->toContain(aLineOfTheGuard('stacks.guard.start_over'));

    $ended->startOver();

    expect([$ended->took, $ended->guarding, $ended->naming])->toBe([null, null, []])
        ->and($ended->lastGuard()->wasAsked)->toBeFalse();
});

it('asking again asks for the forms and for where the guard stands', function (): void {
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), HowTheGuardIsGoing::stillGuarding());
    $screen = aGuardAgreedTo($guarding);
    theGuardAskedAfter($screen);

    $screen->again();

    expect($screen->lastGuard()->isGuarding)->toBeTrue()
        ->and($guarding->followed())->toHaveCount(2);
});

it('forms that could not be listed offer no guard, only asking again', function (): void {
    $screen = theGuardScreen(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding()), AStackThatSupervises::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(aLineOfTheGuard('stacks.guard.name', ['form' => 'library']))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineOfTheGuard(KindOfObstacle::StackDidNotAnswer->said()));
});

it('a stack that declares no forms says so', function (): void {
    $screen = theGuardScreen(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding()), AStackThatSupervises::withNothingRunning());

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineOfTheGuard('stacks.guard.no_forms'));
});

it('the way here, from what keeps running, and the way to hosting one are routes', function (): void {
    $screen = theGuardScreen(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding()));

    expect(NativeRouter::resolve($screen->goes()->ofItself()->guard()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->keepsRunning()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theGuardScreen(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding()))->render()->name())->toBe('operator::guarding-while-you-watch');
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theGuardScreen(AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});
