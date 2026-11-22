<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACatalogueEntry;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ASettingThatReaches;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowExtendingItIsGoing;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\ProposedChange;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\ThePluginCatalogue;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatItHoldsNow;
use Modules\Kernel\Api\WhatToSet;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Operator\Internal\Presenters\AgoAsShown;
use Modules\Operator\Internal\Screens\WhatExtendsThisStack;
use Tests\Support\APluginAsItArrives;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AStackThatExtendsItself;
use Tests\Support\Fakes\AStackToldToChangeSomething;
use Tests\Support\ThePluginsScreen;
use Tests\Support\WhatTheDeviceWouldDraw;

// Choosing a plugin from the stack's verified catalogue, above the typed
// source: what each entry is, rehearsed as a typed name is, and reaching the
// catalogue turned on through the stack's review where it is off.

const THE_REACH_KEY = 'LEMONFIBER_REACH_CATALOGUE';

/** The machine whose catalogue is read. */
function theMachineWhoseCatalogueIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.43:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** The moment every frame here is drawn at. */
function theCataloguesFrame(): Instant
{
    return AroundThePhone::listening()->clock->now();
}

/** Three days before it, when the catalogue's index was published. */
function whenTheIndexWasPublished(): Instant
{
    return Instant::atEpochSeconds(theCataloguesFrame()->epochSeconds() - (3 * 86_400));
}

/** The catalogue as the stack lists it: one plugin not installed, Tdarr installed at an older version, and one entry it could not list. */
function theCatalogueListed(): ThePluginCatalogue
{
    return ThePluginCatalogue::listed(
        whenTheIndexWasPublished(),
        12,
        1,
        ACatalogueEntry::offered('bazarr', 'Bazarr', 'Fetches subtitles for what you watch', '1.4.0', 'Nothing comes with subtitles'),
        ACatalogueEntry::installedHere('tdarr', 'Tdarr', 'Transcodes what comes in', '2.2.0', 'Files stay as they arrive', '2.1.0', newer: true),
    );
}

/** The catalogue switched off by the setting the stack names. */
function theCatalogueSwitchedOff(): ThePluginCatalogue
{
    return ThePluginCatalogue::switchedOff(ASettingThatReaches::named(THE_REACH_KEY, 'on', 'config-set'));
}

/** A stack listing Tdarr installed, whose catalogue answers as given, and that answers the work with these. */
function aStackCataloguing(ThePluginCatalogue $catalogue, HowExtendingItIsGoing ...$answers): AStackThatExtendsItself
{
    return AStackThatExtendsItself::listing(APluginAsItArrives::theListing(), ...$answers)->cataloguing($catalogue);
}

/** The screen with a source being typed, the catalogue above it read. */
function theCatalogueOpen(AStackThatExtendsItself $extending, ?AStackToldToChangeSomething $adjusting = null): WhatExtendsThisStack
{
    $screen = ThePluginsScreen::on(theMachineWhoseCatalogueIsRead(), $extending, adjusting: $adjusting);
    $screen->installOne();

    return $screen;
}

/**
 * A line of the catalogue, filled in.
 *
 * @param array<string, int|string> $with
 */
function aCatalogueLine(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : '';
}

/** What the stack says turning reach on would come to. */
function reachWouldBeTurnedOn(): AStackToldToChangeSomething
{
    return AStackToldToChangeSomething::saying(WhereTheChangeStands::at(
        ProposedChange::of(THE_REACH_KEY, 'on', WhatItHoldsNow::shown('off'), Cost::Cheap),
        Stance::Pending,
        WhatAChangeComesTo::nothing(),
    ));
}

it('lists each entry above the typed source with what it is, what the household is without it, its version, and whether it is installed', function (): void {
    $extending = aStackCataloguing(theCatalogueListed());
    $drawn = WhatTheDeviceWouldDraw::by(theCatalogueOpen($extending));
    $said = $drawn->said();
    $published = AgoAsShown::since(whenTheIndexWasPublished(), theCataloguesFrame());

    expect($published->count)->toBe(3)
        ->and($said)->toContain(aCatalogueLine('plugins.catalogue.heading'))
        ->and($said)->toContain(aCatalogueLine('plugins.catalogue.published', ['when' => trans_choice($published->said, $published->count)]))
        ->and($said)->toContain(aCatalogueLine('plugins.named', ['name' => 'Bazarr', 'version' => '1.4.0']))
        ->and($said)->toContain('Fetches subtitles for what you watch')
        ->and($said)->toContain(aCatalogueLine('plugins.catalogue.without_it', ['without' => 'Nothing comes with subtitles']))
        ->and($said)->toContain(aCatalogueLine('plugins.named', ['name' => 'Tdarr', 'version' => '2.2.0']))
        ->and($said)->toContain(aCatalogueLine('plugins.catalogue.installed', ['version' => '2.1.0']))
        ->and($said)->toContain(aCatalogueLine('plugins.catalogue.newer', ['version' => '2.2.0']))
        ->and($said)->toContain(aCatalogueLine('plugins.catalogue.unreadable', ['count' => 1]))
        ->and($said)->toContain(aCatalogueLine('plugins.source_catalogue'))
        ->and($drawn->offers())->toContain(aCatalogueLine('plugins.catalogue.choose', ['name' => 'Bazarr']))
        ->and($drawn->offers())->not->toContain(aCatalogueLine('plugins.catalogue.choose', ['name' => 'Tdarr']))
        ->and($drawn->offers())->toContain(aCatalogueLine('plugins.settings.open', ['name' => 'Tdarr']))
        ->and($drawn->offers())->toContain(aCatalogueLine('plugins.rehearse'))
        ->and($extending->cataloguesRead())->toBe(1)
        ->and($extending->asked())->toBe([]);
});

it('rehearses the entry chosen as a typed name is rehearsed, and installs only on the yes to that rehearsal', function (): void {
    $extending = aStackCataloguing(theCatalogueListed(), HowExtendingItIsGoing::done(APluginAsItArrives::theReading()), HowExtendingItIsGoing::underway(Job::named('j-2')));
    $screen = theCatalogueOpen($extending);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->choose(0);
    $rehearsed = WhatTheDeviceWouldDraw::by($screen);
    $askedBeforeTheYes = $extending->asked();
    $screen->agree();

    expect($askedBeforeTheYes)->toBe(['rehearse:bazarr'])
        ->and($screen->source)->toBe('bazarr')
        ->and($rehearsed->said())->toContain(aCatalogueLine('plugins.rehearsal'))
        ->and($extending->asked())->toBe(['rehearse:bazarr', sprintf('install:bazarr:%s:', APluginAsItArrives::AGREEMENT)]);
});

it('chooses nothing for a place the catalogue does not have, for an entry installed already, or before the catalogue is read', function (): void {
    $extending = aStackCataloguing(theCatalogueListed());
    $screen = theCatalogueOpen($extending);
    $screen->choose(0);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->choose(2);
    $screen->choose(-1);
    $screen->choose(1);

    expect($extending->asked())->toBe([])
        ->and($screen->source)->toBe('');
});

it('says a catalogue listing nothing is empty, with the typed source still below it', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCatalogueOpen(aStackCataloguing(ThePluginCatalogue::listed(theCataloguesFrame(), 12, 0))));

    expect($drawn->said())->toContain(aCatalogueLine('plugins.catalogue.empty'))
        ->and($drawn->said())->not->toContain(aCatalogueLine('plugins.catalogue.unreadable', ['count' => 0]))
        ->and($drawn->offers())->toContain(aCatalogueLine('plugins.rehearse'));
});

it('says a catalogue not reached in the stack\'s words and code, offers reading it again, and keeps the typed source', function (): void {
    $extending = aStackCataloguing(ThePluginCatalogue::unreached('catalogue-signature', 'The index signature did not verify'));
    $screen = theCatalogueOpen($extending);
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->catalogueAgain();
    WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(aCatalogueLine('plugins.catalogue.failed', ['summary' => 'The index signature did not verify', 'code' => 'catalogue-signature']))
        ->and($drawn->said())->not->toContain(aCatalogueLine('plugins.catalogue.empty'))
        ->and($drawn->offers())->toContain(aCatalogueLine('plugins.catalogue.again'))
        ->and($drawn->offers())->toContain(aCatalogueLine('plugins.rehearse'))
        ->and($screen->typing)->toBeTrue()
        ->and($extending->cataloguesRead())->toBe(2);
});

it('says what stood in the way of a stack that offers no catalogue, and keeps the typed source', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCatalogueOpen(AStackThatExtendsItself::listing(APluginAsItArrives::theListing())));

    expect($drawn->said())->toContain(__(KindOfObstacle::NotOnThisStack->said()))
        ->and($drawn->said())->not->toContain(aCatalogueLine('plugins.catalogue.empty'))
        ->and($drawn->offers())->toContain(aCatalogueLine('plugins.rehearse'));
});

it('says reach is switched off by the setting the stack named, and switches nothing before it is offered', function (): void {
    $adjusting = reachWouldBeTurnedOn();
    $drawn = WhatTheDeviceWouldDraw::by(theCatalogueOpen(aStackCataloguing(theCatalogueSwitchedOff()), $adjusting));

    expect($drawn->said())->toContain(aCatalogueLine('plugins.catalogue.off', ['key' => THE_REACH_KEY]))
        ->and($drawn->offers())->toContain(aCatalogueLine('plugins.catalogue.turn_on'))
        ->and($drawn->offers())->toContain(aCatalogueLine('plugins.rehearse'))
        ->and($adjusting->rehearsals())->toBe(0)
        ->and($adjusting->writes())->toBe(0);
});

it('turns reach on with the key and value the stack named, rehearsed, shown, agreed to, and the catalogue read again on a frame of its own', function (): void {
    $adjusting = reachWouldBeTurnedOn();
    $extending = aStackCataloguing(theCatalogueSwitchedOff());
    $screen = theCatalogueOpen($extending, $adjusting);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->change(THE_REACH_KEY);
    $opened = WhatTheDeviceWouldDraw::by($screen);
    $screen->wouldBe(THE_REACH_KEY);
    $shown = WhatTheDeviceWouldDraw::by($screen);
    $rehearsed = $adjusting->askedFor();
    $screen->agreeToTheChange(THE_REACH_KEY);
    $readsAfterTheYes = $extending->cataloguesRead();
    WhatTheDeviceWouldDraw::by($screen);
    $readsOnTheFrameAfter = $extending->cataloguesRead();
    WhatTheDeviceWouldDraw::by($screen);

    expect($opened->offers())->toContain(aCatalogueLine('config.what_would_happen'))
        ->and($rehearsed)->toEqual(WhatToSet::to(THE_REACH_KEY, 'on'))
        ->and($shown->said())->toContain(aCatalogueLine('config.would_hold', ['value' => 'on']))
        ->and($shown->offers())->toContain(aCatalogueLine('config.agree'))
        ->and($adjusting->rehearsals())->toBe(1)
        ->and($adjusting->writes())->toBe(1)
        ->and($adjusting->askedFor())->toEqual(WhatToSet::to(THE_REACH_KEY, 'on'))
        ->and($readsAfterTheYes)->toBe(1)
        ->and($readsOnTheFrameAfter)->toBe(1)
        ->and($extending->cataloguesRead())->toBe(2)
        ->and($extending->asked())->toBe([]);
});

it('opens no setting but the one the stack named, and agrees to nothing that was not opened', function (): void {
    $adjusting = reachWouldBeTurnedOn();
    $screen = theCatalogueOpen(aStackCataloguing(theCatalogueSwitchedOff()), $adjusting);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->agreeToTheChange(THE_REACH_KEY);
    $screen->change('LEMONFIBER_DATA_ROOT');
    $screen->wouldBe('LEMONFIBER_DATA_ROOT');
    $screen->agreeToTheChange('LEMONFIBER_DATA_ROOT');

    expect($screen->changing())->toBe('')
        ->and($adjusting->rehearsals())->toBe(0)
        ->and($adjusting->writes())->toBe(0);
});

it('opens no setting where the catalogue is listed, so a reach it never named is never written', function (): void {
    $adjusting = reachWouldBeTurnedOn();
    $screen = theCatalogueOpen(aStackCataloguing(theCatalogueListed()), $adjusting);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->change(THE_REACH_KEY);
    $screen->wouldBe(THE_REACH_KEY);
    $screen->agreeToTheChange(THE_REACH_KEY);

    expect($screen->changing())->toBe('')
        ->and($adjusting->rehearsals())->toBe(0)
        ->and($adjusting->writes())->toBe(0);
});

it('reads the catalogue on the frame after the listing, once, and draws the newer version on the installed row', function (): void {
    $extending = aStackCataloguing(theCatalogueListed());
    $screen = ThePluginsScreen::on(theMachineWhoseCatalogueIsRead(), $extending);
    $first = WhatTheDeviceWouldDraw::by($screen);
    $readsOnTheFirstFrame = $extending->cataloguesRead();
    $waitedOnTheFirstFrame = $screen->waitsForTheNextFrame();
    $second = WhatTheDeviceWouldDraw::by($screen);
    $readsOnTheSecondFrame = $extending->cataloguesRead();
    WhatTheDeviceWouldDraw::by($screen);

    expect($readsOnTheFirstFrame)->toBe(0)
        ->and($waitedOnTheFirstFrame)->toBeTrue()
        ->and($first->said())->not->toContain(aCatalogueLine('plugins.catalogue.newer', ['version' => '2.2.0']))
        ->and($readsOnTheSecondFrame)->toBe(1)
        ->and($screen->waitsForTheNextFrame())->toBeFalse()
        ->and($second->said())->toContain(aCatalogueLine('plugins.catalogue.newer', ['version' => '2.2.0']))
        ->and($second->said())->not->toContain(aCatalogueLine('plugins.catalogue.heading'))
        ->and($extending->cataloguesRead())->toBe(1)
        ->and($extending->asked())->toBe(['list']);
});

it('says on the installed rows that newer versions could not be read where the catalogue was not, and never that they are up to date', function (ThePluginCatalogue $catalogue, string $why): void {
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame(ThePluginsScreen::on(theMachineWhoseCatalogueIsRead(), aStackCataloguing($catalogue)))->said();

    expect($said)->toContain(aCatalogueLine('plugins.catalogue.newer_unread', ['why' => $why]))
        ->and($said)->not->toContain(aCatalogueLine('plugins.catalogue.newer', ['version' => '2.2.0']));
})->with([
    'switched off' => [fn(): ThePluginCatalogue => ThePluginCatalogue::switchedOff(ASettingThatReaches::named(THE_REACH_KEY, 'on', 'config-set')), fn(): string => aCatalogueLine('plugins.catalogue.off', ['key' => THE_REACH_KEY])],
    'not reached' => [fn(): ThePluginCatalogue => ThePluginCatalogue::unreached('catalogue-stale', 'An older index than one verified here'), fn(): string => aCatalogueLine('plugins.catalogue.failed', ['summary' => 'An older index than one verified here', 'code' => 'catalogue-stale'])],
]);

it('says nothing of newer versions on the installed rows where the catalogue holds none', function (): void {
    $catalogue = ThePluginCatalogue::listed(theCataloguesFrame(), 12, 0, ACatalogueEntry::installedHere('tdarr', 'Tdarr', 'Transcodes what comes in', '2.1.0', 'Files stay as they arrive', '2.1.0', newer: false));
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame(ThePluginsScreen::on(theMachineWhoseCatalogueIsRead(), aStackCataloguing($catalogue)))->said();

    expect($said)->not->toContain(aCatalogueLine('plugins.catalogue.newer', ['version' => '2.1.0']))
        ->and(implode("\n", $said))->not->toContain(aCatalogueLine('plugins.catalogue.newer_unread', ['why' => '']));
});

it('reads no catalogue while an act is followed', function (): void {
    $extending = aStackCataloguing(theCatalogueListed(), HowExtendingItIsGoing::underway(Job::named('j-1')), HowExtendingItIsGoing::underway(Job::named('j-1')));
    $screen = theCatalogueOpen($extending);
    $screen->source = 'tdarr';
    $screen->rehearse();
    WhatTheDeviceWouldDraw::by($screen);
    $screen->whileItRuns();
    WhatTheDeviceWouldDraw::by($screen);

    expect($screen->followingTheAct)->toBe('j-1')
        ->and($screen->catalogue()->isBeingRead())->toBeTrue()
        ->and($extending->cataloguesRead())->toBe(0);
});

it('reads the catalogue again after going back, so what is installed is what it says', function (): void {
    $extending = aStackCataloguing(theCatalogueListed());
    $screen = theCatalogueOpen($extending);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->backToThePlugins();
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect($extending->cataloguesRead())->toBe(2);
});

it('reads the catalogue on the frame after the one where an act it followed came back to the listing', function (): void {
    $extending = aStackCataloguing(theCatalogueListed(), HowExtendingItIsGoing::underway(Job::named('j-1')), HowExtendingItIsGoing::done(APluginAsItArrives::theListing()));
    $screen = theCatalogueOpen($extending);
    $screen->source = 'tdarr';
    $screen->rehearse();
    $screen->whileItRuns();
    $cameBack = WhatTheDeviceWouldDraw::by($screen);
    $readsOnTheFrameItCameBack = $extending->cataloguesRead();
    $waited = $screen->waitsForTheNextFrame();
    WhatTheDeviceWouldDraw::by($screen);

    expect($extending->asked())->toBe(['rehearse:tdarr', 'after:j-1'])
        ->and($screen->followingTheAct)->toBeNull()
        ->and($cameBack->said())->toContain(aCatalogueLine('plugins.named', ['name' => 'Tdarr', 'version' => '2.1.0']))
        ->and($readsOnTheFrameItCameBack)->toBe(0)
        ->and($waited)->toBeTrue()
        ->and($extending->cataloguesRead())->toBe(1);
});
