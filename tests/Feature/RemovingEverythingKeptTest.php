<?php

declare(strict_types=1);

use Modules\Kernel\Api\APathLeft;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomethingKept;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\TheForgetting;
use Modules\Kernel\Api\ThePlacesGone;
use Modules\Kernel\Api\TheRoots;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatIsBeside;
use Modules\Kernel\Api\WhatIsKept;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Kernel\Api\WhereForgettingGot;
use Modules\Kernel\Api\WhereThingsAreKept;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatThisMachineKeepsHere;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatForgets;
use Tests\Support\Fakes\AStackThatListsItsCopies;
use Tests\Support\Fakes\AStackThatSaysWhatItKeeps;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\WhatTheDeviceWouldDraw;

function whatWouldGoFromTheLoft(): WhatThisMachineKeeps
{
    return WhatThisMachineKeeps::of(
        TheRoots::of(WhereThingsAreKept::at('/srv/lemonfiber', 'Everything the stack writes')),
        WhatIsKept::of(SomethingKept::kept('The VPN credentials', '/srv/lemonfiber/secrets/vpn', 'So the tunnel can be raised', WhetherItHoldsASecret::said(secret: true))),
        WhatIsBeside::of(),
    );
}

/**
 * @param WhatBecameOfTheWork<TheForgetting>|null $removed
 */
function aStackThatListsThenRemoves(?WhatBecameOfTheWork $removed = null): AStackThatForgets
{
    return AStackThatForgets::answering(
        WhatBecameOfTheWork::answered(TheForgetting::said(whatWouldGoFromTheLoft(), WhereForgettingGot::unconfirmed())),
        $removed ?? WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)),
    );
}

function theScreenThatForgets(AStackThatForgets $forgetting, ?AKeychainInMemory $keychain = null): WhatThisMachineKeepsHere
{
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain ??= AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new WhatThisMachineKeepsHere(
        AStackThatSaysWhatItKeeps::with(whatWouldGoFromTheLoft()),
        AStackThatListsItsCopies::with(TheCopies::named()),
        $forgetting,
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

function theListShownToForget(AStackThatForgets $forgetting, ?AKeychainInMemory $keychain = null): WhatThisMachineKeepsHere
{
    $screen = theScreenThatForgets($forgetting, $keychain);
    $screen->whatWouldGo();
    $screen->whileForgettingRuns();
    $screen->howForgettingIsGoing();

    return $screen;
}

it('says what is not touched and that this app\'s session ends, before anything is asked', function (): void {
    $forgetting = aStackThatListsThenRemoves();
    $drawn = WhatTheDeviceWouldDraw::by(theScreenThatForgets($forgetting));

    expect($drawn->said())->toContain(__('stacks.forget.title'), __('stacks.forget.untouched'), __('stacks.forget.ends_this_session'))
        ->and($drawn->offers())->toContain(__('stacks.forget.what_would_go'))
        ->and($drawn->offers())->not->toContain(__('stacks.forget.forget_it'))
        ->and($forgetting->askedWhatWouldGo())->toBe(0);
});

it('asks what would go, follows the work, and draws the stack\'s list with the yes beneath it', function (): void {
    $forgetting = aStackThatListsThenRemoves();
    $screen = theScreenThatForgets($forgetting);
    $screen->whatWouldGo();

    expect($screen->awaitsAnOutcome())->toBeTrue();

    $screen->whileForgettingRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->awaitsAnOutcome())->toBeFalse()
        ->and($drawn->said())->toContain(__('stacks.forget.would_go'), 'Everything the stack writes', '/srv/lemonfiber', 'The VPN credentials', __('stacks.forget.untouched'), __('stacks.forget.ends_this_session'))
        ->and($drawn->offers())->toContain(__('stacks.forget.forget_it'))
        ->and($forgetting->agreed())->toBe([]);
});

it('sends the yes against the list shown, and says the way in went where the answer cannot be read after it', function (): void {
    $keychain = AKeychainInMemory::working();
    $forgetting = aStackThatListsThenRemoves();
    $screen = theListShownToForget($forgetting, $keychain);
    $screen->forgetEverything();

    expect($forgetting->agreed())->toHaveCount(1)
        ->and($forgetting->agreed()[0]->shown()->listed())->toEqual(whatWouldGoFromTheLoft());

    $screen->whileForgettingRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('stacks.forget.way_in_went'))
        ->and($drawn->said())->not->toContain(__(KindOfObstacle::CredentialWasRefused->said()))
        ->and($drawn->offers())->not->toContain(__('stacks.forget.forget_it'), __('stacks.forget.what_would_go'))
        ->and($keychain->isHolding(TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id()))->toBeFalse();
});

it('sends no yes without the list on the screen', function (): void {
    $forgetting = aStackThatListsThenRemoves();
    theScreenThatForgets($forgetting)->forgetEverything();

    $notAListing = AStackThatForgets::answering(WhatBecameOfTheWork::answered(TheForgetting::said(whatWouldGoFromTheLoft(), WhereForgettingGot::notAsked())), WhatBecameOfTheWork::ended());
    $screen = theListShownToForget($notAListing);
    $screen->forgetEverything();

    expect($forgetting->agreed())->toBe([])
        ->and($notAListing->agreed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('stacks.forget.forget_it'));
});

it('draws what went and what was left, where the stack could still say', function (): void {
    $done = WhatBecameOfTheWork::answered(TheForgetting::said(whatWouldGoFromTheLoft(), WhereForgettingGot::done(ThePlacesGone::of('/home/op/.config/lemonfiber'), APathLeft::at('/home/op/.local/share/lemonfiber', 'permission denied'))));
    $screen = theListShownToForget(aStackThatListsThenRemoves($done));
    $screen->forgetEverything();
    $screen->whileForgettingRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(
        __('stacks.forget.done'),
        '/home/op/.config/lemonfiber',
        __('stacks.forget.left', ['where' => '/home/op/.local/share/lemonfiber']),
        'permission denied',
    );
});

it('says the session ended where it went while what would go was asked', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theScreenThatForgets(aStackThatListsThenRemoves(), $keychain);
    $screen->whatWouldGo();
    $keychain->forget(TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id());
    $screen->whileForgettingRuns();

    expect($screen->howForgettingIsGoing()->went->isSignedIn)->toBeFalse()
        ->and($screen->awaitsAnOutcome())->toBeFalse();
});

it('draws a refusal in the stack\'s own words, an outcome the stack no longer has, and what stood in the way before a yes', function (): void {
    $why = ARefusalInItsWords::said('this run cannot say where lemonfiber keeps its own files', '', WhatTheRefusalNamed::nothing(), 'Run this as the account that installed lemonfiber');
    $refused = theListShownToForget(AStackThatForgets::answering(WhatBecameOfTheWork::refused($why), WhatBecameOfTheWork::ended()));
    $ended = theListShownToForget(AStackThatForgets::answering(WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended()));
    $met = theScreenThatForgets(AStackThatForgets::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $met->whatWouldGo();

    expect(WhatTheDeviceWouldDraw::by($refused)->said())->toContain(__('stacks.forget.refused'), 'this run cannot say where lemonfiber keeps its own files')
        ->and(WhatTheDeviceWouldDraw::by($ended)->said())->toContain(__('stacks.forget.no_outcome'))
        ->and(WhatTheDeviceWouldDraw::by($met)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));
});
