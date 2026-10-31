<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACertificateReplacement;
use Modules\Kernel\Api\ACredentialHeld;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AHolderReached;
use Modules\Kernel\Api\AnInternalCertificate;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ASessionPlaying;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarAHolderWasReached;
use Modules\Kernel\Api\HowPlayingWasRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCredentialGrants;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\WhatAReplacementCameTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheHouseholdIsPlaying;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheStoreProtects;
use Modules\Kernel\Api\WhatUsesIt;
use Modules\Kernel\Api\WhereACredentialStands;
use Modules\Kernel\Api\WhereAReplacementSettled;
use Modules\Kernel\Api\WhoMadeACredential;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatItHoldsToLetThemIn;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatEndsGrants;
use Tests\Support\Fakes\AStackThatHoldsCredentials;
use Tests\Support\Fakes\AStackThatPlays;
use Tests\Support\Fakes\AStackThatReplacesItsCertificate;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

/** The offer the stack's reading of replacing the certificate goes by. */
const THE_REPLACEMENT_READ = 'the-certificate-in-force|the-holders|jellyfin';

/** What the stack says replacing the certificate would do. */
const WHAT_REPLACING_WOULD_DO = 'the decline service and the request gate are handed the new certificate\'s SHA-256 beside the old one; Jellyfin then serves the new certificate and may restart to do so';

function theMachineWhoseCertificateIsReplaced(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('i', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** The internal certificate with this SHA-256 in force, beside a password. */
function theCertificateAmongThePasswords(string $digest): TheCredentialsHeld
{
    return TheCredentialsHeld::of(
        WhatTheStoreProtects::said('Plain files readable only by you', Remarks::of(), Remarks::of()),
        TheCredentialGrants::of(),
        ACredentialHeld::described('Media server internal certificate', AnInternalCertificate::SETTING, WhereACredentialStands::Active, WhoMadeACredential::Lemonfiber, WhatUsesIt::of('the decline service', 'the request gate'), '')
            ->inForce(AnInternalCertificate::of($digest)),
        ACredentialHeld::described('Indexer API key', 'indexer.api_key', WhereACredentialStands::Active, WhoMadeACredential::Operator, WhatUsesIt::of('sonarr'), ''),
    );
}

function theDigestBefore(): string
{
    return str_repeat('a', Fingerprint::CHARACTERS);
}

function theDigestAfter(): string
{
    return str_repeat('b', Fingerprint::CHARACTERS);
}

function theCertificateAtAGlance(string $digest): string
{
    return AnInternalCertificate::of($digest)->atAGlance()->shown();
}

/** What the stack answers a reading of replacing the certificate with. */
function theReplacementAsRead(): WhatAReplacementCameTo
{
    return WhatAReplacementCameTo::of(ACertificateReplacement::read(THE_REPLACEMENT_READ, WHAT_REPLACING_WOULD_DO), theCertificateAmongThePasswords(theDigestBefore()));
}

/** What the stack answers the replacement with, settled where it is said to have. */
function theReplacementSettled(WhereAReplacementSettled $settled, string $said, string $digest, AHolderReached ...$holders): WhatAReplacementCameTo
{
    return WhatAReplacementCameTo::of(ACertificateReplacement::settled($settled, $said, ...$holders), theCertificateAmongThePasswords($digest));
}

function theReplacementLanded(): WhatAReplacementCameTo
{
    return theReplacementSettled(
        WhereAReplacementSettled::Replaced,
        'Jellyfin serves the new certificate, and the one it replaced is withdrawn',
        theDigestAfter(),
        AHolderReached::said('the decline service', HowFarAHolderWasReached::Updated, ''),
        AHolderReached::said('the request gate', HowFarAHolderWasReached::Updated, ''),
    );
}

/** Who is watching while the replacement is read. */
function sanneWatching(): AStackThatPlays
{
    return AStackThatPlays::holdingOpen(HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these(ASessionPlaying::of('Sanne', 'Low Tide', 'Living room TV', paused: false))));
}

function theReplacingScreen(AStackThatReplacesItsCertificate $replacing, ?AStackThatPlays $playing = null, ?Whose $whose = null, bool $signedIn = true): WhatItHoldsToLetThemIn
{
    $stack = theMachineWhoseCertificateIsReplaced();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), $whose ?? Whose::theOperator());
    }

    $screen = new WhatItHoldsToLetThemIn(
        AStackThatHoldsCredentials::holding(theCertificateAmongThePasswords(theDigestBefore())),
        AStackThatEndsGrants::untouched(),
        $replacing,
        $playing ?? sanneWatching(),
        FrozenClock::at(Instant::atEpochSeconds(1_761_321_600)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);
    $screen->answer();

    return $screen;
}

/** The screen once the replacement is read and the reading is in. */
function theReplacementReadOn(AStackThatReplacesItsCertificate $replacing, ?AStackThatPlays $playing = null): WhatItHoldsToLetThemIn
{
    $screen = theReplacingScreen($replacing, $playing);
    $screen->replaceTheCertificate();
    $screen->whileItRuns();
    $screen->howTheReplacementGoes();

    return $screen;
}

/** @param array<string, string> $with */
function aLineAboutTheCertificate(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : '';
}

it('lists the internal certificate by the one in force at a glance, says the pairing is not it, and offers replacing it alone', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theReplacingScreen(AStackThatReplacesItsCertificate::untouched()));

    expect($drawn->said())->toContain(
        'Media server internal certificate',
        aLineAboutTheCertificate('stacks.credentials.certificate.in_force', ['glance' => theCertificateAtAGlance(theDigestBefore())]),
        aLineAboutTheCertificate('stacks.credentials.certificate.not_the_pairing'),
    )->and(implode("\n", $drawn->said()))->not->toContain(theDigestBefore())
        ->and(array_count_values($drawn->offers())[aLineAboutTheCertificate('stacks.credentials.certificate.replace')] ?? 0)->toBe(1);
});

it('reads what replacing it would do first, labelled as nothing replaced yet, in the stack\'s words, with who is watching now', function (): void {
    $replacing = AStackThatReplacesItsCertificate::answering(WhatBecameOfTheWork::answered(theReplacementAsRead()), WhatBecameOfTheWork::ended());
    $playing = sanneWatching();
    $screen = theReplacingScreen($replacing, $playing);
    $screen->replaceTheCertificate();
    $running = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($running)->toContain(aLineAboutTheCertificate('stacks.credentials.replace.working'))
        ->and($replacing->asked())->toBe(['read', sprintf('after:%s', AStackThatReplacesItsCertificate::THE_READING)])
        ->and($playing->read())->toBe(1)
        ->and($drawn->said())->toContain(
            aLineAboutTheCertificate('stacks.credentials.replace.reading'),
            WHAT_REPLACING_WOULD_DO,
            aLineAboutTheCertificate('stacks.credentials.replace.watching'),
            aLineAboutTheCertificate('stacks.credentials.replace.session', ['member' => 'Sanne', 'device' => 'Living room TV']),
            aLineAboutTheCertificate('stacks.credentials.certificate.not_the_pairing'),
        )
        ->and($drawn->said())->not->toContain(aLineAboutTheCertificate(WhereAReplacementSettled::Replaced->saidOnTheScreen()))
        ->and($drawn->offers())->toContain(aLineAboutTheCertificate('stacks.credentials.replace.agree'));
});

it('says nobody is watching, that who is watching could not be read, and never asks with a member\'s session', function (): void {
    $replacing = static fn(): AStackThatReplacesItsCertificate => AStackThatReplacesItsCertificate::answering(WhatBecameOfTheWork::answered(theReplacementAsRead()), WhatBecameOfTheWork::ended());
    $nobody = theReplacementReadOn($replacing(), AStackThatPlays::holdingOpen(HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these())));
    $unread = theReplacementReadOn($replacing(), AStackThatPlays::holdingOpen(HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::unread('Jellyfin did not answer.'))));
    $refused = theReplacementReadOn($replacing(), AStackThatPlays::holdingOpen(HowPlayingWasRead::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $playing = sanneWatching();
    $member = theReplacingScreen($replacing(), $playing, Whose::member('sanne'));
    $member->replaceTheCertificate();

    expect(WhatTheDeviceWouldDraw::by($nobody)->said())->toContain(aLineAboutTheCertificate('stacks.credentials.replace.nobody_watching'))
        ->and(WhatTheDeviceWouldDraw::by($unread)->said())->toContain(aLineAboutTheCertificate('stacks.credentials.replace.watching_unread'))
        ->and(WhatTheDeviceWouldDraw::by($refused)->said())->toContain(aLineAboutTheCertificate('stacks.credentials.replace.watching_unread'))
        ->and($playing->read())->toBe(0)
        ->and($member->watching?->read)->toBeFalse();
});

it('replaces it on a yes carrying the reading\'s offer, and says replaced only where the stack does, with each holder and the one now in force', function (): void {
    $replacing = AStackThatReplacesItsCertificate::answering(WhatBecameOfTheWork::answered(theReplacementAsRead()), WhatBecameOfTheWork::answered(theReplacementLanded()));
    $screen = theReplacementReadOn($replacing);
    $screen->agreeToReplace();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($replacing->asked())->toBe(['read', sprintf('after:%s', AStackThatReplacesItsCertificate::THE_READING), sprintf('replace:%s', THE_REPLACEMENT_READ), sprintf('after:%s', AStackThatReplacesItsCertificate::THE_REPLACEMENT)])
        ->and($drawn->said())->toContain(
            aLineAboutTheCertificate(WhereAReplacementSettled::Replaced->saidOnTheScreen()),
            'Jellyfin serves the new certificate, and the one it replaced is withdrawn',
            aLineAboutTheCertificate('stacks.credentials.replace.holder', ['holder' => 'the request gate', 'reach' => aLineAboutTheCertificate(HowFarAHolderWasReached::Updated->saidOnTheScreen())]),
            aLineAboutTheCertificate('stacks.credentials.replace.in_force', ['glance' => theCertificateAtAGlance(theDigestAfter())]),
            aLineAboutTheCertificate('stacks.credentials.certificate.not_the_pairing'),
        )
        ->and($drawn->offers())->not->toContain(aLineAboutTheCertificate('stacks.credentials.replace.agree'), aLineAboutTheCertificate('stacks.credentials.replace.again'));

    $screen->backToTheCredentials();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineAboutTheCertificate('stacks.credentials.certificate.in_force', ['glance' => theCertificateAtAGlance(theDigestAfter())]));
});

it('says each outcome short of replaced in its own words, with each holder and why, and offers starting again', function (WhereAReplacementSettled $settled): void {
    $failed = AHolderReached::said('the request gate', HowFarAHolderWasReached::Failed, 'it did not answer the new SHA-256');
    $replacing = AStackThatReplacesItsCertificate::answering(WhatBecameOfTheWork::answered(theReplacementAsRead()), WhatBecameOfTheWork::answered(theReplacementSettled($settled, 'the old certificate is still kept', theDigestBefore(), $failed)));
    $screen = theReplacementReadOn($replacing);
    $screen->agreeToReplace();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(
        aLineAboutTheCertificate($settled->saidOnTheScreen()),
        'the old certificate is still kept',
        aLineAboutTheCertificate('stacks.credentials.replace.holder', ['holder' => 'the request gate', 'reach' => aLineAboutTheCertificate(HowFarAHolderWasReached::Failed->saidOnTheScreen())]),
        'it did not answer the new SHA-256',
    )->and($drawn->said())->not->toContain(aLineAboutTheCertificate(WhereAReplacementSettled::Replaced->saidOnTheScreen()))
        ->and($drawn->offers())->toContain(aLineAboutTheCertificate('stacks.credentials.replace.again'));

    $screen->replaceTheCertificate();

    expect($replacing->asked())->toBe(['read', sprintf('after:%s', AStackThatReplacesItsCertificate::THE_READING), sprintf('replace:%s', THE_REPLACEMENT_READ), sprintf('after:%s', AStackThatReplacesItsCertificate::THE_REPLACEMENT), 'read']);
})->with([
    'replaced, unproven' => [WhereAReplacementSettled::ReplacedUnproven],
    'unproven' => [WhereAReplacementSettled::Unproven],
    'refused' => [WhereAReplacementSettled::Refused],
    'unknown' => [WhereAReplacementSettled::Unknown],
    'elsewhere' => [WhereAReplacementSettled::Elsewhere],
]);

it('reads it again where the yes was refused because the reading moved, never as a failure, and offers the fresh reading', function (): void {
    $moved = ARefusalInItsWords::said('What was agreed to is not what replacing the certificate would do now', '', WhatTheRefusalNamed::nothing(), 'Read the replacement again');
    $replacing = AStackThatReplacesItsCertificate::answering(WhatBecameOfTheWork::answered(theReplacementAsRead()), WhatBecameOfTheWork::moved($moved));
    $screen = theReplacementReadOn($replacing);
    $screen->agreeToReplace();
    $screen->whileItRuns();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($replacing->asked())->toBe([
        'read', sprintf('after:%s', AStackThatReplacesItsCertificate::THE_READING),
        sprintf('replace:%s', THE_REPLACEMENT_READ), sprintf('after:%s', AStackThatReplacesItsCertificate::THE_REPLACEMENT),
        'read', sprintf('after:%s', AStackThatReplacesItsCertificate::THE_READING),
    ])->and($drawn->said())->toContain(
        aLineAboutTheCertificate('stacks.credentials.replace.moved'),
        'What was agreed to is not what replacing the certificate would do now',
        aLineAboutTheCertificate('stacks.credentials.replace.reading'),
    )->and($drawn->said())->not->toContain(aLineAboutTheCertificate('stacks.credentials.replace.refused'))
        ->and($drawn->offers())->toContain(aLineAboutTheCertificate('stacks.credentials.replace.agree'));

    $screen->agreeToReplace();

    expect($screen->movedOn)->toBeNull();
});

it('sends no yes without a reading, and asks nothing new while the stack is still at it', function (): void {
    $replacing = AStackThatReplacesItsCertificate::answering(WhatBecameOfTheWork::underway(Job::named(AStackThatReplacesItsCertificate::THE_READING)), WhatBecameOfTheWork::ended());
    $screen = theReplacingScreen($replacing);
    $screen->agreeToReplace();
    $screen->replaceTheCertificate();
    $screen->replaceTheCertificate();
    $screen->revoke(0);
    $screen->agreeToReplace();

    expect($replacing->asked())->toBe(['read'])
        ->and($screen->awaitsAnOutcome())->toBeTrue();
});

it('draws a refusal in the stack\'s words, a lost outcome, and what stood in the way', function (): void {
    $refusal = ARefusalInItsWords::said('The media server could not be reached', '', WhatTheRefusalNamed::nothing());
    $refused = theReplacingScreen(AStackThatReplacesItsCertificate::sayingAtOnce(WhatBecameOfTheWork::refused($refusal)));
    $refused->replaceTheCertificate();
    $readAgain = $refused->held;
    $lost = theReplacingScreen(AStackThatReplacesItsCertificate::sayingAtOnce(WhatBecameOfTheWork::ended()));
    $lost->replaceTheCertificate();
    $met = theReplacingScreen(AStackThatReplacesItsCertificate::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $met->replaceTheCertificate();

    expect(WhatTheDeviceWouldDraw::by($refused)->said())->toContain(aLineAboutTheCertificate('stacks.credentials.replace.refused'), 'The media server could not be reached')
        ->and($readAgain)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($lost)->said())->toContain(aLineAboutTheCertificate('stacks.credentials.replace.no_outcome'))
        ->and(WhatTheDeviceWouldDraw::by($met)->said())->toContain(__(Obstacle::of(KindOfObstacle::StackDidNotAnswer)->said()));
});

it('a phone that holds no session says so, and asks nobody who is watching', function (): void {
    $playing = sanneWatching();
    $keychainless = theReplacingScreen(AStackThatReplacesItsCertificate::untouched(), $playing, signedIn: false);
    $keychainless->replaceTheCertificate();

    expect($keychainless->howTheReplacementGoes()->stands->went->cameBack())->toBeFalse()
        ->and($playing->read())->toBe(0);
});

it('leaves the replacement for the list, keeping nothing it read', function (): void {
    $replacing = AStackThatReplacesItsCertificate::answering(WhatBecameOfTheWork::answered(theReplacementAsRead()), WhatBecameOfTheWork::ended());
    $screen = theReplacementReadOn($replacing);
    $screen->backToTheCredentials();

    expect([$screen->replacing, $screen->replacement, $screen->watching, $screen->movedOn, $screen->followingTheReplacement])->toBe([false, null, null, null, null])
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(aLineAboutTheCertificate('stacks.credentials.certificate.replace'));
});
