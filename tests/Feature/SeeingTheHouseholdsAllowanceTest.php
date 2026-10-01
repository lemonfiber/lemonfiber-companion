<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatTheHouseholdIsAllowed;
use Modules\Operator\Internal\TheMenu;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

function theStackWhoseAllowanceIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The cellar'),
        Address::of('https://192.168.1.91'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theAllowanceScreen(AMemberWhoIsOwed $owing, ?AKeychainInMemory $keychain = null, bool $signedIn = true): WhatTheHouseholdIsAllowed
{
    $stack = theStackWhoseAllowanceIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatTheHouseholdIsAllowed($owing, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('draws what the core says the household may ask for, word for word and in its order', function (): void {
    $said = Sentences::of(
        Sentence::of('Anna can ask for two films a month; one is left.'),
        Sentence::of('Everything Ben asks for waits for approval.'),
    );
    $drawn = WhatTheDeviceWouldDraw::by(theAllowanceScreen(AMemberWhoIsOwed::owed($said)));

    expect($drawn->said())->toContain('Anna can ask for two films a month; one is left.')
        ->and($drawn->said())->toContain('Everything Ben asks for waits for approval.')
        ->and($drawn->offers())->toBe([__('health.ask_again')]);
});

it('says there is nothing to tell where the core said nothing', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theAllowanceScreen(AMemberWhoIsOwed::owedNothing()));

    expect($drawn->said())->toContain(__('household.nothing_owed'))
        ->and($drawn->said())->toContain(__('household.nothing_owed_action'));
});

it('says what stopped the reading, and asks again from nothing', function (): void {
    $screen = theAllowanceScreen(AMemberWhoIsOwed::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));

    $screen->again();

    expect($screen->answered)->toBeNull();
});

it('offers signing in where no session is held, and lets go of one the stack refused', function (): void {
    expect(theAllowanceScreen(AMemberWhoIsOwed::owedNothing(), signedIn: false)->answer()->went->isSignedIn)->toBeFalse();

    $keychain = AKeychainInMemory::working();
    $screen = theAllowanceScreen(AMemberWhoIsOwed::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseAllowanceIsRead()->id()))->toBeFalse();
});

it('is where the menu\'s Allowance goes, and carries the menu', function (): void {
    $screen = theAllowanceScreen(AMemberWhoIsOwed::owedNothing());
    $path = TheMenu::Allowance->screen()->forTheStack(theStackWhoseAllowanceIsRead()->id());

    expect(NativeRouter::resolve($path)['class'] ?? null)->toBe(WhatTheHouseholdIsAllowed::class)
        ->and($path)->toBe(AStacksScreen::Allowance->forTheStack(theStackWhoseAllowanceIsRead()->id()))
        ->and($screen->drawerOverride()->isBesideBack())->toBeTrue()
        ->and($screen->render()->name())->toBe('operator::what-the-household-is-allowed');
});
