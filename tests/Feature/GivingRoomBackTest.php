<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACategoryOffered;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheAccount;
use Modules\Kernel\Api\TheDownloadsOnDisk;
use Modules\Kernel\Api\TheRoomGivenBack;
use Modules\Kernel\Api\TheRoomOnOffer;
use Modules\Kernel\Api\TheVolumes;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItOccupies;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhereTheRoomStands;
use Modules\Kernel\Api\WhereTheRoomWent;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyItemsStayed;
use Modules\Operator\Internal\Screens\GivingRoomBack;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatGivesBackRoom;
use Tests\Support\Fakes\AStackThatMeasuresItsRoom;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Giving back one category of the room the stack offers as costing nothing.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The name the stack's offer goes by. */
const THE_OFFER_OF_ROOM = 'space-3f9a';

/**
 * One line of the catalogue, with what it takes.
 *
 * @param array<string, int|string> $with
 */
function aLineOfGivingBack(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

/** A counted line of the catalogue. */
function aCountGivenBack(string $key, int $count): string
{
    return trans_choice($key, $count, ['count' => $count]);
}

/** A size as the glass says it, for a sentence that takes one. */
function aSizeGivenBack(string $key, int $bytes): string
{
    $big = HowBig::of($bytes);

    return aLineOfGivingBack($key, ['figure' => $big->figure, 'unit' => aLineOfGivingBack($big->said)]);
}

/** The machine room is given back on. */
function theStackRoomIsGivenBackOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The machine's room, offering downloads no service took in and, where asked, nothing else. */
function aRoomOffering(ACategoryOffered ...$categories): WhereTheRoomWent
{
    return WhereTheRoomWent::measured(
        TheVolumes::of(),
        WhereTheRoomStands::Ample,
        TheAccount::of(),
        TheDownloadsOnDisk::of(),
        TheRoomOnOffer::named(THE_OFFER_OF_ROOM, ...$categories),
        halted: false,
    );
}

/** Downloads no service took in: thirty gigabytes in twelve files. */
function theOrphansOnOffer(): ACategoryOffered
{
    return ACategoryOffered::holding(WhatALineIsAbout::Orphaned, WhatItOccupies::counted(30_000_000_000, 30_000_000_000), 12);
}

/** The screen, opened on a category, with a keychain holding whatever a case says. */
function theGivingBackScreen(
    AStackThatMeasuresItsRoom $measuring,
    AStackThatGivesBackRoom $givingBack,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    mixed $category = 'orphaned',
): GivingRoomBack {
    $stack = theStackRoomIsGivenBackOn();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new GivingRoomBack($measuring, $givingBack, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored(), 'about' => $category]);

    return $screen;
}

/** The stack's report: twenty-nine gigabytes back, eleven items gone, one left with its reason. */
function theRoomCameBack(): TheRoomGivenBack
{
    return TheRoomGivenBack::reported(29_000_000_000, 11, WhyItemsStayed::because('A service started reading it while it was being removed'));
}

/** The stack's words where the offer moved. */
function theOfferMoved(): ARefusalInItsWords
{
    return ARefusalInItsWords::said('The room on offer has changed since it was read', 'Read it again and agree to what it offers now', WhatTheRefusalNamed::nothing());
}

it('confirms once, naming the category, its size, how many files it holds and that it cannot be undone', function (): void {
    $givingBack = AStackThatGivesBackRoom::untouched();
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer())), $givingBack);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(aLineOfGivingBack('stacks.room.about.orphaned'))
        ->and($drawn->said())->toContain(aSizeGivenBack('stacks.room.takes', 30_000_000_000))
        ->and($drawn->said())->toContain(aCountGivenBack('stacks.give_back.files', 12))
        ->and($drawn->said())->toContain(aLineOfGivingBack('stacks.give_back.cannot_be_undone'))
        ->and($drawn->offers())->toBe([aLineOfGivingBack('stacks.give_back.give_it_back'), aLineOfGivingBack('health.ask_again')])
        ->and($givingBack->asked())->toBe([]);
});

it('reads the room afresh, about the machine the route names', function (): void {
    $measuring = AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer()));
    $screen = theGivingBackScreen($measuring, AStackThatGivesBackRoom::untouched());
    $screen->answer();
    $screen->answer();

    expect($measuring->askings())->toBe(1)
        ->and($measuring->askedAbout())->toEqual(theStackRoomIsGivenBackOn());

    $screen->again();
    $screen->answer();

    expect($measuring->askings())->toBe(2);
});

it('gives back that category of that offer alone, and follows it', function (): void {
    $givingBack = AStackThatGivesBackRoom::answering(WhatBecameOfTheWork::underway(Job::named(AStackThatGivesBackRoom::THE_GIVING_BACK)));
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer())), $givingBack);
    $screen->answer();
    $screen->agree();

    expect($givingBack->asked())->toBe([sprintf('give-back:%s:orphaned', THE_OFFER_OF_ROOM)])
        ->and($screen->wasAgreedTo())->toBeTrue()
        ->and($screen->isWorking())->toBeTrue()
        ->and($screen->awaitsAnOutcome())->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineOfGivingBack('stacks.give_back.giving_back'));

    $screen->whileItRuns();
    $screen->done();

    expect($givingBack->asked())->toBe([sprintf('give-back:%s:orphaned', THE_OFFER_OF_ROOM), sprintf('after:%s', AStackThatGivesBackRoom::THE_GIVING_BACK)]);
});

it('says what came back from the stack\'s report alone: the room returned, how many went, and why each left one stayed, with no path', function (): void {
    $givingBack = AStackThatGivesBackRoom::answering(WhatBecameOfTheWork::answered(theRoomCameBack()));
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer())), $givingBack);
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->isWorking())->toBeFalse()
        ->and($screen->done()->went)->toBe(11)
        ->and($screen->done()->left)->toBe(['A service started reading it while it was being removed'])
        ->and($drawn->said())->toContain(aSizeGivenBack('stacks.give_back.returned', 29_000_000_000))
        ->and($drawn->said())->toContain(aCountGivenBack('stacks.give_back.went', 11))
        ->and($drawn->said())->toContain(aCountGivenBack('stacks.give_back.left', 1))
        ->and($drawn->said())->toContain('A service started reading it while it was being removed')
        ->and($drawn->said())->not->toContain(aSizeGivenBack('stacks.room.takes', 30_000_000_000))
        ->and($drawn->offers())->toContain(aLineOfGivingBack('stacks.let_go.see_the_room'));
});

it('says nothing was left behind where the stack left nothing', function (): void {
    $report = TheRoomGivenBack::reported(30_000_000_000, 12, WhyItemsStayed::because());
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer())), AStackThatGivesBackRoom::sayingAtOnce(WhatBecameOfTheWork::answered($report)));
    $screen->answer();
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(aLineOfGivingBack('stacks.give_back.none_left'))
        ->and($drawn->said())->toContain(aCountGivenBack('stacks.give_back.went', 12))
        ->and($drawn->said())->not->toContain(aCountGivenBack('stacks.give_back.left', 0));
});

it('removes nothing where the offer moved, says so in the stack\'s words, and reads the offer again for a fresh yes', function (): void {
    $measuring = AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer()));
    $givingBack = AStackThatGivesBackRoom::sayingAtOnce(WhatBecameOfTheWork::moved(theOfferMoved()));
    $screen = theGivingBackScreen($measuring, $givingBack);
    $screen->answer();
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->wasAgreedTo())->toBeFalse()
        ->and($screen->isWorking())->toBeFalse()
        ->and($measuring->askings())->toBe(2)
        ->and($givingBack->asked())->toBe([sprintf('give-back:%s:orphaned', THE_OFFER_OF_ROOM)])
        ->and($drawn->said())->toContain(aLineOfGivingBack('stacks.give_back.moved'))
        ->and($drawn->said())->toContain('The room on offer has changed since it was read')
        ->and($drawn->offers())->toContain(aLineOfGivingBack('stacks.give_back.give_it_back'));
});

it('draws a refusal as the stack\'s refusal, with its remedy, and never as room returned', function (): void {
    $refusal = ARefusalInItsWords::said('Nowhere to measure', 'The data volume is not mounted', WhatTheRefusalNamed::nothing(), 'Mount it and ask again');
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer())), AStackThatGivesBackRoom::sayingAtOnce(WhatBecameOfTheWork::refused($refusal)));
    $screen->answer();
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->wasAgreedTo())->toBeTrue()
        ->and($drawn->said())->toContain(aLineOfGivingBack('stacks.give_back.refused'))
        ->and($drawn->said())->toContain('Nowhere to measure')
        ->and($drawn->said())->toContain(aLineOfGivingBack('stacks.refusal.remedy', ['remedy' => 'Mount it and ask again']))
        ->and($screen->done()->returned)->toBeNull()
        ->and($screen->done()->went)->toBe(0)
        ->and($screen->done()->left)->toBe([]);
});

it('reads the offer afresh when asked again after a yes the stack refused', function (): void {
    $measuring = AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer()));
    $refusal = ARefusalInItsWords::said('Nowhere to measure', 'The data volume is not mounted', WhatTheRefusalNamed::nothing());
    $givingBack = AStackThatGivesBackRoom::sayingAtOnce(WhatBecameOfTheWork::refused($refusal));
    $screen = theGivingBackScreen($measuring, $givingBack);
    $screen->answer();
    $screen->agree();
    $screen->again();

    expect($screen->wasAgreedTo())->toBeFalse()
        ->and($screen->answer()->stands->went->cameBack())->toBeTrue()
        ->and($measuring->askings())->toBe(2)
        ->and($givingBack->asked())->toBe([sprintf('give-back:%s:orphaned', THE_OFFER_OF_ROOM)]);
});

it('offers nothing to agree to where the offer no longer holds the category, and sends nothing', function (): void {
    $givingBack = AStackThatGivesBackRoom::untouched();
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering()), $givingBack);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->category)->toBeNull()
        ->and($drawn->said())->toContain(aLineOfGivingBack('stacks.give_back.not_on_offer'))
        ->and($drawn->offers())->not->toContain(aLineOfGivingBack('stacks.give_back.give_it_back'));

    $screen->agree();

    expect($givingBack->asked())->toBe([])
        ->and($screen->wasAgreedTo())->toBeFalse();
});

it('names no category where it was opened on none the stack gives, and asks the stack nothing', function (mixed $category): void {
    $measuring = AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer()));
    $screen = theGivingBackScreen($measuring, AStackThatGivesBackRoom::untouched(), category: $category);

    expect($screen->answer()->namesACategory)->toBeFalse()
        ->and($measuring->askings())->toBe(0)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineOfGivingBack('stacks.give_back.names_no_category'));
})->with(['a word it does not give' => ['gluetun'], 'blank' => ['  '], 'not text' => [42]]);

it('a stack that could not be read is what stood in the way, and nothing is offered', function (): void {
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), AStackThatGivesBackRoom::untouched());

    expect($screen->answer()->stands->went->cameBack())->toBeFalse()
        ->and($screen->answer()->stands->went->met)->toBe(KindOfObstacle::StackDidNotAnswer->said())
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(aLineOfGivingBack('stacks.give_back.give_it_back'));
});

it('lets go of a session the stack refused while the room was read', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), AStackThatGivesBackRoom::untouched(), $keychain);

    expect($screen->answer()->stands->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackRoomIsGivenBackOn()->id()))->toBeFalse();
});

it('lets go of a session the stack refused while the room was given back', function (): void {
    $keychain = AKeychainInMemory::working();
    $givingBack = AStackThatGivesBackRoom::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)));
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer())), $givingBack, $keychain);
    $screen->answer();
    $screen->agree();

    expect($screen->done()->stands->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackRoomIsGivenBackOn()->id()))->toBeFalse();
});

it('asks for a session rather than the room where this device holds none', function (): void {
    $measuring = AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer()));
    $screen = theGivingBackScreen($measuring, AStackThatGivesBackRoom::untouched(), signedIn: false);

    expect($screen->answer()->stands->went->isSignedIn)->toBeFalse()
        ->and($measuring->askings())->toBe(0)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(aLineOfGivingBack('connection.sign_in'));
});

it('asks for a session where it is gone by the time the yes is sent, or asked after', function (bool $afterTheYes, array $asked): void {
    $keychain = AKeychainInMemory::working();
    $givingBack = AStackThatGivesBackRoom::answering(WhatBecameOfTheWork::answered(theRoomCameBack()));
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer())), $givingBack, $keychain);
    $screen->answer();

    if ($afterTheYes) {
        $screen->agree();
    }

    $keychain->forget(theStackRoomIsGivenBackOn()->id());

    if (! $afterTheYes) {
        $screen->agree();
    }

    $screen->whileItRuns();

    expect($screen->done()->stands->went->isSignedIn)->toBeFalse()
        ->and($screen->done()->returned)->toBeNull()
        ->and($givingBack->asked())->toBe($asked);
})->with([
    'before the yes' => [false, []],
    'after it' => [true, [sprintf('give-back:%s:orphaned', THE_OFFER_OF_ROOM)]],
]);

it('has nothing to report for a yes nobody gave', function (): void {
    $givingBack = AStackThatGivesBackRoom::answering(WhatBecameOfTheWork::answered(theRoomCameBack()));
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer())), $givingBack);

    expect($screen->done()->stands->hasEnded())->toBeTrue()
        ->and($screen->done()->returned)->toBeNull()
        ->and($screen->done()->went)->toBe(0)
        ->and($screen->done()->left)->toBe([])
        ->and($givingBack->asked())->toBe([]);
});

it('says the stack no longer knows what became of it, and offers the room to see what is still there', function (): void {
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer())), AStackThatGivesBackRoom::answering(WhatBecameOfTheWork::noOutcome()));
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(aLineOfGivingBack('stacks.give_back.no_outcome'))
        ->and($drawn->offers())->toContain(aLineOfGivingBack('stacks.let_go.see_the_room'));
});

it('asking again after a yes asks after the same handle, and before one reads the offer afresh', function (): void {
    $givingBack = AStackThatGivesBackRoom::answering(WhatBecameOfTheWork::underway(Job::named(AStackThatGivesBackRoom::THE_GIVING_BACK)));
    $screen = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering(theOrphansOnOffer())), $givingBack);
    $screen->answer();
    $screen->agree();
    $screen->again();

    expect($screen->wasAgreedTo())->toBeTrue()
        ->and($screen->isWorking())->toBeTrue()
        ->and($givingBack->asked())->toBe([sprintf('give-back:%s:orphaned', THE_OFFER_OF_ROOM), sprintf('after:%s', AStackThatGivesBackRoom::THE_GIVING_BACK)]);
});

it('is reached by a route of its own, about one category', function (): void {
    $goes = theGivingBackScreen(AStackThatMeasuresItsRoom::with(aRoomOffering()), AStackThatGivesBackRoom::untouched())->goes()->about(AStacksScreen::GiveBack, WhatALineIsAbout::Extracted);

    expect($goes)->toBe(sprintf('/stacks/%s/give-back/extracted', theStackRoomIsGivenBackOn()->id()->stored()))
        ->and(NativeRouter::resolve($goes))->not->toBeNull();
});
