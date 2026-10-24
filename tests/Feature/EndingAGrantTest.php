<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\ACredentialHeld;
use Modules\Kernel\Api\ACredentialRevoke;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCredentialGrants;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\WhatARevokeCameTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheStoreProtects;
use Modules\Kernel\Api\WhatUsesIt;
use Modules\Kernel\Api\WhereACredentialStands;
use Modules\Kernel\Api\WhoMadeACredential;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Listed;
use Modules\Operator\Internal\Screens\WhatItHoldsToLetThemIn;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatEndsGrants;
use Tests\Support\Fakes\AStackThatHoldsCredentials;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

/** When the grants here were approved. */
const WHEN_THE_GRANT_WAS_APPROVED = 1_761_321_600;

/** The agreement the stack's reading of ending the grant goes by. */
const THE_REVOKE_READ = 'credentials-revoke:tdarr:plex.token:3';

function theMachineWhoseGrantIsEnded(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('h', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

function plexsTokenGrantedToTdarr(): ACredentialGrant
{
    return ACredentialGrant::of(APluginId::called('tdarr'), 'plex.token', ServiceId::called('plex'), 'library-scan', AMomentAsWritten::inSeconds((string) WHEN_THE_GRANT_WAS_APPROVED));
}

/** The Plex token held and granted to Tdarr, and a grant of a credential the list does not hold. */
function thePasswordsWithTheirGrants(ACredentialGrant ...$grants): TheCredentialsHeld
{
    return TheCredentialsHeld::of(
        WhatTheStoreProtects::said('Plain files readable only by you', Remarks::of(), Remarks::of()),
        TheCredentialGrants::of(...$grants),
        ACredentialHeld::described('Plex token', 'plex.token', WhereACredentialStands::Active, WhoMadeACredential::Service, WhatUsesIt::of('plex'), ''),
    );
}

function theGrantingScreen(AStackThatEndsGrants $ending, ?TheCredentialsHeld $held = null, bool $signedIn = true): WhatItHoldsToLetThemIn
{
    $stack = theMachineWhoseGrantIsEnded();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatItHoldsToLetThemIn(
        AStackThatHoldsCredentials::holding($held ?? thePasswordsWithTheirGrants(plexsTokenGrantedToTdarr())),
        $ending,
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_GRANT_WAS_APPROVED + 86_400)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);
    $screen->answer();

    return $screen;
}

/** What the stack answers a reading of ending the grant with. */
function theRevokeAsRead(): WhatARevokeCameTo
{
    return WhatARevokeCameTo::of(ACredentialRevoke::read(THE_REVOKE_READ, plexsTokenGrantedToTdarr(), Services::these(ServiceId::called('tdarr'), ServiceId::called('tdarr-node'))), thePasswordsWithTheirGrants(plexsTokenGrantedToTdarr()));
}

/** What the stack answers the grant ended with: the grant gone, and why its services did not restart. */
function theRevokeAsEnded(): WhatARevokeCameTo
{
    return WhatARevokeCameTo::of(ACredentialRevoke::ended(THE_REVOKE_READ, plexsTokenGrantedToTdarr(), Services::these(ServiceId::called('tdarr')), 'It was stopped'), thePasswordsWithTheirGrants());
}

/** @param array<string, string> $with */
function aLineAboutGrants(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : '';
}

it('lists each grant beside the credential it crosses, and a grant of one the list does not hold apart, never dropped', function (): void {
    $elsewhere = ACredentialGrant::of(APluginId::called('bazarr'), 'jellyfin.key', ServiceId::called('jellyfin'), 'subtitles', AMomentAsWritten::inSeconds(''));
    $drawn = WhatTheDeviceWouldDraw::by(theGrantingScreen(AStackThatEndsGrants::untouched(), thePasswordsWithTheirGrants(plexsTokenGrantedToTdarr(), $elsewhere)));

    expect($drawn->said())->toContain(
        'Plex token',
        aLineAboutGrants('stacks.credentials.grants.granted', ['plugin' => 'tdarr', 'fills' => 'library-scan']),
        aLineAboutGrants('stacks.credentials.grants.elsewhere'),
        aLineAboutGrants('stacks.credentials.grants.of', ['credential' => 'jellyfin.key', 'whose' => 'jellyfin']),
        aLineAboutGrants('stacks.credentials.grants.granted', ['plugin' => 'bazarr', 'fills' => 'subtitles']),
    )->and(array_filter($drawn->said(), static fn(string $line): bool => str_starts_with($line, aLineAboutGrants('stacks.credentials.grants.approved', ['when' => '']))))->toHaveCount(1)
        ->and(array_count_values($drawn->offers())[aLineAboutGrants('stacks.credentials.grants.revoke')] ?? 0)->toBe(2);
});

it('reads what ending a grant would do first, labelled as nothing ended yet, with what restarts and what stays', function (): void {
    $ending = AStackThatEndsGrants::answering(WhatBecameOfTheWork::answered(theRevokeAsRead()), WhatBecameOfTheWork::ended());
    $screen = theGrantingScreen($ending);
    $screen->revoke(0);
    $running = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($running)->toContain(aLineAboutGrants('stacks.credentials.revoke.working'))
        ->and($ending->asked())->toBe(['read:tdarr:plex.token', sprintf('after:%s', AStackThatEndsGrants::THE_READING)])
        ->and($drawn->said())->toContain(
            aLineAboutGrants('stacks.credentials.revoke.reading'),
            aLineAboutGrants('stacks.credentials.revoke.grant', ['credential' => 'plex.token', 'whose' => 'plex', 'plugin' => 'tdarr', 'fills' => 'library-scan']),
            aLineAboutGrants('stacks.credentials.revoke.restarts', ['services' => Listed::these('tdarr', 'tdarr-node')]),
            aLineAboutGrants('stacks.credentials.revoke.stays', ['plugin' => 'tdarr', 'fills' => 'library-scan']),
            aLineAboutGrants('stacks.credentials.revoke.rotate'),
        )
        ->and($drawn->said())->not->toContain(aLineAboutGrants('stacks.credentials.revoke.ended'))
        ->and($drawn->offers())->toContain(aLineAboutGrants('stacks.credentials.revoke.agree'));
});

it('ends the grant on a yes carrying the reading\'s offer, and says what it came to and why nothing restarted', function (): void {
    $ending = AStackThatEndsGrants::answering(WhatBecameOfTheWork::answered(theRevokeAsRead()), WhatBecameOfTheWork::answered(theRevokeAsEnded()));
    $screen = theGrantingScreen($ending);
    $screen->revoke(0);
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($ending->asked())->toBe(['read:tdarr:plex.token', sprintf('after:%s', AStackThatEndsGrants::THE_READING), sprintf('end:tdarr:plex.token:%s', THE_REVOKE_READ), sprintf('after:%s', AStackThatEndsGrants::THE_END)])
        ->and($drawn->said())->toContain(
            aLineAboutGrants('stacks.credentials.revoke.ended'),
            aLineAboutGrants('stacks.credentials.revoke.unrestarted', ['why' => 'It was stopped']),
            aLineAboutGrants('stacks.credentials.revoke.rotate'),
        )
        ->and($drawn->offers())->not->toContain(aLineAboutGrants('stacks.credentials.revoke.agree'));

    $screen->backToTheCredentials();
    $screen->answered = null;

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(aLineAboutGrants('stacks.credentials.grants.granted', ['plugin' => 'tdarr', 'fills' => 'library-scan']));
});

it('sends nothing for a yes with no reading of that grant, nor for a grant the list does not hold', function (): void {
    $ending = AStackThatEndsGrants::answering(WhatBecameOfTheWork::answered(theRevokeAsEnded()), WhatBecameOfTheWork::ended());
    $screen = theGrantingScreen($ending);
    $screen->agree();
    $screen->revoke(7);
    $screen->revoke(0);
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->agree();

    expect($ending->asked())->toBe(['read:tdarr:plex.token', sprintf('after:%s', AStackThatEndsGrants::THE_READING)]);
});

it('asks nothing new while the stack is still at it', function (): void {
    $ending = AStackThatEndsGrants::answering(WhatBecameOfTheWork::underway(Job::named(AStackThatEndsGrants::THE_READING)), WhatBecameOfTheWork::ended());
    $screen = theGrantingScreen($ending);
    $screen->revoke(0);
    $screen->revoke(0);

    expect($ending->asked())->toBe(['read:tdarr:plex.token'])
        ->and($screen->awaitsAnOutcome())->toBeTrue();
});

it('draws a refusal in the stack\'s words, a lost outcome, and what stood in the way', function (): void {
    $refusal = ARefusalInItsWords::said('tdarr holds no grant of plex.token', '', WhatTheRefusalNamed::nothing(), 'Read the passwords again');
    $refused = theGrantingScreen(AStackThatEndsGrants::sayingAtOnce(WhatBecameOfTheWork::refused($refusal)));
    $refused->revoke(0);
    $lost = theGrantingScreen(AStackThatEndsGrants::sayingAtOnce(WhatBecameOfTheWork::ended()));
    $lost->revoke(0);
    $met = theGrantingScreen(AStackThatEndsGrants::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $met->revoke(0);

    expect(WhatTheDeviceWouldDraw::by($refused)->said())->toContain(aLineAboutGrants('stacks.credentials.revoke.refused'), 'tdarr holds no grant of plex.token')
        ->and(WhatTheDeviceWouldDraw::by($lost)->said())->toContain(aLineAboutGrants('stacks.credentials.revoke.no_outcome'))
        ->and(WhatTheDeviceWouldDraw::by($met)->said())->toContain(__(Obstacle::of(KindOfObstacle::StackDidNotAnswer)->said()));
});

it('asks the stack again for the passwords and grants when the operator asks again', function (): void {
    $safekeeping = AStackThatHoldsCredentials::holding(thePasswordsWithTheirGrants(plexsTokenGrantedToTdarr()));
    $stack = theMachineWhoseGrantIsEnded();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $screen = new WhatItHoldsToLetThemIn($safekeeping, AStackThatEndsGrants::untouched(), FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_GRANT_WAS_APPROVED)), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored()]);
    $screen->answer();
    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($safekeeping->askings())->toBe(2);
});

it('a phone that holds no session says so when ending a grant', function (): void {
    $keychainless = theGrantingScreen(AStackThatEndsGrants::untouched(), signedIn: false);
    $keychainless->held = thePasswordsWithTheirGrants(plexsTokenGrantedToTdarr());
    $keychainless->revoke(0);

    expect($keychainless->howItIsGoing()->went->cameBack())->toBeFalse();
});
