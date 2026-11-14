<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AlertBackEndId;
use Modules\Kernel\Api\AlertBackEnds;
use Modules\Kernel\Api\AnAlertBackEnd;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARequestOfOurs;
use Modules\Kernel\Api\ARequestOfTheirs;
use Modules\Kernel\Api\ARequestOnAPluginsBehalf;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowDeliveryStands;
use Modules\Kernel\Api\HowFarItIsProven;
use Modules\Kernel\Api\HowItsDeliveriesWent;
use Modules\Kernel\Api\KindOfAlertBackEnd;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OurRequests;
use Modules\Kernel\Api\RequestsOnAPluginsBehalf;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheirRequests;
use Modules\Kernel\Api\UnreadBackEnds;
use Modules\Kernel\Api\WhatLeavesThisMachine;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhereAlertsGo;
use Modules\Kernel\Api\WhereItGoes;
use Modules\Kernel\Api\WhereItIsHosted;
use Modules\Kernel\Api\WhetherItIsAllowed;
use Modules\Kernel\Api\WhoChoseIt;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowWhatLeavesReads;
use Modules\Operator\Internal\Screens\WhatLeavesHere;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatSaysWhatLeavesIt;
use Tests\Support\Fakes\AStackThatSaysWhereAlertsGo;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Everything that leaves this machine, in three lists.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine whose connections this screen is about. */
function theStackWhoseConnectionsAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** Two of lemonfiber's requests, one made for a plugin, and three services, one of each kind of answer. */
function aMachineThatSendsThings(): WhatLeavesThisMachine
{
    return WhatLeavesThisMachine::of(
        OurRequests::of(
            ARequestOfOurs::described(WhatLemonfiberAsksFor::Registry, WhereItGoes::to('ghcr.io', 'lscr.io'), 'To fetch the images the stack runs', 'The name and version of each image', WhetherItIsAllowed::Allowed, 'registry.pull', 'No service can be installed or updated'),
            ARequestOfOurs::described(WhatLemonfiberAsksFor::Updates, WhereItGoes::to(), 'To say when a newer lemonfiber is out', 'Nothing but the request itself', WhetherItIsAllowed::SwitchedOff, 'updates.check', 'Nobody hears that a version came out'),
        ),
        RequestsOnAPluginsBehalf::of(ARequestOnAPluginsBehalf::declaredBy(APluginId::called('plex'), 'plex.tv', 'To claim the media server')),
        TheirRequests::of(
            ARequestOfTheirs::recorded(ServiceId::called('sonarr'), 'thetvdb.com', 'Series metadata', WhoPutItThere::bundled()),
            ARequestOfTheirs::recorded(ServiceId::called('gluetun'), '', 'Nothing of its own', WhoPutItThere::bundled()),
            ARequestOfTheirs::unrecorded(ServiceId::called('my-fork'), WhoPutItThere::bundled()),
        ),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theLeavingScreen(
    AStackThatSaysWhatLeavesIt $outgoing,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    ?AStackThatSaysWhereAlertsGo $alerts = null,
): WhatLeavesHere {
    $stack = theStackWhoseConnectionsAreRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $alerts ??= AStackThatSaysWhereAlertsGo::saying(WhereAlertsGo::read(HowDeliveryStands::Unconfigured, AlertBackEnds::these(), UnreadBackEnds::these()));
    $screen = new WhatLeavesHere($outgoing, $alerts, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('lemonfiber\'s requests, those it makes for a plugin, and the services\' come back as three lists', function (): void {
    $answer = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()))->answer();

    expect($answer->went->cameBack())->toBeTrue()
        ->and($answer->ours)->toHaveCount(2)
        ->and($answer->onBehalf)->toHaveCount(1)
        ->and($answer->theirs)->toHaveCount(3);
});

it('a request made for a plugin is drawn under its own heading, naming the plugin, why and where', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()));
    $plex = $screen->answer()->onBehalf[0];
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect([$plex->plugin, $plex->destination, $plex->purpose])->toBe(['plex', 'plex.tv', 'To claim the media server'])
        ->and($drawn)->toContain(__('stacks.outbound.on_behalf.heading'))
        ->and($drawn)->toContain(__('stacks.outbound.on_behalf.for', ['plugin' => 'plex']))
        ->and($drawn)->toContain('To claim the media server')
        ->and($drawn)->toContain(__('stacks.outbound.on_behalf.goes_to', ['destination' => 'plex.tv']))
        ->and($drawn)->not->toContain(__('stacks.outbound.on_behalf.none'));
});

it('a machine sending nothing for a plugin says so under that heading', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(WhatLeavesThisMachine::of(OurRequests::of(), RequestsOnAPluginsBehalf::of(), TheirRequests::of())));

    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('stacks.outbound.on_behalf.heading'))
        ->and($drawn)->toContain(__('stacks.outbound.on_behalf.none'));
});

it('each of lemonfiber\'s requests says why, what it sends, where, its switch and what turning it off costs', function (): void {
    $registry = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()))->answer()->ours[0];

    expect($registry->asksForSaid)->toBe(WhatLemonfiberAsksFor::Registry->saidOnTheScreen())
        ->and($registry->purpose)->toBe('To fetch the images the stack runs')
        ->and($registry->sends)->toBe('The name and version of each image')
        ->and($registry->destinations)->toBe(['ghcr.io', 'lscr.io'])
        ->and($registry->allowedSaid)->toBe(WhetherItIsAllowed::Allowed->saidOnTheScreen())
        ->and($registry->switch)->toBe('registry.pull')
        ->and($registry->cost)->toBe('No service can be installed or updated');
});

it('a request configured to reach nowhere is not a request switched off, and each is said', function (): void {
    $updates = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()))->answer()->ours[1];

    expect($updates->destinations)->toBe([])
        ->and($updates->allowedSaid)->toBe(WhetherItIsAllowed::SwitchedOff->saidOnTheScreen());
});

it('a service says where it goes, that it goes nowhere, or that nobody knows — three sentences, never two', function (): void {
    [$sonarr, $gluetun, $fork] = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()))->answer()->theirs;

    expect([$sonarr->service, $sonarr->reachesSaid, $sonarr->destination, $sonarr->purpose])->toBe(['sonarr', HowWhatLeavesReads::REACHES, 'thetvdb.com', 'Series metadata'])
        ->and([$gluetun->service, $gluetun->reachesSaid, $gluetun->destination, $gluetun->purpose])->toBe(['gluetun', HowWhatLeavesReads::REACHES_NOTHING, '', 'Nothing of its own'])
        ->and([$fork->service, $fork->reachesSaid, $fork->destination, $fork->purpose])->toBe(['my-fork', HowWhatLeavesReads::UNRECORDED, '', '']);
});

it('a machine sending nothing is an answer rather than a gap', function (): void {
    $answer = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(WhatLeavesThisMachine::of(OurRequests::of(), RequestsOnAPluginsBehalf::of(), TheirRequests::of())))->answer();

    expect($answer->went->cameBack())->toBeTrue()
        ->and($answer->ours)->toBe([])
        ->and($answer->onBehalf)->toBe([])
        ->and($answer->theirs)->toBe([]);
});

it('a stack that could not be asked is not a machine sending nothing', function (): void {
    $answer = theLeavingScreen(AStackThatSaysWhatLeavesIt::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)))->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($answer->ours)->toBe([])
        ->and($answer->onBehalf)->toBe([])
        ->and($answer->theirs)->toBe([]);
});

it('an obstacle that is not a refused credential leaves the session standing', function (): void {
    $answer = theLeavingScreen(AStackThatSaysWhatLeavesIt::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)))->answer();

    expect($answer->went->isSignedIn)->toBeTrue();
});

it('a session that has ended is not a machine sending nothing', function (): void {
    $answer = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()), signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->ours)->toBe([])
        ->and($answer->onBehalf)->toBe([])
        ->and($answer->theirs)->toBe([]);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain);

    expect($keychain->isHolding(theStackWhoseConnectionsAreRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        ->and($keychain->isHolding(theStackWhoseConnectionsAreRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    $outgoing = AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings());
    $screen = theLeavingScreen($outgoing);

    $screen->answer();
    $screen->answer();

    expect($outgoing->askings())->toBe(1)
        ->and($outgoing->wasGivenASession())->toBeTrue()
        ->and($outgoing->askedAbout()?->id()->stored())->toBe(theStackWhoseConnectionsAreRead()->id()->stored());
});

it('asking again asks the machine again', function (): void {
    $outgoing = AStackThatSaysWhatLeavesIt::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork));
    $screen = theLeavingScreen($outgoing);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($outgoing->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()));

    expect(NativeRouter::resolve($screen->goes()->to(AStacksScreen::Health)))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()));

    expect($screen->render()->name())->toBe('operator::what-leaves-here');
});

/**
 * A machine whose services include a plugin's, after one of the stack's own.
 *
 * The stack's own first, so a screen deciding from the first row alone gets
 * this wrong; the plugin's service unrecorded, which is how one arrives.
 */
function aMachineWithAPluginsService(WhoPutItThere $second): WhatLeavesThisMachine
{
    return WhatLeavesThisMachine::of(
        OurRequests::of(),
        RequestsOnAPluginsBehalf::of(),
        TheirRequests::of(
            ARequestOfTheirs::recorded(ServiceId::called('sonarr'), 'thetvdb.com', 'Series metadata', WhoPutItThere::bundled()),
            ARequestOfTheirs::unrecorded(ServiceId::called('plex'), $second),
        ),
    );
}

it('a plugin\'s service says who brought it, and the list says once what an unmarked row is', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineWithAPluginsService(WhoPutItThere::plugin('plex'))));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->answer()->marksAnOrigin())->toBeTrue()
        ->and($screen->answer()->theirs[0]->from->isTheStacksOwn())->toBeTrue()
        ->and($screen->answer()->theirs[1]->from->attributed)->toBe('plex')
        ->and($drawn)->toContain(__('stacks.outbound.theirs.origin.plugin', ['named' => 'plex']))
        ->and($drawn)->toContain(__('stacks.outbound.theirs.origin.legend'))
        ->and($drawn)->not->toContain(__('stacks.outbound.theirs.origin.bundled'));
});

it('a service nobody could attribute is marked with the stack\'s reason', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineWithAPluginsService(WhoPutItThere::unknown('its plugin was removed'))));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())
        ->toContain(__('stacks.outbound.theirs.origin.unknown', ['why' => 'its plugin was removed']));
});

it('a list of only the stack\'s own services marks none and explains nothing', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineWithAPluginsService(WhoPutItThere::bundled())));

    expect($screen->answer()->marksAnOrigin())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('stacks.outbound.theirs.origin.legend'));
});

function aBackEndHostedAt(string $id, WhereItIsHosted $hosted): AnAlertBackEnd
{
    return AnAlertBackEnd::configured(AlertBackEndId::called($id), KindOfAlertBackEnd::Ntfy, sprintf('%s/alerts', $id), WhoChoseIt::theOperator(), $hosted, HowFarItIsProven::unproven(), HowItsDeliveriesWent::nothingYet());
}

it('a hosted back-end alerts go to is listed under services you chose, with what it sends, and never among lemonfiber\'s own', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()), alerts: AStackThatSaysWhereAlertsGo::saying(WhereAlertsGo::read(
        HowDeliveryStands::Ready,
        AlertBackEnds::these(
            aBackEndHostedAt('ntfy-cloud', WhereItIsHosted::at('ntfy.sh', 'The alert\'s title and text', 'This machine\'s address')),
            aBackEndHostedAt('ntfy-home', WhereItIsHosted::notHosted()),
        ),
        UnreadBackEnds::these(),
    )));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->hosted()->hosts)->toHaveCount(1)
        ->and($screen->hosted()->hosts[0]->host)->toBe('ntfy.sh')
        ->and($screen->hosted()->hosts[0]->leaves)->toBe(['The alert\'s title and text', 'This machine\'s address'])
        ->and($screen->answer()->ours)->toHaveCount(2)
        ->and($drawn)->toContain(__('alerts.hosted.heading'))
        ->and($drawn)->toContain('ntfy.sh')
        ->and($drawn)->toContain('This machine\'s address')
        ->and($drawn)->not->toContain('ntfy-home')
        ->and($drawn)->not->toContain(__('alerts.hosted.none'));
});

it('no hosted back-end says so under services you chose', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings())))->said();

    expect($drawn)->toContain(__('alerts.hosted.heading'))
        ->and($drawn)->toContain(__('alerts.hosted.none'));
});

it('a hosted back-end listing nothing it sends says that, rather than nothing', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()), alerts: AStackThatSaysWhereAlertsGo::saying(WhereAlertsGo::read(
        HowDeliveryStands::Ready,
        AlertBackEnds::these(aBackEndHostedAt('ntfy-cloud', WhereItIsHosted::at('ntfy.sh'))),
        UnreadBackEnds::these(),
    )));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('alerts.hosted.sends_nothing'));
});

it('where alerts go that could not be read is said under services you chose, and the rest of the screen still draws', function (): void {
    $alerts = AStackThatSaysWhereAlertsGo::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()), alerts: $alerts);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->hosted()->went->cameBack())->toBeFalse()
        ->and($drawn)->toContain(__('stacks.outbound.ours.heading'))
        ->and($drawn)->not->toContain(__('alerts.hosted.none'))
        ->and($alerts->askings)->toBe(1);
});

it('where alerts go is not asked where this phone holds no session for the machine', function (): void {
    $alerts = AStackThatSaysWhereAlertsGo::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));

    expect(theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()), signedIn: false, alerts: $alerts)->hosted()->went->isSignedIn)->toBeFalse()
        ->and($alerts->askings)->toBe(0);
});
