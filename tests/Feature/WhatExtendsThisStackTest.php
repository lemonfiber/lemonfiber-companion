<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\APlugin;
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
use Modules\Kernel\Api\TheInstalledPlugins;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\ThePluginSources;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatExtendsThisStack;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Internal\TheMenu;
use Tests\Support\APluginAsItArrives;
use Tests\Support\AroundThePhone;
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
function thePluginsScreen(AStackThatExtendsItself $extending, ?AKeychainInMemory $keychain = null): WhatExtendsThisStack
{
    $stack = theMachinePluginsExtend();
    $keychain ??= AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new WhatExtendsThisStack($extending, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
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
        ->and($drawn->offers())->toContain(aPluginsLine('plugins.install_one'));
});

it('says no plugins are installed where the record holds none, and that the record could not be read where it could not', function (): void {
    $none = WhatTheDeviceWouldDraw::by(thePluginsScreen(AStackThatExtendsItself::listing(ThePlugins::listed(TheInstalledPlugins::these(), ThePluginSources::these()))));
    $unread = WhatTheDeviceWouldDraw::by(thePluginsScreen(AStackThatExtendsItself::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));

    expect($none->said())->toContain(aPluginsLine('plugins.none'))
        ->and($unread->said())->not->toContain(aPluginsLine('plugins.none'))
        ->and($unread->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));
});

it('asks for a source in one field, with its three shapes under it, and asks nothing for a blank one', function (): void {
    $extending = aStackWithTdarr();
    $screen = thePluginsScreen($extending);
    $screen->installOne();
    $drawn = WhatTheDeviceWouldDraw::by($screen);
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
        ->and($said)->toContain(aPluginsLine('plugins.stays_here'))
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
    $screen = new WhatExtendsThisStack($extending, AKeychainInMemory::working(), AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    expect($screen->answer()->went->cameBack())->toBeFalse()
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
    $extending = AStackThatExtendsItself::listing(ThePlugins::listed(TheInstalledPlugins::these($sourceless), ThePluginSources::these()));
    $screen = thePluginsListed($extending);
    $cannot = WhatTheDeviceWouldDraw::by($screen);
    $screen->updateOne(0);
    $screen->updateOne(4);
    $screen->removeOne(4);

    expect($drawn->offers())->toContain(aPluginsLine('plugins.update_it', ['name' => 'Tdarr']))
        ->and($drawn->offers())->toContain(aPluginsLine('plugins.remove_it', ['name' => 'Tdarr']))
        ->and($drawn->said())->not->toContain(aPluginsLine('plugins.no_source_to_update'))
        ->and($cannot->offers())->not->toContain(aPluginsLine('plugins.update_it', ['name' => 'Tdarr']))
        ->and($cannot->offers())->toContain(aPluginsLine('plugins.remove_it', ['name' => 'Tdarr']))
        ->and($cannot->said())->toContain(aPluginsLine('plugins.no_source_to_update'))
        ->and($extending->asked())->toBe(['list']);
});

it('rehearses an update and draws it as a rehearsal: from and to, what stops, what comes off, and the new version', function (): void {
    $extending = aStackWithTdarr(HowExtendingItIsGoing::underway(Job::named('j-1')), HowExtendingItIsGoing::done(APluginAsItArrives::theUpdateReading()));
    $screen = thePluginsListed($extending);
    $screen->updateOne(0);
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
    $screen->updateOne(0);
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
    $screen->removeOne(0);
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

it('says removed only where the record was written, with what stayed on the machine and why', function (): void {
    $extending = aStackWithTdarr(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theRemovalReading()),
        HowExtendingItIsGoing::done(APluginAsItArrives::theRemoval()),
    );
    $screen = thePluginsListed($extending);
    $screen->removeOne(0);
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
    $screen->removeOne(0);
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
