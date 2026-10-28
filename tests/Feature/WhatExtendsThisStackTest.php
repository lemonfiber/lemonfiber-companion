<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APrivilegedShape;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowExtendingItIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Kernel\Api\TheInstalledPlugins;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\ThePluginSources;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyNothingWasScanned;
use Modules\Operator\Internal\Screens\WhatExtendsThisStack;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Internal\TheMenu;
use Tests\Support\APluginAsItArrives;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACameraInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExtendsItself;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// The plugins on a machine, and installing one: rehearsed, agreed to in two
// parts, and followed to what it came to.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.

/** The machine plugins are installed on. */
function theMachinePluginsExtend(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The screen, signed in, in front of a stack that answers as the case says. */
function thePluginsScreen(AStackThatExtendsItself $extending, ?AKeychainInMemory $keychain = null, ?ACameraInMemory $camera = null): WhatExtendsThisStack
{
    $stack = theMachinePluginsExtend();
    $keychain ??= AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new WhatExtendsThisStack($extending, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening(), $camera ?? ACameraInMemory::reading('tdarr'));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A stack listing Tdarr, then answering the rehearsal and anything after it with these. */
function aStackWithTdarr(HowExtendingItIsGoing ...$answers): AStackThatExtendsItself
{
    return AStackThatExtendsItself::listing(APluginAsItArrives::theListing(), ...$answers);
}

/** The screen with Tdarr's rehearsal in front of the operator. */
function tdarrRehearsed(AStackThatExtendsItself $extending): WhatExtendsThisStack
{
    $screen = thePluginsScreen($extending);
    $screen->installOne();
    $screen->source = 'tdarr';
    $screen->rehearse();
    $screen->whileItRuns();
    $screen->answer();

    return $screen;
}

/**
 * A line of the plugins catalogue, filled in.
 *
 * @param array<string, string> $with
 */
function aPluginsLine(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : '';
}

/** What the stack refuses a plugin with, in its words. */
function aPluginRefusal(string $said): HowExtendingItIsGoing
{
    return HowExtendingItIsGoing::refused(ARefusalInItsWords::said($said, 'Nothing was installed and nothing was written.', WhatTheRefusalNamed::nothing()));
}

it('takes where a plugin comes from off a code as well as from the field', function (): void {
    $screen = thePluginsScreen(aStackWithTdarr(), camera: ACameraInMemory::reading('  https://example.org/plugins/tdarr.git  '));
    $screen->installOne();
    $screen->scanWhereItComesFrom();

    expect($screen->source)->toBe('https://example.org/plugins/tdarr.git')
        ->and($screen->nothingWasScanned())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(aPluginsLine('plugins.scan_where_from'));
});

it('says why where the camera handed nothing back, and leaves the field as it was', function (): void {
    $screen = thePluginsScreen(aStackWithTdarr(), camera: ACameraInMemory::answering(WhyNothingWasScanned::TheCameraWasDeclined));
    $screen->installOne();
    $screen->source = 'tdarr';
    $screen->scanWhereItComesFrom();

    expect($screen->source)->toBe('tdarr')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(WhyNothingWasScanned::TheCameraWasDeclined->saidOnTheScreen()));
});

it('says a plugin fills nothing its adapter answered outside the contract for, until it is proved again', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(thePluginsScreen(aStackWithTdarr()));

    expect($drawn->said())->toContain(aPluginsLine('plugins.out_of_contract'))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.answered_out_of_contract', [
            'capability' => 'transcoding',
            'operation' => 'queue',
            'why' => 'It answered with a field its contract does not have',
        ]));
});

it('says nothing of answers out of contract where its adapters gave none', function (): void {
    $held = iterator_to_array(APluginAsItArrives::theListing()->installed(), preserve_keys: false);
    $drawn = WhatTheDeviceWouldDraw::by(thePluginsScreen(AStackThatExtendsItself::listing(ThePlugins::listed(TheInstalledPlugins::these(...$held), ThePluginSources::these(), TheAnswersOutOfContract::these()))));

    expect($drawn->said())->not->toContain(aPluginsLine('plugins.out_of_contract'));
});

it('opens on what is installed, each with what vouches for it and how its source stands', function (): void {
    $extending = aStackWithTdarr();
    $drawn = WhatTheDeviceWouldDraw::by(thePluginsScreen($extending));

    expect($extending->asked())->toBe(['list'])
        ->and($drawn->said())->toContain(aPluginsLine('plugins.named', ['name' => 'Tdarr', 'version' => '2.1.0']))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.reviewed'))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.from', ['source' => 'tdarr']))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.source.unreachable', ['why' => 'The catalogue did not answer']))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.signed', ['signed' => 'the catalogue key SHA256:abc']))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.licence', ['licence' => 'GPL-3.0']))
        ->and($drawn->offers())->toContain(aPluginsLine('plugins.install_one'))
        ->and($drawn->offers())->toContain(aPluginsLine('plugins.settings.open', ['name' => 'Tdarr']));
});

it('says no plugins are installed where the record holds none, and that the record could not be read where it could not', function (): void {
    $none = WhatTheDeviceWouldDraw::by(thePluginsScreen(AStackThatExtendsItself::listing(ThePlugins::listed(TheInstalledPlugins::these(), ThePluginSources::these(), TheAnswersOutOfContract::these()))));
    $unread = WhatTheDeviceWouldDraw::by(thePluginsScreen(AStackThatExtendsItself::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));

    expect($none->said())->toContain(aPluginsLine('plugins.none'))
        ->and($unread->said())->not->toContain(aPluginsLine('plugins.none'))
        ->and($unread->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($unread->said())->not->toContain(aPluginsLine('plugins.unread_after_yes'));
});

it('asks for a source in one field, with its three shapes under it, and asks nothing for a blank one or before one is typed', function (): void {
    $extending = aStackWithTdarr();
    $screen = thePluginsScreen($extending);
    $screen->installOne();
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->rehearse();
    $screen->source = '   ';
    $screen->rehearse();

    expect($drawn->said())->toContain(aPluginsLine('plugins.source_catalogue'))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.source_directory'))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.source_git'))
        ->and($drawn->offers())->toContain(aPluginsLine('plugins.rehearse'))
        ->and($extending->asked())->toBe([]);
});

it('rehearses an install and draws all of it as a rehearsal, every proof not asked', function (): void {
    $extending = aStackWithTdarr(HowExtendingItIsGoing::underway(Job::named('j-1')), HowExtendingItIsGoing::done(APluginAsItArrives::theReading()));
    $screen = thePluginsScreen($extending);
    $screen->installOne();
    $screen->source = '  tdarr  ';
    $screen->rehearse();
    $running = WhatTheDeviceWouldDraw::by($screen);
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $said = $drawn->said();

    expect($running->said())->toContain(aPluginsLine('plugins.working.rehearse'))
        ->and($extending->asked())->toBe(['rehearse:tdarr', 'after:j-1'])
        ->and($said)->toContain(aPluginsLine('plugins.rehearsal'))
        ->and($said)->toContain('/srv/lemonfiber/plugins/tdarr/plugin.toml')
        ->and($said)->toContain(aPluginsLine('plugins.puts.document'))
        ->and($said)->toContain('It answers on its port')
        ->and($said)->toContain(aPluginsLine('plugins.proof.not_asked'))
        ->and($said)->not->toContain(aPluginsLine('plugins.proof.passed'))
        ->and($said)->toContain('sonarr.rename')
        ->and($said)->toContain('transcode')
        ->and($said)->toContain(aPluginsLine('plugins.claimant', ['claimant' => 'unmanic']))
        ->and($said)->toContain(aPluginsLine('plugins.step', ['method' => 'POST', 'to' => 'sonarr', 'path' => '/api/v3/notification']))
        ->and($said)->toContain(aPluginsLine('plugins.adapter', ['adapter' => 'servarr']))
        ->and($said)->toContain(aPluginsLine('plugins.pair', ['value' => 'library', 'to' => 'hooks.example.com']))
        ->and(array_count_values($said)[aPluginsLine('plugins.stays_here')] ?? 0)->toBe(1)
        ->and($said)->not->toContain(aPluginsLine('plugins.not_asked_here'))
        ->and($said)->toContain(aPluginsLine('plugins.inputs_elsewhere'))
        ->and($drawn->offers())->toContain(aPluginsLine('plugins.install'));
});

it('approves each value apart from the install, and sends only what was approved with the reading\'s name', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
        HowExtendingItIsGoing::underway(Job::named('j-2')),
    );
    $screen = tdarrRehearsed($extending);
    $screen->approve(7);
    $screen->approve(0);
    $screen->approve(0);
    $screen->approve(0);
    $approved = WhatTheDeviceWouldDraw::by($screen);
    $screen->agree();
    $screen->agree();

    expect($screen->approved)->toBe([])
        ->and($approved->said())->toContain(aPluginsLine('plugins.approvals_apart'))
        ->and($extending->asked())->toBe(['rehearse:tdarr', 'after:j-1', sprintf('install:tdarr:%s:%s', APluginAsItArrives::AGREEMENT, APluginAsItArrives::APPROVAL)])
        ->and($screen->following)->toBe('j-2')
        ->and($screen->agreed)->toBeTrue();
});

it('draws each service taking a privileged shape with what it is granted and given and why, each with a switch of its own', function (): void {
    $screen = tdarrRehearsed(aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theGuardedReading()),
    ));
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $plain = WhatTheDeviceWouldDraw::by(tdarrRehearsed(aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
    )));

    expect($drawn->said())->toContain('gluetun')
        ->and($drawn->said())->toContain(aPluginsLine('plugins.shape.egress-guard'))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.granted', ['grant' => 'NET_ADMIN']))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.given', ['device' => '/dev/net/tun']))
        ->and($drawn->offers())->toContain(aPluginsLine('plugins.approve_shape', ['service' => 'gluetun']))
        ->and($plain->said())->toContain(aPluginsLine('plugins.no_shapes'))
        ->and($plain->offers())->not->toContain(aPluginsLine('plugins.approve_shape', ['service' => 'gluetun']));
});

it('names each adapter that would write the files of the service it fronts, and says where none would', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(tdarrRehearsed(aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theSharingReading()),
    )));
    $plain = WhatTheDeviceWouldDraw::by(tdarrRehearsed(aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
    )));

    expect($drawn->said())->toContain(
        aPluginsLine('plugins.sharing'),
        aPluginsLine('plugins.writes_files_of', ['adapter' => 'tdarr-adapter', 'upstream' => 'tdarr']),
        aPluginsLine('plugins.writes_files_at', ['upstream' => 'tdarr', 'at' => '/upstream']),
    )
        ->and($drawn->said())->not->toContain(aPluginsLine('plugins.no_sharing'))
        ->and($plain->said())->toContain(aPluginsLine('plugins.no_sharing'))
        ->and($plain->said())->not->toContain(aPluginsLine('plugins.writes_files_of', ['adapter' => 'tdarr-adapter', 'upstream' => 'tdarr']));
});

it('names an adapter writing its upstream\'s files in an update\'s rehearsal too', function (): void {
    $extending = aStackWithTdarr(HowExtendingItIsGoing::underway(Job::named('j-1')), HowExtendingItIsGoing::done(APluginAsItArrives::theSharingUpdateReading()));
    $screen = thePluginsListed($extending);
    $screen->actOnTheRow('update', 0);
    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aPluginsLine('plugins.writes_files_of', ['adapter' => 'tdarr-adapter', 'upstream' => 'tdarr']));
});

it('approves a shape apart from the install, and sends it with the reading\'s name only where it was approved', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theGuardedReading()),
        HowExtendingItIsGoing::underway(Job::named('j-2')),
    );
    $screen = tdarrRehearsed($extending);
    $screen->approve(1);
    $screen->agree();

    expect($extending->asked())->toBe(['rehearse:tdarr', 'after:j-1', sprintf('install:tdarr:%s:%s', APluginAsItArrives::AGREEMENT, APluginAsItArrives::SHAPE_APPROVAL)])
        ->and($screen->leftUnapproved?->isEmpty())->toBeTrue();
});

it('draws each password of another service the install would hand the plugin, by its setting, each with a switch of its own that starts off', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(tdarrRehearsed(aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theGrantingReading()),
    )));
    $plain = WhatTheDeviceWouldDraw::by(tdarrRehearsed(aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
    )));

    expect($drawn->said())->toContain(
        aPluginsLine('plugins.granting.heading'),
        aPluginsLine('stacks.credentials.grants.of', ['credential' => 'plex.token', 'whose' => 'plex']),
        aPluginsLine('plugins.granting.for', ['fills' => 'library-scan']),
        aPluginsLine('plugins.granting.each'),
    )->and($drawn->offers())->toContain(aPluginsLine('plugins.granting.approve', ['credential' => 'plex.token', 'plugin' => 'Tdarr']))
        ->and($plain->said())->toContain(aPluginsLine('plugins.granting.none'))
        ->and($plain->said())->not->toContain(aPluginsLine('plugins.granting.each'));
});

it('hands a password over only where its switch was turned on, and the yes to the install alone hands over none', function (): void {
    $approving = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theGrantingReading()),
        HowExtendingItIsGoing::underway(Job::named('j-2')),
    );
    $screen = tdarrRehearsed($approving);
    $screen->approve(1);
    $screen->agree();
    $leaving = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theGrantingReading()),
        HowExtendingItIsGoing::underway(Job::named('j-2')),
    );
    $alone = tdarrRehearsed($leaving);
    $alone->agree();

    expect($approving->asked())->toBe(['rehearse:tdarr', 'after:j-1', sprintf('install:tdarr:%s:%s', APluginAsItArrives::AGREEMENT, APluginAsItArrives::GRANT_APPROVAL)])
        ->and($leaving->asked())->toBe(['rehearse:tdarr', 'after:j-1', sprintf('install:tdarr:%s:', APluginAsItArrives::AGREEMENT)]);
});

it('names each service the yes left without its shape where the install is refused, and lets go of them on the way back', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theGuardedReading()),
        aPluginRefusal('egress-guard@gluetun was not approved'),
    );
    $screen = tdarrRehearsed($extending);
    $screen->approve(0);
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->backToThePlugins();

    expect($extending->asked())->toBe(['rehearse:tdarr', 'after:j-1', sprintf('install:tdarr:%s:%s', APluginAsItArrives::AGREEMENT, APluginAsItArrives::APPROVAL)])
        ->and($drawn->said())->toContain(aPluginsLine('plugins.refused.install'))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.shape_unapproved', ['service' => 'gluetun']))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.shape.egress-guard'))
        ->and($screen->leftUnapproved)->toBeNull();
});

it('sends an install with nothing approved, and draws what the stack makes of it in its words', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
        aPluginRefusal('library@hooks.example.com was not approved'),
    );
    $screen = tdarrRehearsed($extending);
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($extending->asked())->toBe(['rehearse:tdarr', 'after:j-1', sprintf('install:tdarr:%s:', APluginAsItArrives::AGREEMENT)])
        ->and($drawn->said())->toContain(aPluginsLine('plugins.refused.install'))
        ->and($drawn->said())->toContain('library@hooks.example.com was not approved')
        ->and($drawn->offers())->not->toContain(aPluginsLine('plugins.install'));
});

it('draws a plugin the stack refuses to rehearse as its refusal, with nothing to install', function (): void {
    $extending = aStackWithTdarr(aPluginRefusal('This plugin declares native content, which this build refuses'));
    $screen = thePluginsScreen($extending);
    $screen->installOne();
    $screen->source = './native';
    $screen->rehearse();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(aPluginsLine('plugins.refused.rehearse'))
        ->and($drawn->said())->toContain('This plugin declares native content, which this build refuses')
        ->and($drawn->offers())->not->toContain(aPluginsLine('plugins.install'))
        ->and($drawn->offers())->toContain(aPluginsLine('plugins.back'));
});

it('says installed only where the record was written, its proofs held and nothing broke', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
        HowExtendingItIsGoing::underway(Job::named('j-2')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theInstall()),
    );
    $screen = tdarrRehearsed($extending);
    $screen->approve(0);
    $screen->agree();
    $installing = WhatTheDeviceWouldDraw::by($screen);
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($installing->said())->toContain(aPluginsLine('plugins.working.install'))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.installed_it'))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.proof.passed'))
        ->and($drawn->said())->toContain(aPluginsLine('plugins.broke_nothing'))
        ->and($drawn->said())->not->toContain(aPluginsLine('plugins.rehearsal'))
        ->and($drawn->offers())->not->toContain(aPluginsLine('plugins.install'))
        ->and($screen->following)->toBeNull();
});

it('says an install that did not hold was put back, with what went back and what did not and why', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
        HowExtendingItIsGoing::done(APluginAsItArrives::thePutBack()),
    );
    $screen = tdarrRehearsed($extending);
    $screen->agree();
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(aPluginsLine('plugins.put_back'))
        ->and($said)->not->toContain(aPluginsLine('plugins.installed_it'))
        ->and($said)->toContain(aPluginsLine('plugins.proof.failed'))
        ->and($said)->toContain('It answered 502')
        ->and($said)->toContain(aPluginsLine('plugins.not_checked'))
        ->and($said)->toContain(aPluginsLine('plugins.what_went_back'))
        ->and($said)->toContain('tdarr-data')
        ->and($said)->toContain('The volume was in use');
});

it('says whether it was installed could not be read where following the install met something', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
        HowExtendingItIsGoing::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
    );
    $screen = tdarrRehearsed($extending);
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(aPluginsLine('plugins.unread_after_yes'))
        ->and($drawn->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));
});

it('says the stack has no outcome for work it no longer knows, and goes back to what is installed', function (): void {
    $extending = aStackWithTdarr(HowExtendingItIsGoing::underway(Job::named('j-1')));
    $screen = thePluginsScreen($extending);
    $screen->installOne();
    $screen->source = 'tdarr';
    $screen->rehearse();
    $screen->whileItRuns();
    $ended = WhatTheDeviceWouldDraw::by($screen);
    $screen->backToThePlugins();
    $screen->answer();

    expect($ended->said())->toContain(aPluginsLine('plugins.no_outcome'))
        ->and($extending->asked())->toBe(['rehearse:tdarr', 'after:j-1', 'list'])
        ->and($screen->source)->toBe('');
});

it('lets go of the rehearsal and every approval when it goes back, and asks after the same work while it runs', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
        HowExtendingItIsGoing::underway(Job::named('j-2')),
    );
    $screen = tdarrRehearsed($extending);
    $screen->approve(0);
    $screen->backToThePlugins();

    expect($screen->rehearsal)->toBeNull()
        ->and($screen->approved)->toBe([]);

    $screen->agree();
    $screen->installOne();
    $screen->source = 'tdarr';
    $screen->rehearse();
    $screen->installOne();
    $screen->again();
    $screen->backToThePlugins();

    expect($extending->asked())->toBe(['rehearse:tdarr', 'after:j-1', 'rehearse:tdarr'])
        ->and($screen->following)->toBe('j-2');
});

it('says the session has ended where this phone holds none, and asks nothing', function (): void {
    $extending = aStackWithTdarr();
    $stack = theMachinePluginsExtend();
    $screen = new WhatExtendsThisStack($extending, AKeychainInMemory::working(), AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening(), ACameraInMemory::reading('tdarr'));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    expect($screen->answer()->stands->went->cameBack())->toBeFalse()
        ->and($screen->awaitsAnOutcome())->toBeFalse()
        ->and($extending->asked())->toBe([]);
});

it('approves nothing before a rehearsal, and asks what is installed again when asked to', function (): void {
    $extending = aStackWithTdarr();
    $screen = thePluginsScreen($extending);
    $screen->approve(0);
    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($screen->approved)->toBe([])
        ->and($extending->asked())->toBe(['list', 'list']);
});

it('offers the source field again where installing one begins with a rehearsal on the screen', function (): void {
    $screen = tdarrRehearsed(aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
    ));
    $screen->installOne();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->offers())->toContain(aPluginsLine('plugins.rehearse'))
        ->and($drawn->offers())->not->toContain(aPluginsLine('plugins.install'));
});

it('rehearses afresh, every approval let go of, where a source is rehearsed again', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
        HowExtendingItIsGoing::underway(Job::named('j-2')),
    );
    $screen = tdarrRehearsed($extending);
    $screen->approve(0);
    $screen->rehearse();
    $screen->answer();
    $screen->agree();

    expect($extending->asked())->toBe(['rehearse:tdarr', 'after:j-1', 'rehearse:tdarr', sprintf('install:tdarr:%s:', APluginAsItArrives::AGREEMENT)]);
});

it('takes an approval back where it is given twice, and sends every one still given', function (): void {
    $taken = tdarrRehearsed(aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theGuardedReading()),
    ));
    $taken->approve(0);
    $taken->approve(0);
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theGuardedReading()),
        HowExtendingItIsGoing::underway(Job::named('j-2')),
    );
    $both = tdarrRehearsed($extending);
    $both->approve(0);
    $both->approve(1);
    $both->agree();

    expect($taken->approved)->toBe([])
        ->and($extending->asked())->toContain(sprintf('install:tdarr:%s:%s,%s', APluginAsItArrives::AGREEMENT, APluginAsItArrives::APPROVAL, APluginAsItArrives::SHAPE_APPROVAL));
});

it('asks what is installed again, and lets go of the rehearsal, when asked again with no work followed', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theReading()),
    );
    $screen = tdarrRehearsed($extending);
    $screen->again();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($extending->asked())->toBe(['rehearse:tdarr', 'after:j-1', 'list'])
        ->and($drawn->offers())->not->toContain(aPluginsLine('plugins.install'));
});

/** The screen with the listing read, so a row can be chosen. */
function thePluginsListed(AStackThatExtendsItself $extending): WhatExtendsThisStack
{
    $screen = thePluginsScreen($extending);
    $screen->answer();

    return $screen;
}

it('offers to update and to remove each plugin by its name, and says where one cannot be updated', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(thePluginsListed(aStackWithTdarr()));
    $sourceless = APlugin::named('tdarr', '2.1.0', 'Tdarr', WhatVouchesForAPlugin::recorded('', '', '', reviewed: false, upstream: '', licence: ''), TheRecipes::these());
    $extending = AStackThatExtendsItself::listing(ThePlugins::listed(TheInstalledPlugins::these($sourceless), ThePluginSources::these(), TheAnswersOutOfContract::these()));
    $screen = thePluginsListed($extending);
    $cannot = WhatTheDeviceWouldDraw::by($screen);
    $screen->actOnTheRow('update', 0);
    $screen->actOnTheRow('update', 4);
    $screen->actOnTheRow('remove', 4);

    expect($drawn->offers())->toContain(aPluginsLine('plugins.update_it', ['name' => 'Tdarr']))
        ->and($drawn->offers())->toContain(aPluginsLine('plugins.remove_it', ['name' => 'Tdarr']))
        ->and($drawn->said())->not->toContain(aPluginsLine('plugins.no_source_to_update'))
        ->and($cannot->offers())->not->toContain(aPluginsLine('plugins.update_it', ['name' => 'Tdarr']))
        ->and($cannot->offers())->toContain(aPluginsLine('plugins.remove_it', ['name' => 'Tdarr']))
        ->and($cannot->said())->toContain(aPluginsLine('plugins.no_source_to_update'))
        ->and($extending->asked())->toBe(['list']);
});

it('asks about the plugin in the row named, and nothing before anything is listed', function (): void {
    $vouched = WhatVouchesForAPlugin::recorded('', '', '', reviewed: false, upstream: '', licence: '');
    $listing = ThePlugins::listed(TheInstalledPlugins::these(APlugin::named('tdarr', '2.1.0', 'Tdarr', $vouched, TheRecipes::these()), APlugin::named('unmanic', '1.0.0', 'Unmanic', $vouched, TheRecipes::these())), ThePluginSources::these(), TheAnswersOutOfContract::these());
    $extending = AStackThatExtendsItself::listing($listing, HowExtendingItIsGoing::underway(Job::named('j-1')));
    $unlisted = thePluginsScreen($extending);
    $unlisted->actOnTheRow('remove', 0);
    $asked = $extending->asked();
    $unlisted->answer();
    $unlisted->actOnTheRow('remove', 1);

    expect($asked)->toBe([])
        ->and($extending->asked())->toBe(['list', 'rehearse removal:unmanic']);
});

it('rehearses an update and draws it as a rehearsal: from and to, what stops, what comes off, and the new version', function (): void {
    $extending = aStackWithTdarr(HowExtendingItIsGoing::underway(Job::named('j-1')), HowExtendingItIsGoing::done(APluginAsItArrives::theUpdateReading()));
    $screen = thePluginsListed($extending);
    $screen->actOnTheRow('update', 0);
    $running = WhatTheDeviceWouldDraw::by($screen);
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $said = $drawn->said();

    expect($running->said())->toContain(aPluginsLine('plugins.working.rehearse'))
        ->and($extending->asked())->toBe(['list', 'rehearse update:tdarr:tdarr', 'after:j-1'])
        ->and($said)->toContain(aPluginsLine('plugins.rehearsal'))
        ->and($said)->toContain(aPluginsLine('plugins.from_to', ['from' => '2.1.0', 'to' => '2.2.0']))
        ->and($said)->toContain(aPluginsLine('plugins.what_goes_back'))
        ->and($said)->toContain(__('stacks.run_back.a_rehearsal'))
        ->and($said)->toContain('What the service wrote stays for the new version')
        ->and($said)->toContain(aPluginsLine('plugins.new_version'))
        ->and($said)->toContain(aPluginsLine('plugins.proof.not_asked'))
        ->and($said)->not->toContain(aPluginsLine('plugins.not_updated'))
        ->and($drawn->offers())->toContain(aPluginsLine('plugins.update'))
        ->and($drawn->offers())->not->toContain(aPluginsLine('plugins.install'));
});

it('sends the update with the reading\'s name and each approval, and says a new version that did not hold was not updated and how far the old one came back', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theUpdateReading()),
        HowExtendingItIsGoing::underway(Job::named('j-2')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theUpdateNotHeld()),
    );
    $screen = thePluginsListed($extending);
    $screen->actOnTheRow('update', 0);
    $screen->whileItRuns();
    $screen->answer();
    $screen->approve(0);
    $screen->agree();
    $updating = WhatTheDeviceWouldDraw::by($screen);
    $screen->whileItRuns();
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($extending->asked())->toBe(['list', 'rehearse update:tdarr:tdarr', 'after:j-1', sprintf('update:tdarr:tdarr:%s:%s', APluginAsItArrives::AGREEMENT, APluginAsItArrives::APPROVAL), 'after:j-2'])
        ->and($updating->said())->toContain(aPluginsLine('plugins.working.update'))
        ->and($said)->toContain(aPluginsLine('plugins.not_updated'))
        ->and($said)->toContain(aPluginsLine('plugins.stopped', ['why' => 'The container would not start']))
        ->and($said)->toContain(aPluginsLine('plugins.restored.not_running', ['version' => '2.1.0']))
        ->and($said)->toContain('The volume was in use')
        ->and($said)->not->toContain(aPluginsLine('plugins.rehearsal'));
});

it('rehearses a removal with what it would leave unfilled, and says one whose record was not written is partial and not removed', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theRemovalReading()),
        HowExtendingItIsGoing::done(APluginAsItArrives::thePartialRemoval()),
    );
    $screen = thePluginsListed($extending);
    $screen->actOnTheRow('remove', 0);
    $screen->whileItRuns();
    $reading = WhatTheDeviceWouldDraw::by($screen);
    $screen->agree();
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($reading->said())->toContain(aPluginsLine('plugins.rehearsal'))
        ->and($reading->said())->toContain(aPluginsLine('plugins.unfilled_line', ['capability' => 'transcode', 'plugin' => 'tdarr']))
        ->and($reading->offers())->toContain(aPluginsLine('plugins.remove'))
        ->and($extending->asked())->toBe(['list', 'rehearse removal:tdarr', 'after:j-1', sprintf('remove:tdarr:%s', APluginAsItArrives::AGREEMENT)])
        ->and($said)->toContain(aPluginsLine('plugins.partly_removed'))
        ->and($said)->not->toContain(aPluginsLine('plugins.removed_it'))
        ->and($said)->toContain('What the service wrote stays for the new version')
        ->and($said)->not->toContain(aPluginsLine('plugins.rehearsal'));
});

it('sends the yes once, though the answer to it names an agreement again', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theRemovalReading()),
        HowExtendingItIsGoing::done(APluginAsItArrives::theRemovalReading()),
    );
    $screen = thePluginsListed($extending);
    $screen->actOnTheRow('remove', 0);
    $screen->whileItRuns();
    $screen->answer();
    $screen->agree();
    $screen->agree();

    expect($extending->asked())->toBe(['list', 'rehearse removal:tdarr', 'after:j-1', sprintf('remove:tdarr:%s', APluginAsItArrives::AGREEMENT)]);
});

it('offers no yes to an answer that names no agreement', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theListing()),
    );
    $screen = thePluginsListed($extending);
    $screen->actOnTheRow('remove', 0);
    $screen->whileItRuns();
    $screen->answer();
    $screen->agree();

    expect($extending->asked())->toBe(['list', 'rehearse removal:tdarr', 'after:j-1']);
});

it('rehearses the next act on a row as a rehearsal, after a yes to the last one came back', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theRemovalReading()),
        HowExtendingItIsGoing::done(APluginAsItArrives::theRemoval()),
        HowExtendingItIsGoing::underway(Job::named('j-2')),
    );
    $screen = thePluginsListed($extending);
    $screen->actOnTheRow('remove', 0);
    $screen->whileItRuns();
    $screen->answer();
    $screen->agree();
    $screen->actOnTheRow('update', 0);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aPluginsLine('plugins.working.rehearse'));
});

it('says removed only where the record was written, with what stayed on the machine and why', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theRemovalReading()),
        HowExtendingItIsGoing::done(APluginAsItArrives::theRemoval()),
    );
    $screen = thePluginsListed($extending);
    $screen->actOnTheRow('remove', 0);
    $screen->whileItRuns();
    $screen->answer();
    $screen->agree();
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(aPluginsLine('plugins.removed_it'))
        ->and($said)->not->toContain(aPluginsLine('plugins.partly_removed'))
        ->and($said)->toContain(__('stacks.run_back.did.not_all'))
        ->and($said)->toContain('/srv/lemonfiber/config/tdarr');
});

it('draws a removal the stack refuses after the yes as its refusal, said as a removal', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theRemovalReading()),
        aPluginRefusal('The offer moved on'),
    );
    $screen = thePluginsListed($extending);
    $screen->actOnTheRow('remove', 0);
    $screen->whileItRuns();
    $screen->answer();
    $screen->agree();
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(aPluginsLine('plugins.refused.remove'))
        ->and($said)->toContain('The offer moved on');
});

it('is in the menu beside Connections, and opens on its own path', function (): void {
    expect(TheMenu::Plugins->screen())->toBe(AStacksScreen::Plugins)
        ->and(TheMenu::Plugins->group())->toBe(TheMenu::Connections->group())
        ->and(AStacksScreen::Plugins->forTheStack(theMachinePluginsExtend()->id()))->toBe(sprintf('/stacks/%s/plugins', theMachinePluginsExtend()->id()->stored()));
});

it('says in every language why a service takes each privileged shape', function (APrivilegedShape $shape, string $locale): void {
    expect(app('translator')->has($shape->neededFor(), $locale, fallback: false))->toBeTrue();
})->with(APrivilegedShape::cases())->with(['en', 'nl']);
