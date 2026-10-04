<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AServiceDropped;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCatalogue;
use Modules\Kernel\Api\WhatAServiceIsFor;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheServicesAreFor;
use Modules\Kernel\Api\WhatWasDropped;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatEachServiceIsFor;
use Modules\Operator\Internal\ViewModels\AServiceAsCatalogued;
use Modules\Operator\Internal\ViewModels\AServiceDroppedAsShown;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatCatalogues;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\NoticingWhatIsNew;
use Tests\Support\WhatTheDeviceWouldDraw;

// What each service on this machine is for, and what became of any the stack
// dropped.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.

/** The machine whose catalogue this screen is about. */
function theStackWhoseCatalogueIsShown(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A catalogue of two services and two dropped, one of them replaced. */
function aCatalogueOfTwo(): TheCatalogue
{
    return TheCatalogue::of(
        WhatTheServicesAreFor::these(
            WhatAServiceIsFor::declared(ServiceId::called('sonarr'), 'Sonarr', 'Finds and fetches television', 'New episodes stop arriving', HowMuchItMatters::Important),
            WhatAServiceIsFor::declared(ServiceId::called('bazarr'), 'Bazarr', 'Finds subtitles', 'Nothing has subtitles', HowMuchItMatters::Optional),
        ),
        WhatWasDropped::these(
            AServiceDropped::replaced(ServiceId::called('ombi'), '0.8.0', 'Requests moved into the household app', 'jellyseerr'),
            AServiceDropped::went(ServiceId::called('lidarr'), '0.9.0', 'Music is handled elsewhere'),
        ),
    );
}

/**
 * Where on the frame a line is said, counted from the top; raised where it is not said at all.
 *
 * @param list<string> $said
 */
function whereTheCatalogueSays(array $said, string $line): int
{
    $at = array_search($line, $said, strict: true);

    if (! is_int($at)) {
        throw new RuntimeException(sprintf('The frame does not say `%s`.', $line));
    }

    return $at;
}

/** The screen, with a stack it knows and a keychain holding whatever a case says. */
function theCatalogueScreen(AStackThatCatalogues $catalogue, ?AKeychainInMemory $keychain = null, bool $signedIn = true): WhatEachServiceIsFor
{
    $stack = theStackWhoseCatalogueIsShown();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatEachServiceIsFor($catalogue, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening(), NoticingWhatIsNew::fromNothing());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('says of every service what it does for the house, what the house goes without and how much that matters', function (): void {
    $screen = theCatalogueScreen(AStackThatCatalogues::with(aCatalogueOfTwo()));
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(array_map(
        static fn(AServiceAsCatalogued $service): array => [$service->name, $service->describes, $service->withoutIt, $service->mattersSaid],
        $screen->answer()->services,
    ))->toBe([
        ['Sonarr', 'Finds and fetches television', 'New episodes stop arriving', HowMuchItMatters::Important->saidOnTheScreen()],
        ['Bazarr', 'Finds subtitles', 'Nothing has subtitles', HowMuchItMatters::Optional->saidOnTheScreen()],
    ])
        ->and($said)->toContain(__('stacks.catalogue.as_declared'))
        ->and($said)->toContain(__('stacks.catalogue.without_it', ['without' => 'New episodes stop arriving']))
        ->and($said)->toContain(__(HowMuchItMatters::Optional->saidOnTheScreen()))
        ->and(whereTheCatalogueSays($said, 'Finds and fetches television'))->toBeLessThan(whereTheCatalogueSays($said, 'Sonarr'));
});

it('names what took a dropped service\'s place, and says so where nothing did', function (): void {
    $screen = theCatalogueScreen(AStackThatCatalogues::with(aCatalogueOfTwo()));
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(array_map(
        static fn(AServiceDroppedAsShown $dropped): array => [$dropped->id, $dropped->removedIn, $dropped->reason, $dropped->replacedBy],
        $screen->answer()->dropped,
    ))->toBe([
        ['ombi', '0.8.0', 'Requests moved into the household app', 'jellyseerr'],
        ['lidarr', '0.9.0', 'Music is handled elsewhere', ''],
    ])
        ->and($said)->toContain(__('stacks.catalogue.removed_in', ['version' => '0.8.0', 'reason' => 'Requests moved into the household app']))
        ->and($said)->toContain(__('stacks.catalogue.replaced_by', ['by' => 'jellyseerr']))
        ->and($said)->toContain(__('stacks.catalogue.not_replaced'))
        ->and($said)->not->toContain(__('stacks.catalogue.nothing_dropped'));
});

it('says a stack that declares nothing and has dropped nothing does, which is an answer', function (): void {
    $screen = theCatalogueScreen(AStackThatCatalogues::with(TheCatalogue::of(WhatTheServicesAreFor::these(), WhatWasDropped::these())));
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->answer()->went->cameBack())->toBeTrue()
        ->and($said)->toContain(__('stacks.catalogue.nothing_declared'))
        ->and($said)->toContain(__('stacks.catalogue.nothing_dropped'));
});

it('a stack that could not be asked is not a stack that declares nothing', function (): void {
    $screen = theCatalogueScreen(AStackThatCatalogues::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->answer()->went->cameBack())->toBeFalse()
        ->and($screen->answer()->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($screen->answer()->services)->toBe([])
        ->and($screen->answer()->dropped)->toBe([])
        ->and($said)->not->toContain(__('stacks.catalogue.nothing_declared'));
});

it('says a stack that cannot read its own description in its words, apart from one that could not be asked, and does not offer asking again', function (): void {
    $why = ARefusalInItsWords::said(
        'This stack file could not be read',
        'A stack.toml is written in a strict format, and this one breaks it — so nothing in the file has been read at all.',
        WhatTheRefusalNamed::as('expected `=`, found newline at line 3 column 9'),
    );
    $keychain = AKeychainInMemory::working();
    $screen = theCatalogueScreen(AStackThatCatalogues::refusing($why), $keychain);
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $unreachable = WhatTheDeviceWouldDraw::by(theCatalogueScreen(AStackThatCatalogues::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));

    expect($screen->answer()->went->cameBack())->toBeTrue()
        ->and($screen->answer()->refused?->said)->toBe('This stack file could not be read')
        ->and($screen->answer()->refused?->named)->toBe('expected `=`, found newline at line 3 column 9')
        ->and($screen->answer()->services)->toBe([])
        ->and($drawn->said())->toContain(__('stacks.catalogue.refused'))
        ->and($drawn->said())->toContain('This stack file could not be read')
        ->and($drawn->said())->toContain('A stack.toml is written in a strict format, and this one breaks it — so nothing in the file has been read at all.')
        ->and($drawn->said())->toContain(__('stacks.refusal.named', ['named' => 'expected `=`, found newline at line 3 column 9']))
        ->and($drawn->said())->toContain(__('stacks.catalogue.same_answer'))
        ->and($drawn->said())->not->toContain(__('stacks.catalogue.nothing_declared'))
        ->and($drawn->said())->not->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($drawn->offers())->not->toContain(__('health.ask_again'))
        ->and($unreachable->said())->not->toContain(__('stacks.catalogue.refused'))
        ->and($unreachable->offers())->toContain(__('health.ask_again'))
        ->and($keychain->isHolding(theStackWhoseCatalogueIsShown()->id()))->toBeTrue();
});

it('asks for a session where this device holds none, and asks the stack nothing', function (): void {
    $catalogue = AStackThatCatalogues::with(aCatalogueOfTwo());
    $screen = theCatalogueScreen($catalogue, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($catalogue->askings())->toBe(0)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('connection.sign_in'));
});

it('lets go of a session the stack refused', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theCatalogueScreen(AStackThatCatalogues::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseCatalogueIsShown()->id()))->toBeFalse();
});

it('asks the machine the route names once a frame, and again when asked to', function (): void {
    $catalogue = AStackThatCatalogues::with(aCatalogueOfTwo());
    $screen = theCatalogueScreen($catalogue);

    $screen->answer();
    $screen->answer();

    expect($catalogue->askings())->toBe(1)
        ->and($catalogue->askedAbout()?->id()->stored())->toBe(theStackWhoseCatalogueIsShown()->id()->stored());

    $screen->again();
    $screen->answer();

    expect($catalogue->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theCatalogueScreen(AStackThatCatalogues::with(aCatalogueOfTwo()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('renders its own view, is reached from the machine, and the way back is a route', function (): void {
    $screen = theCatalogueScreen(AStackThatCatalogues::with(aCatalogueOfTwo()));

    expect($screen->render()->name())->toBe('operator::what-each-service-is-for')
        ->and(NativeRouter::resolve($screen->goes()->ofItself()->catalogue()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});
