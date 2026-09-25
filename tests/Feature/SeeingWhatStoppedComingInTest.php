<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowMuchIsShown;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotConfigured;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stalled;
use Modules\Kernel\Api\Stuck;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatElseItIsCalled;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatStoppedComingIn;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatStalled;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Stuck downloads are reachable.
//
// The screen that answers the question an operator is asked in person: *I asked
// for that film on Tuesday and it never arrived*. The requests screen can only
// half answer it, because a request marked as being fetched and one that has
// been being fetched for nine days look the same there.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite.

/** The machine whose stalled downloads this screen is about. */
function theStackWhoseStallIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** Two stalled titles, one of which nothing is going to move. */
function aWeekOfStalledDownloads(): Stalled
{
    return Stalled::of(
        HowMuchIsShown::AllOfIt,
        WhatIsUnsupported::none(),
        Stuck::at('A film nobody has seen', 'radarr', Stage::Searching),
        Stuck::at('A series somebody has', 'sonarr', Stage::NotMonitored),
    );
}

/**
 * The screen, with a stack it knows and a keychain holding whatever a test says.
 *
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theStalledScreen(
    AStackThatStalled $stalling,
    ?AKeychainInMemory $keychain = null,
    ?string $named = null,
    bool $signedIn = true,
    ?AStackThatExplainsItsWords $explaining = null,
): WhatStoppedComingIn {
    $stack = theStackWhoseStallIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatStoppedComingIn($stalling, $keychain, StacksInMemory::holding($stack), $explaining ?? AStackThatExplainsItsWords::with(TheGlossary::of()));
    $screen->setParams(['stack' => $named ?? $stack->id()->stored()]);

    return $screen;
}

it('N2-R9 — shows what stopped, where it stopped, and who has it', function (): void {
    $screen = theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()));

    expect($screen->howMany())->toBe(2)
        // A stack that answered is not a session that ended. `isSignedIn` is the
        // template's first branch, so a fold reporting otherwise here would put
        // The sign-in prompt in front of an operator whose session is
        // working and the rows would never be reached at all.
        ->and($screen->answer()->went->isSignedIn)->toBeTrue()
        // Neither of the obstacle's two keys, because nothing was met.
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('');

    $rows = $screen->answer()->stalled;

    expect($rows[0]->title)->toBe('A film nobody has seen')
        ->and($rows[0]->service)->toBe('radarr')
        ->and($rows[0]->stage)->toBe('searching')
        ->and($rows[0]->stageSaid)->toBe(Stage::Searching->saidOnTheScreen())
        ->and($rows[1]->title)->toBe('A series somebody has')
        ->and($rows[1]->service)->toBe('sonarr')
        ->and($rows[1]->stage)->toBe('not-monitored')
        ->and($rows[1]->stageSaid)->toBe(Stage::NotMonitored->saidOnTheScreen());
});

it('N15-R9 — draws the stack\'s word for a stage, with the plain sentence beside it', function (): void {
    // Asked of the drawn screen rather than of the row, because a row can
    // carry the word and a template can still draw only the sentence.
    // Both locales, because the word is not the catalogue's and must come
    // through a Dutch screen exactly as an English one draws it.
    foreach (['en', 'nl'] as $locale) {
        app()->setLocale($locale);

        $drawn = WhatTheDeviceWouldDraw::by(theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads())))->said();

        expect($drawn)->toContain(__('health.at_stage', ['stage' => 'searching']))
            ->and($drawn)->toContain(__(Stage::Searching->saidOnTheScreen()))
            ->and($drawn)->toContain(__('health.at_stage', ['stage' => 'not-monitored']))
            ->and($drawn)->toContain(__(Stage::NotMonitored->saidOnTheScreen()));
    }
});

it('keeps the stack\'s order rather than putting the hopeless ones first', function (): void {
    // Tempting and wrong. The order is the one the work was queued in, and the
    // oldest thing stuck is usually the one that has been wrong longest — which
    // is what an operator scanning this is looking for.
    $rows = theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()))->answer()->stalled;

    expect($rows[0]->stillMoving)->toBeTrue()
        ->and($rows[1]->stillMoving)->toBeFalse();
});

it('N2-R9 — says how much of what the stack holds this is', function (): void {
    // The field a screen cannot notice the absence of. A partial listing
    // rendered without it claims to be complete, and an operator shown three
    // stalled titles and told that is all of them stops looking.
    $partial = Stalled::of(
        HowMuchIsShown::SomeOfIt,
        WhatIsUnsupported::none(),
        Stuck::at('A film nobody has seen', 'radarr', Stage::Searching),
    );

    expect(theStalledScreen(AStackThatStalled::with($partial))->answer()->shownSaid)
        ->toBe(HowMuchIsShown::SomeOfIt->saidOnTheScreen())
        ->and(theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()))->answer()->shownSaid)
        ->toBe(HowMuchIsShown::AllOfIt->saidOnTheScreen());
});

it('nothing stuck is an answer, and not the same one as a stack that would not say', function (): void {
    // The distinction the whole screen turns on. A phone in flight mode must
    // not report a house where everything is arriving normally.
    $quiet = theStalledScreen(AStackThatStalled::withNothingStuck());

    expect($quiet->howMany())->toBe(0)
        ->and($quiet->answer()->went->met)->toBe('')
        ->and($quiet->answer()->shownSaid)->toBe(HowMuchIsShown::AllOfIt->saidOnTheScreen());
});

it('N1-R10 — a stack that could not be asked says which of the six it met', function (): void {
    $screen = theStalledScreen(AStackThatStalled::met(Obstacle::DeviceHasNoNetwork));

    expect($screen->howMany())->toBe(0)
        // Meeting an obstacle is not losing the session either: the device
        // asked and was answered. Reporting otherwise would hide which of the
        // six was met behind a sign-in screen for a session that is fine.
        ->and($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($screen->answer()->went->met)->toBe(Obstacle::DeviceHasNoNetwork->said())
        ->and($screen->answer()->went->remedy)->toBe(Obstacle::DeviceHasNoNetwork->remedy())
        // Nothing to be complete about, so the line is not rendered at all
        // rather than claiming a listing that was never read.
        ->and($screen->answer()->shownSaid)->toBe('');
});

it('N1-R44 — a device with no session for that stack is not asked to wait for one', function (): void {
    $stalling = AStackThatStalled::with(aWeekOfStalledDownloads());
    $screen = theStalledScreen($stalling, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->howMany())->toBe(0)
        // Nothing was met, because the app never got as far as asking — and a
        // remedy beside no obstacle would be an instruction about nothing.
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        // Nothing to be complete about either, so the line the listing branch
        // always renders is not rendered here at all.
        ->and($screen->answer()->shownSaid)->toBe('')
        ->and($stalling->askings())->toBe(0);
});

it('N1-R65 — asks once however many accessors a frame reads', function (): void {
    $stalling = AStackThatStalled::with(aWeekOfStalledDownloads());
    $screen = theStalledScreen($stalling);

    $screen->howMany();
    $screen->answer();
    $screen->answer();
    $screen->answer();

    expect($stalling->askings())->toBe(1);
});

it('N1-R11 — asks about the stack the route names, with the session kept for it', function (): void {
    $stalling = AStackThatStalled::with(aWeekOfStalledDownloads());
    theStalledScreen($stalling)->howMany();

    expect($stalling->askedAbout()?->id()->stored())->toBe(theStackWhoseStallIsRead()->id()->stored())
        ->and($stalling->wasGivenASession())->toBeTrue();
});

it('N1-R11 — a route naming a stack this device has forgotten is refused', function (): void {
    $screen = theStalledScreen(
        AStackThatStalled::with(aWeekOfStalledDownloads()),
        named: str_repeat('z', Nonce::SHORTEST),
    );

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsNotConfigured::class);
});

it('refuses a route parameter that is not text', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped. Anything that is not a string names no stack,
    // which is the same situation as a route with nothing in that segment. The
    // three screens either side of this one make the same assertion.
    $screen = theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('N2-R9 — the screen is registered under the route that reaches it', function (): void {
    $resolved = NativeRouter::resolve(
        AStacksScreen::Stuck->forTheStack(theStackWhoseStallIsRead()->id()),
    );

    expect($resolved['class'] ?? null)->toBe(WhatStoppedComingIn::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()));

    expect($screen->render()->name())->toBe('operator::what-stopped-coming-in');
});

it('N1-R3 — asking again after an obstacle asks the stack again', function (): void {
    // The action an obstacle must not take away. A stack that was asleep when
    // the screen opened may be awake now, and leaving and returning is what
    // the cadence rule refuses by name.
    $stalling = AStackThatStalled::met(Obstacle::DeviceHasNoNetwork);
    $screen = theStalledScreen($stalling);

    $screen->howMany();
    $screen->again();
    $screen->howMany();

    expect($stalling->askings())->toBe(2);
});

it('N3-R13 — a credential the stack refused signs this device out and lets the session go', function (): void {
    // The stalled screen makes the same two moves the others do, and it has to
    // make them itself: a fold cannot forget anything, and a session left in
    // the store is resumed on the next frame and refused again.
    $keychain = AKeychainInMemory::working();
    $screen = theStalledScreen(AStackThatStalled::met(Obstacle::CredentialWasRefused), $keychain);

    expect($keychain->isHolding(theStackWhoseStallIsRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        // Nothing about a machine, because this is not about the machine — and
        // nothing already loaded, which is named separately.
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->howMany())->toBe(0)
        ->and($screen->answer()->shownSaid)->toBe('')
        ->and($keychain->isHolding(theStackWhoseStallIsRead()->id()))->toBeFalse();
});

it('explains a stage in place by any name the glossary gives it, and leaves one it does not carry as it came', function (): void {
    $explaining = AStackThatExplainsItsWords::with(TheGlossary::of(
        AWord::explained('search', 'Looking through the indexers for a release', '', 'searching'),
    ));
    $drawn = WhatTheDeviceWouldDraw::by(theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()), explaining: $explaining))->said();

    expect($drawn)->toContain(__('stacks.words.in_place', ['word' => 'search', 'short' => 'Looking through the indexers for a release']))
        ->and(array_filter($drawn, static fn(string $line): bool => str_starts_with($line, 'search:')))->toHaveCount(1)
        ->and($explaining->askings())->toBe(1);
});

it('explains a stage in place by the form lemonfiber writes it in', function (): void {
    $explaining = AStackThatExplainsItsWords::with(TheGlossary::of(
        AWord::explained('search', 'Looking through the indexers for a release', '')->writtenAs(
            WhatElseItIsCalled::formsOf('searching', 'searched'),
        ),
    ));
    $drawn = WhatTheDeviceWouldDraw::by(theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()), explaining: $explaining))->said();

    expect($drawn)->toContain(__('stacks.words.in_place', ['word' => 'search', 'short' => 'Looking through the indexers for a release']));
});

it('asks for no glossary where nothing stopped', function (): void {
    $explaining = AStackThatExplainsItsWords::with(TheGlossary::of());
    $drawn = WhatTheDeviceWouldDraw::by(theStalledScreen(AStackThatStalled::with(Stalled::of(HowMuchIsShown::AllOfIt, WhatIsUnsupported::none())), explaining: $explaining))->said();

    expect($drawn)->not->toBeEmpty()
        ->and($explaining->askings())->toBe(0);
});

it('offers each stalled item\'s trace, reached by its title', function (): void {
    $screen = theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()));

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('health.trace.road_in', ['item' => 'A film nobody has seen']))
        ->and(NativeRouter::resolve($screen->traceOf('A film nobody has seen')))->toHaveKey('params.service', 'A film nobody has seen');
});

/** A queue the stack's repairs could not look into, with nothing it could see stuck. */
function aQueueItCouldNotReach(Stuck ...$stalled): Stalled
{
    return Stalled::of(
        HowMuchIsShown::AllOfIt,
        WhatIsUnsupported::these(Unsupported::of('The download client', 'It refused the credentials lemonfiber holds for it')),
        ...$stalled,
    );
}

it('draws what the repairs could not reach as its own answer, and never as nothing stopped', function (): void {
    foreach (['en', 'nl'] as $locale) {
        app()->setLocale($locale);

        $drawn = WhatTheDeviceWouldDraw::by(theStalledScreen(AStackThatStalled::with(aQueueItCouldNotReach())))->said();

        expect($drawn)->toContain(__('health.could_not_reach'))
            ->and($drawn)->toContain(__('health.could_not_reach_explained'))
            ->and($drawn)->toContain('The download client')
            ->and($drawn)->toContain('It refused the credentials lemonfiber holds for it')
            ->and($drawn)->toContain(trans_choice('health.stuck_count_where_it_looked', 0))
            ->and($drawn)->not->toContain(trans_choice('health.stuck_count', 0))
            ->and($drawn)->not->toContain(__('health.nothing_stopped'))
            ->and($drawn)->not->toContain(__('health.nothing_stopped_action'));
    }
});

it('draws what it could not reach before the rows it could', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theStalledScreen(AStackThatStalled::with(
        aQueueItCouldNotReach(Stuck::at('A film nobody has seen', 'radarr', Stage::Searching)),
    )))->said();

    expect(array_values(array_intersect($drawn, ['A film nobody has seen', 'The download client'])))
        ->toBe(['The download client', 'A film nobody has seen'])
        ->and($drawn)->toContain(trans_choice('health.stuck_count_where_it_looked', 1));
});

it('says nothing about reach where the stack reached everything', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theStalledScreen(AStackThatStalled::withNothingStuck()))->said();

    expect($drawn)->toContain(trans_choice('health.stuck_count', 0))
        ->and($drawn)->toContain(__('health.nothing_stopped'))
        ->and($drawn)->toContain(__('health.nothing_stopped_action'))
        ->and($drawn)->not->toContain(__('health.could_not_reach'));
});

it('draws a queue it could not ask as the obstacle, and never as nothing stopped', function (Obstacle $met): void {
    $drawn = WhatTheDeviceWouldDraw::by(theStalledScreen(AStackThatStalled::met($met)))->said();

    expect($drawn)->toContain(__($met->said()))
        ->and($drawn)->not->toContain(trans_choice('health.stuck_count', 0))
        ->and($drawn)->not->toContain(__('health.nothing_stopped'))
        ->and($drawn)->not->toContain(__('health.nothing_stopped_action'));
})->with([
    'no network' => [Obstacle::DeviceHasNoNetwork],
    'a stack that did not answer' => [Obstacle::StackDidNotAnswer],
]);

it('offers asking again and following an item, and nothing that acts on the queue or sets it up', function (): void {
    $screen = theStalledScreen(AStackThatStalled::with(aQueueItCouldNotReach(
        Stuck::at('A film nobody has seen', 'radarr', Stage::Searching),
    )));

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([
        __('health.trace.road_in', ['item' => 'A film nobody has seen']),
        __('health.ask_again'),
    ]);
});
