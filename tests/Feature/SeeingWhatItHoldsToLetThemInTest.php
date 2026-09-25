<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACredentialHeld;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\WhatTheStoreProtects;
use Modules\Kernel\Api\WhatUsesIt;
use Modules\Kernel\Api\WhereACredentialStands;
use Modules\Kernel\Api\WhoMadeACredential;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatItHoldsToLetThemIn;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatHoldsCredentials;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// The credentials a machine holds to let services in, where each stands and what uses it.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine this screen is about. */
function theStackWhoseCredentialsAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What the store protects against, as every reading here says it. */
function howTheCredentialsAreKept(): WhatTheStoreProtects
{
    return WhatTheStoreProtects::said('Plain files readable only by you', Remarks::of('Another account on this machine'), Remarks::of('Anyone signed in as you'));
}

/** One credential in each of the three troubled states, and one nothing uses. */
function credentialsInTrouble(): TheCredentialsHeld
{
    return TheCredentialsHeld::of(
        howTheCredentialsAreKept(),
        ACredentialHeld::described('Indexer API key', WhereACredentialStands::Stale, WhoMadeACredential::Service, WhatUsesIt::of('sonarr', 'bazarr'), ''),
        ACredentialHeld::described('Usenet password', WhereACredentialStands::Invalid, WhoMadeACredential::Operator, WhatUsesIt::of('sabnzbd'), 'The provider refused it on Tuesday'),
        ACredentialHeld::described('Torrent client password', WhereACredentialStands::Rotating, WhoMadeACredential::Lemonfiber, WhatUsesIt::of('qbittorrent'), ''),
        ACredentialHeld::described('Old tracker key', WhereACredentialStands::Superseded, WhoMadeACredential::Operator, WhatUsesIt::of(), ''),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theCredentialsScreen(
    AStackThatHoldsCredentials $safekeeping,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatItHoldsToLetThemIn {
    $stack = theStackWhoseCredentialsAreRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatItHoldsToLetThemIn($safekeeping, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('draws stale, invalid and rotating each in words of its own', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCredentialsScreen(AStackThatHoldsCredentials::holding(credentialsInTrouble())))->said();

    expect($drawn)->toContain('Indexer API key')
        ->and($drawn)->toContain(__(WhereACredentialStands::Stale->saidOnTheScreen()))
        ->and($drawn)->toContain(__(WhereACredentialStands::Invalid->saidOnTheScreen()))
        ->and($drawn)->toContain(__(WhereACredentialStands::Rotating->saidOnTheScreen()))
        ->and($drawn)->toContain(__(WhereACredentialStands::Superseded->saidOnTheScreen()))
        ->and(__(WhereACredentialStands::Stale->saidOnTheScreen()))->not->toBe(__(WhereACredentialStands::Invalid->saidOnTheScreen()))
        ->and(__(WhereACredentialStands::Invalid->saidOnTheScreen()))->not->toBe(__(WhereACredentialStands::Rotating->saidOnTheScreen()))
        ->and(__(WhereACredentialStands::Rotating->saidOnTheScreen()))->not->toBe(__(WhereACredentialStands::Stale->saidOnTheScreen()));
});

it('names what uses each credential, and says so where nothing does', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCredentialsScreen(AStackThatHoldsCredentials::holding(credentialsInTrouble())))->said();

    expect($drawn)->toContain('sonarr')
        ->and($drawn)->toContain('bazarr')
        ->and($drawn)->toContain('sabnzbd')
        ->and($drawn)->toContain(__('stacks.credentials.used_by'))
        ->and($drawn)->toContain(__('stacks.credentials.used_by_nothing'));
});

it('says who made each credential, and what the stack advises about it', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCredentialsScreen(AStackThatHoldsCredentials::holding(credentialsInTrouble())))->said();

    expect($drawn)->toContain(__(WhoMadeACredential::Service->saidOnTheScreen()))
        ->and($drawn)->toContain(__(WhoMadeACredential::Operator->saidOnTheScreen()))
        ->and($drawn)->toContain(__(WhoMadeACredential::Lemonfiber->saidOnTheScreen()))
        ->and($drawn)->toContain('The provider refused it on Tuesday');
});

it('says what the store protects against, and what it does not', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCredentialsScreen(AStackThatHoldsCredentials::holding(credentialsInTrouble())))->said();

    expect($drawn)->toContain('Plain files readable only by you')
        ->and($drawn)->toContain(__('stacks.credentials.protection.against'))
        ->and($drawn)->toContain('Another account on this machine')
        ->and($drawn)->toContain(__('stacks.credentials.protection.not_against'))
        ->and($drawn)->toContain('Anyone signed in as you');
});

it('offers nothing that would set, change or show a value', function (): void {
    $screen = theCredentialsScreen(AStackThatHoldsCredentials::holding(credentialsInTrouble()));

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([__('health.ask_again')])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.credentials.at_the_machine'));
});

it('a stack holding no credentials says there are none', function (): void {
    $none = TheCredentialsHeld::of(WhatTheStoreProtects::said('Plain files readable only by you', Remarks::of(), Remarks::of()));
    $drawn = WhatTheDeviceWouldDraw::by(theCredentialsScreen(AStackThatHoldsCredentials::holding($none)))->said();

    expect($drawn)->toContain(__('stacks.credentials.none'))
        ->and($drawn)->toContain(__('stacks.credentials.protection.nothing_listed'));
});

it('a stack that could not be asked is not a stack holding none', function (): void {
    $screen = theCredentialsScreen(AStackThatHoldsCredentials::met(Obstacle::StackDidNotAnswer));
    $answer = $screen->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->went->isSignedIn)->toBeTrue()
        ->and([$answer->held, $answer->summary, $answer->against, $answer->notAgainst])->toBe([[], '', [], []])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('stacks.credentials.none'));
});

it('a session that has ended is not a stack holding none', function (): void {
    $safekeeping = AStackThatHoldsCredentials::holding(credentialsInTrouble());
    $answer = theCredentialsScreen($safekeeping, signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->held)->toBe([])
        ->and($safekeeping->askings())->toBe(0);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theCredentialsScreen(AStackThatHoldsCredentials::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseCredentialsAreRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    $safekeeping = AStackThatHoldsCredentials::holding(credentialsInTrouble());
    $screen = theCredentialsScreen($safekeeping);

    $screen->answer();
    $screen->answer();

    expect($safekeeping->askings())->toBe(1)
        ->and($safekeeping->wasGivenASession())->toBeTrue()
        ->and($safekeeping->askedAbout()?->id()->stored())->toBe(theStackWhoseCredentialsAreRead()->id()->stored());
});

it('asking again asks the machine again', function (): void {
    $safekeeping = AStackThatHoldsCredentials::met(Obstacle::DeviceHasNoNetwork);
    $screen = theCredentialsScreen($safekeeping);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($safekeeping->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theCredentialsScreen(AStackThatHoldsCredentials::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way here and the way back are routes', function (): void {
    $screen = theCredentialsScreen(AStackThatHoldsCredentials::holding(credentialsInTrouble()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->whoGetsIn()->credentials()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theCredentialsScreen(AStackThatHoldsCredentials::holding(credentialsInTrouble()))->render()->name())->toBe('operator::what-it-holds-to-let-them-in');
});
