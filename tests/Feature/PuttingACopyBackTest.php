<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ACopyPutBack;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnExistingSetup;
use Modules\Kernel\Api\ARelocation;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowPuttingItBackIsGoing;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhatWroteACopy;
use Modules\Kernel\Api\WhereTheDataGoes;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\PuttingACopyBack;
use Modules\Operator\Internal\ViewModels\ARelocationAsShown;
use Modules\Operator\Internal\ViewModels\AScopeAsShown;
use Modules\Operator\Internal\ViewModels\HowPuttingItBackWent;
use Modules\Operator\Internal\ViewModels\WhatPuttingItBackWouldShow;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatPutsCopiesBack;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Putting a copy back: the rehearsal first, labelled as one; the yes, sent
// against that listing and nothing else; and the stack's report of it.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.


/**
 * One line of the catalogue, as text, for filling another.
 *
 * @param array<string, string> $with
 */
function aLineOfPuttingBack(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

/** The machine a copy is put back on. */
function theStackACopyGoesBackOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The screen, opened on a copy, with a keychain holding whatever a case says. */
function thePuttingBackScreen(
    AStackThatPutsCopiesBack $puttingBack,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    string $copy = 'lemonfiber-20260924-0300-full',
): PuttingACopyBack {
    $stack = theStackACopyGoesBackOn();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new PuttingACopyBack($puttingBack, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $copy]);

    return $screen;
}

/** A listing of the copy, with where its data goes and whether it is older as a case says. */
function aListingOfTheCopy(WhereTheDataGoes $data, bool $older = false, ?ScopeOfACopy $scope = null): WhatPuttingItBackWouldDo
{
    return WhatPuttingItBackWouldDo::listed(
        ACopy::named('lemonfiber-20260924-0300-full'),
        'restore-the-whole-stack-0.9.0',
        $scope ?? ScopeOfACopy::theWholeStack(),
        WhatWroteACopy::of('0.9.0', '2026-09-24T03:00:00Z'),
        WhatACopyHolds::these('lemonfiber configuration', 'service configuration'),
        older: $older,
        data: $data,
    );
}

/** The data moving from one root to another. */
function toTheNewRoot(): WhereTheDataGoes
{
    return WhereTheDataGoes::elsewhere(ARelocation::from('/srv/old', '/srv/new'));
}

/**
 * Every field of the listing as drawn, in one comparable row.
 *
 * @return array<string, mixed>
 */
function everythingTheListingShows(WhatPuttingItBackWouldShow $shown): array
{
    return [
        'cameBack' => $shown->went->cameBack(),
        'isSignedIn' => $shown->went->isSignedIn,
        'met' => $shown->went->met,
        'namesACopy' => $shown->namesACopy,
        'scope' => $shown->scope instanceof AScopeAsShown ? [$shown->scope->said, $shown->scope->with, $shown->scope->trees] : null,
        'takenBy' => $shown->takenBy,
        'takenAt' => $shown->takenAt,
        'contents' => $shown->contents,
        'isOlder' => $shown->isOlder,
        'relocation' => $shown->relocation instanceof ARelocationAsShown ? [$shown->relocation->was, $shown->relocation->now] : null,
    ];
}

/**
 * Every field of what became of the yes, in one comparable row.
 *
 * @return array<string, mixed>
 */
function everythingThePuttingBackShows(HowPuttingItBackWent $went): array
{
    return [
        'cameBack' => $went->went->cameBack(),
        'isSignedIn' => $went->went->isSignedIn,
        'met' => $went->went->met,
        'isWorking' => $went->isWorking,
        'hasEnded' => $went->hasEnded,
        'scope' => $went->scope instanceof AScopeAsShown ? [$went->scope->said, $went->scope->with, $went->scope->trees] : null,
        'takenBy' => $went->takenBy,
        'relocation' => $went->relocation instanceof ARelocationAsShown ? [$went->relocation->was, $went->relocation->now] : null,
    ];
}

/**
 * What a listing with nothing in it says of every field, changed where a case says.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function nothingListedOfTheCopy(array $changed): array
{
    return [
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'namesACopy' => true,
        'scope' => null,
        'takenBy' => null,
        'takenAt' => null,
        'contents' => [],
        'isOlder' => false,
        'relocation' => null,
        ...$changed,
    ];
}

/**
 * What a yes with no report says of every field, changed where a case says.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function nothingReportedOfPuttingItBack(array $changed): array
{
    return [
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'isWorking' => false,
        'hasEnded' => false,
        'scope' => null,
        'takenBy' => null,
        'relocation' => null,
        ...$changed,
    ];
}

it('rehearses first, labelled as one, with the scope, what wrote it, what it holds and where the data would go', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheListingShows($screen->answer()))->toBe([
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'namesACopy' => true,
        'scope' => ['stacks.copy.scope.whole_stack', [], []],
        'takenBy' => '0.9.0',
        'takenAt' => '2026-09-24T03:00:00Z',
        'contents' => ['lemonfiber configuration', 'service configuration'],
        'isOlder' => false,
        'relocation' => ['/srv/old', '/srv/new'],
    ])
        ->and(array_map(static fn(ACopy $copy): string => $copy->name(), $puttingBack->rehearsed()))->toBe(['lemonfiber-20260924-0300-full'])
        ->and($puttingBack->agreed())->toBe([])
        ->and($drawn->said())->toContain(__('stacks.put_back.a_rehearsal'))
        ->and($drawn->said())->toContain(__('stacks.put_back.would_put_back', ['scope' => aLineOfPuttingBack('stacks.copy.scope.whole_stack')]))
        ->and($drawn->said())->toContain(__('stacks.put_back.taken_by', ['version' => '0.9.0', 'at' => '2026-09-24T03:00:00Z']))
        ->and($drawn->said())->toContain(__('stacks.put_back.would_move', ['was' => '/srv/old', 'now' => '/srv/new']))
        ->and($drawn->said())->toContain('lemonfiber configuration')
        ->and($drawn->said())->toContain(__('stacks.put_back.only_while_stopped'))
        ->and($drawn->said())->not->toContain(__('stacks.put_back.older'))
        ->and($drawn->said())->not->toContain(__('stacks.put_back.moved', ['was' => '/srv/old', 'now' => '/srv/new']))
        ->and($drawn->offers())->toContain(__('stacks.put_back.put_it_back'));
});

it('says a copy whose data goes back where it came from does, and one from an older version is', function (): void {
    $screen = thePuttingBackScreen(AStackThatPutsCopiesBack::listing(aListingOfTheCopy(WhereTheDataGoes::whereItWas(), older: true), HowPuttingItBackIsGoing::stillRunning()));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->answer()->relocation)->toBeNull()
        ->and($screen->answer()->isOlder)->toBeTrue()
        ->and($drawn)->toContain(__('stacks.put_back.would_go_where_it_was'))
        ->and($drawn)->toContain(__('stacks.put_back.older'));
});

it('lists one service, and the trees of an existing setup, as what would be put back', function (ScopeOfACopy $scope, array $shown, string $said): void {
    $screen = thePuttingBackScreen(AStackThatPutsCopiesBack::listing(aListingOfTheCopy(WhereTheDataGoes::whereItWas(), scope: $scope), HowPuttingItBackIsGoing::stillRunning()));

    expect(everythingTheListingShows($screen->answer())['scope'])->toBe($shown)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain($said);
})->with([
    'one service' => [ScopeOfACopy::oneService(ServiceId::called('sonarr')), ['stacks.copy.scope.service', ['name' => 'sonarr'], []], 'lemonfiber configuration'],
    'an existing setup' => [ScopeOfACopy::anExistingSetup(AnExistingSetup::of('media', WhatACopyHolds::these('/srv/arr'))), ['stacks.copy.scope.existing', ['name' => 'media'], ['/srv/arr']], '/srv/arr'],
]);

it('says a copy that lists nothing inside it does', function (): void {
    $listing = WhatPuttingItBackWouldDo::listed(ACopy::named('lemonfiber-20260924-0300-full'), 'restore-0.9.0', ScopeOfACopy::theWholeStack(), WhatWroteACopy::of('0.9.0', '2026-09-24T03:00:00Z'), WhatACopyHolds::these(), older: false, data: WhereTheDataGoes::whereItWas());
    $screen = thePuttingBackScreen(AStackThatPutsCopiesBack::listing($listing, HowPuttingItBackIsGoing::stillRunning()));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.put_back.holds_nothing'));
});

it('offers nothing to agree to where the stack would not list the copy', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::met(Obstacle::StackDidNotAnswer);
    $screen = thePuttingBackScreen($puttingBack);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheListingShows($screen->answer()))->toBe(nothingListedOfTheCopy(['cameBack' => false, 'met' => Obstacle::StackDidNotAnswer->said()]))
        ->and($drawn->offers())->not->toContain(__('stacks.put_back.put_it_back'))
        ->and($drawn->said())->not->toContain(__('stacks.put_back.a_rehearsal'));

    $screen->agree();

    expect($puttingBack->agreed())->toBe([])
        ->and($screen->wasAgreedTo())->toBeFalse();
});

it('names no copy where it was opened on none, and asks the stack nothing', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack, copy: '  ');

    expect(everythingTheListingShows($screen->answer()))->toBe(nothingListedOfTheCopy(['namesACopy' => false]))
        ->and($puttingBack->rehearsed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.put_back.names_no_copy'));
});

it('asks for a session rather than a listing where this device holds none', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack, signedIn: false);

    expect(everythingTheListingShows($screen->answer()))->toBe(nothingListedOfTheCopy(['cameBack' => false, 'isSignedIn' => false]))
        ->and($puttingBack->rehearsed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('connection.sign_in'));
});

it('lets go of a session the stack refused while listing', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = thePuttingBackScreen(AStackThatPutsCopiesBack::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackACopyGoesBackOn()->id()))->toBeFalse();
});

it('puts back exactly the listing it showed, and says it is running without drawing progress nobody measured', function (): void {
    $listing = aListingOfTheCopy(toTheNewRoot());
    $puttingBack = AStackThatPutsCopiesBack::listing($listing, HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack);
    $screen->answer();
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($puttingBack->agreed())->toBe([$listing])
        ->and($screen->wasAgreedTo())->toBeTrue()
        ->and($screen->isWorking())->toBeTrue()
        ->and(everythingThePuttingBackShows($screen->done()))->toBe(nothingReportedOfPuttingItBack(['isWorking' => true]))
        ->and($drawn)->toContain(__('stacks.put_back.putting_back', ['copy' => 'lemonfiber-20260924-0300-full']))
        ->and($drawn)->toContain(__('stacks.put_back.no_progress_while_running'))
        ->and($drawn)->toContain(__('health.every.while_work_runs', ['count' => 5]))
        ->and($drawn)->not->toContain(__('stacks.put_back.a_rehearsal'))
        ->and($puttingBack->followed())->toBe([]);
});

it('sends no yes before a listing has been read', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack);
    $screen->agree();

    expect($puttingBack->agreed())->toBe([])
        ->and($screen->wasAgreedTo())->toBeFalse()
        ->and($screen->isWorking())->toBeFalse();
});

it('asks after a restore on its cadence while it runs, by the handle the yes was answered with', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack);
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $screen->done();

    expect($puttingBack->followed())->toHaveCount(1)
        ->and($puttingBack->followed()[0]->shown())->toBe(AStackThatPutsCopiesBack::THE_JOB);
});

it('does not ask on its cadence while it is only showing a listing', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack);
    $screen->answer();
    $screen->whileItRuns();
    $screen->answer();

    expect($puttingBack->rehearsed())->toHaveCount(1)
        ->and($puttingBack->followed())->toBe([]);
});

it('reports what was put back, and where the data went when it moved', function (WhereTheDataGoes $data, ?array $relocation, string $key): void {
    $report = ACopyPutBack::reported(ScopeOfACopy::oneService(ServiceId::called('sonarr')), '0.9.0', $data);
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy($data), HowPuttingItBackIsGoing::done($report));
    $screen = thePuttingBackScreen($puttingBack);
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingThePuttingBackShows($screen->done()))->toBe(nothingReportedOfPuttingItBack([
        'scope' => ['stacks.copy.scope.service', ['name' => 'sonarr'], []],
        'takenBy' => '0.9.0',
        'relocation' => $relocation,
    ]))
        ->and($drawn->said())->toContain(__('stacks.put_back.put_back', ['scope' => aLineOfPuttingBack('stacks.copy.scope.service', ['name' => 'sonarr']), 'version' => '0.9.0']))
        ->and($drawn->said())->toContain(__($key, ['was' => '/srv/old', 'now' => '/srv/new']))
        ->and($drawn->said())->not->toContain(__('stacks.put_back.a_rehearsal'))
        ->and($drawn->offers())->toContain(__('stacks.copy.see_the_copies'));

    $screen->whileItRuns();
    $screen->done();

    expect($puttingBack->followed())->toHaveCount(1);
})->with([
    'moved' => [WhereTheDataGoes::elsewhere(ARelocation::from('/srv/old', '/srv/new')), ['/srv/old', '/srv/new'], 'stacks.put_back.moved'],
    'where it was' => [WhereTheDataGoes::whereItWas(), null, 'stacks.put_back.where_it_was'],
]);

it('names the trees an existing setup\'s copy put back', function (): void {
    $scope = ScopeOfACopy::anExistingSetup(AnExistingSetup::of('media', WhatACopyHolds::these('/srv/arr')));
    $screen = thePuttingBackScreen(AStackThatPutsCopiesBack::listing(aListingOfTheCopy(WhereTheDataGoes::whereItWas()), HowPuttingItBackIsGoing::done(ACopyPutBack::reported($scope, '0.9.0', WhereTheDataGoes::whereItWas()))));
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();

    expect($screen->done()->scope?->trees)->toBe(['/srv/arr'])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain('/srv/arr');
});

it('says a restore the stack has no outcome for is not known, rather than failed', function (): void {
    $screen = thePuttingBackScreen(AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::ended()));
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(everythingThePuttingBackShows($screen->done()))->toBe(nothingReportedOfPuttingItBack(['hasEnded' => true]))
        ->and($drawn)->toContain(__('stacks.put_back.no_outcome'))
        ->and($drawn)->toContain(__('stacks.put_back.no_outcome_action'));
});

it('says what stood in the way of a yes the stack refused, and asks for the listing afresh', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listingButRefusing(aListingOfTheCopy(toTheNewRoot()), Obstacle::StackDidNotAnswer);
    $screen = thePuttingBackScreen($puttingBack);
    $screen->answer();
    $screen->agree();

    expect(everythingThePuttingBackShows($screen->done()))->toBe(nothingReportedOfPuttingItBack(['cameBack' => false, 'met' => Obstacle::StackDidNotAnswer->said()]))
        ->and($screen->isWorking())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(Obstacle::StackDidNotAnswer->said()));

    $screen->again();

    expect($screen->wasAgreedTo())->toBeFalse()
        ->and($screen->answer()->went->cameBack())->toBeTrue()
        ->and($puttingBack->rehearsed())->toHaveCount(2)
        ->and($puttingBack->followed())->toBe([]);
});

it('asks after the same handle when asked again after a yes the stack took on', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack);
    $screen->answer();
    $screen->agree();
    $screen->again();
    $screen->done();

    expect($screen->wasAgreedTo())->toBeTrue()
        ->and($puttingBack->followed())->toHaveCount(1)
        ->and($puttingBack->rehearsed())->toHaveCount(1);
});

it('asks for the listing afresh when asked again before a yes', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack);
    $screen->answer();
    $screen->again();
    $screen->agree();

    expect($puttingBack->agreed())->toBe([])
        ->and($screen->answer()->went->cameBack())->toBeTrue()
        ->and($puttingBack->rehearsed())->toHaveCount(2);
});

it('lets go of a session refused while putting back or asking after', function (bool $whileAsking): void {
    $keychain = AKeychainInMemory::working();
    $puttingBack = $whileAsking
        ? AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::met(Obstacle::CredentialWasRefused))
        : AStackThatPutsCopiesBack::listingButRefusing(aListingOfTheCopy(toTheNewRoot()), Obstacle::CredentialWasRefused);
    $screen = thePuttingBackScreen($puttingBack, $keychain);
    $screen->answer();
    $screen->agree();

    if ($whileAsking) {
        $screen->whileItRuns();
    }

    expect($screen->done()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackACopyGoesBackOn()->id()))->toBeFalse();
})->with(['putting back' => [false], 'asking after' => [true]]);

it('asks for a session where it is gone by the time the yes is sent, or asked after', function (bool $afterTheYes): void {
    $keychain = AKeychainInMemory::working();
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack, $keychain);
    $screen->answer();

    if ($afterTheYes) {
        $screen->agree();
    }

    $keychain->forget(theStackACopyGoesBackOn()->id());

    if (! $afterTheYes) {
        $screen->agree();
    }

    $screen->whileItRuns();

    expect(everythingThePuttingBackShows($screen->done()))->toBe(nothingReportedOfPuttingItBack(['cameBack' => false, 'isSignedIn' => false]));
})->with(['before the yes' => [false], 'after it' => [true]]);

it('has nothing to report for a yes nobody gave', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack);

    expect(everythingThePuttingBackShows($screen->done()))->toBe(nothingReportedOfPuttingItBack(['hasEnded' => true]))
        ->and($puttingBack->followed())->toBe([]);
});

it('refuses a route parameter that is not text as naming a stack', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped, and anything that is not a string names no
    // stack.
    $screen = thePuttingBackScreen(AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning()));
    $screen->setParams(['stack' => 42, 'service' => 'lemonfiber-20260924-0300-full']);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('names no copy where the route carries one that is not text, and asks the stack nothing', function (): void {
    $puttingBack = AStackThatPutsCopiesBack::listing(aListingOfTheCopy(toTheNewRoot()), HowPuttingItBackIsGoing::stillRunning());
    $screen = thePuttingBackScreen($puttingBack);
    $screen->setParams(['stack' => theStackACopyGoesBackOn()->id()->stored(), 'service' => 42]);

    expect($screen->copyNamed())->toBe('')
        ->and(everythingTheListingShows($screen->answer()))->toBe(nothingListedOfTheCopy(['namesACopy' => false]))
        ->and($puttingBack->rehearsed())->toBe([]);
});
