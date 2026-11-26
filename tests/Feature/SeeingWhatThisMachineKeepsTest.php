<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACopyRunning;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\HowTheCopyMoves;
use Modules\Kernel\Api\HowTheCopyRunningWasRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomethingBeside;
use Modules\Kernel\Api\SomethingKept;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Storing;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\TheRoots;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatIsBeingCopied;
use Modules\Kernel\Api\WhatIsBeside;
use Modules\Kernel\Api\WhatIsCopied;
use Modules\Kernel\Api\WhatIsKept;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Kernel\Api\WhereThingsAreKept;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowTheCopyRunningReads;
use Modules\Operator\Internal\Screens\WhatThisMachineKeepsHere;
use Modules\Operator\Internal\ViewModels\ARootAsShown;
use Modules\Operator\Internal\ViewModels\SomethingBesideAsShown;
use Modules\Operator\Internal\ViewModels\SomethingKeptAsShown;
use Modules\Operator\Internal\ViewModels\TheCopiesAsFound;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Storekeepers;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeRouter;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AJobThatSays;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatCopies;
use Tests\Support\Fakes\AStackThatForgets;
use Tests\Support\Fakes\AStackThatListsItsCopies;
use Tests\Support\Fakes\AStackThatSaysWhatItKeeps;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// What this machine keeps, where, and why, and the copies it holds.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The machine this screen is about. */
function theStackWhoseKeepingIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A machine keeping one secret and one thing that is not, beside a library that is not its own. */
function whatTheLoftKeeps(): WhatThisMachineKeeps
{
    return WhatThisMachineKeeps::of(
        TheRoots::of(WhereThingsAreKept::at('/srv/lemonfiber', 'Everything the stack writes')),
        WhatIsKept::of(
            SomethingKept::kept('The VPN credentials', '/srv/lemonfiber/secrets/vpn', 'So the tunnel can be raised', WhetherItHoldsASecret::Secret),
            SomethingKept::kept('Sonarr\'s settings', '/srv/lemonfiber/config/sonarr', 'So Sonarr starts as it was left', WhetherItHoldsASecret::Plain),
        ),
        WhatIsBeside::of(SomethingBeside::named('/srv/media', 'Your library, which the stack reads and never removes')),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theKeepingScreen(
    Storing $storing,
    AStackThatListsItsCopies $copying,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    ?AStackThatCopies $running = null,
): WhatThisMachineKeepsHere {
    $stack = theStackWhoseKeepingIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatThisMachineKeepsHere($storing, $copying, AStackThatForgets::sayingAtOnce(WhatBecameOfTheWork::noOutcome()), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening(clock: FrozenClock::at(aMomentOfTheBackup(0)), copying: $running ?? AStackThatCopies::copyingNothing()), AJobThatSays::nothing());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** The copies, as the frame after the first one reads them. */
function theCopiesOnTheNextFrame(WhatThisMachineKeepsHere $screen): ?TheCopiesAsFound
{
    $screen->answer();
    $screen->render();

    return $screen->copies();
}

/**
 * How soon the frame drawn last asked to be drawn again, in milliseconds, if it asked.
 *
 * Read off the package's own record of the intervals a template declared,
 * which it keeps to itself, because that record is what wakes the next frame.
 *
 * @return list<int>
 */
function howSoonItAskedForTheNextFrame(WhatThisMachineKeepsHere $screen): array
{
    $asked = Closure::bind(static fn(NativeComponent $drawn): array => array_keys($drawn->bladePollDeadlines), null, NativeComponent::class)($screen);

    return array_values(array_filter($asked, is_int(...)));
}

/** Two copies, newest first, as the stack lists them. */
function twoCopies(): AStackThatListsItsCopies
{
    return AStackThatListsItsCopies::with(TheCopies::named('lemonfiber-20260924-0300-full', 'lemonfiber-20260923-0300-full'));
}

it('shows where things are kept, what, why, and whether each holds a secret', function (): void {
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies());
    $answer = $screen->answer();
    $copies = theCopiesOnTheNextFrame($screen);

    expect($answer->went->cameBack())->toBeTrue()
        ->and(array_map(static fn(ARootAsShown $r): array => [$r->where, $r->what], $answer->roots))->toBe([['/srv/lemonfiber', 'Everything the stack writes']])
        ->and(array_map(static fn(SomethingKeptAsShown $k): array => [$k->what, $k->where, $k->why, $k->secretSaid], $answer->kept))->toBe([
            ['The VPN credentials', '/srv/lemonfiber/secrets/vpn', 'So the tunnel can be raised', WhetherItHoldsASecret::Secret->saidOnTheScreen()],
            ['Sonarr\'s settings', '/srv/lemonfiber/config/sonarr', 'So Sonarr starts as it was left', WhetherItHoldsASecret::Plain->saidOnTheScreen()],
        ])
        ->and(array_map(static fn(SomethingBesideAsShown $b): array => [$b->what, $b->why], $answer->beside))->toBe([['/srv/media', 'Your library, which the stack reads and never removes']])
        ->and($copies?->went->cameBack())->toBeTrue()
        ->and($copies?->names)->toBe(['lemonfiber-20260924-0300-full', 'lemonfiber-20260923-0300-full']);
});

it('a value the stack sends beside a secret never reaches the glass', function (): void {
    // The real adapter, not the fake, because the fake cannot carry a value
    // at all and this is about what happens when the wire does.
    //
    // `stored` is stood in for and not judged: the `value` beside the secret
    // is one the contract does not declare, put there to show that a field
    // the reader does not name never reaches the glass.
    $leaked = 'hunter2-not-for-the-screen';
    $said = [
        'api_version' => 1,
        'kind' => 'stored',
        'data' => [
            'roots' => [],
            'kept' => [['what' => 'The VPN credentials', 'at' => '/srv/lemonfiber/secrets/vpn', 'why' => 'So the tunnel can be raised', 'secret' => true, 'value' => $leaked]],
            'beside' => [],
            'removal' => ['state' => 'not-asked'],
        ],
    ];
    // Destroyed first, because `MockClient::global()` is `??=` and every
    // test begins with one that answers nothing.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode($said))]);

    $drawn = WhatTheDeviceWouldDraw::by(theKeepingScreen(new Storekeepers(new PinnedClients()), twoCopies()))->said();

    expect($drawn)->toContain('The VPN credentials')
        ->and($drawn)->toContain(__(WhetherItHoldsASecret::Secret->saidOnTheScreen()))
        ->and(implode("\n", $drawn))->not->toContain($leaked);
});

it('no copy taken and a list that could not be read are different sentences on the glass', function (): void {
    $none = WhatTheDeviceWouldDraw::onTheSecondFrame(theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), AStackThatListsItsCopies::with(TheCopies::named())))->said();
    $unread = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), AStackThatListsItsCopies::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $unreadDrawn = WhatTheDeviceWouldDraw::onTheSecondFrame($unread)->said();

    expect($none)->toContain(__('stacks.keeps.no_copies'))
        ->and($none)->not->toContain(__('stacks.keeps.copies_unread'))
        ->and($unreadDrawn)->toContain(__('stacks.keeps.copies_unread'))
        ->and($unreadDrawn)->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($unreadDrawn)->toContain(__(KindOfObstacle::StackDidNotAnswer->remedy()))
        ->and(WhatTheDeviceWouldDraw::by($unread)->offers())->toBe([__('stacks.keeps.take_a_copy'), __('stacks.forget.what_would_go'), __('health.ask_again')])
        ->and($unreadDrawn)->not->toContain(__('stacks.keeps.no_copies'))
        // What the stack keeps is still shown: the copies failing is theirs alone.
        ->and($unread->answer()->went->cameBack())->toBeTrue()
        ->and($unreadDrawn)->toContain('The VPN credentials');
});

it('the copies drawn are the copies listed, each by its name', function (): void {
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame(theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies()))->said();

    expect($drawn)->toContain('lemonfiber-20260924-0300-full')
        ->and($drawn)->toContain('lemonfiber-20260923-0300-full')
        ->and($drawn)->not->toContain(__('stacks.keeps.no_copies'));
});

it('says so where the stack keeps nothing, names nowhere, and has nothing beside it', function (): void {
    $empty = WhatThisMachineKeeps::of(TheRoots::of(), WhatIsKept::of(), WhatIsBeside::of());
    $drawn = WhatTheDeviceWouldDraw::by(theKeepingScreen(AStackThatSaysWhatItKeeps::with($empty), twoCopies()))->said();

    expect($drawn)->toContain(__('stacks.keeps.no_roots'))
        ->and($drawn)->toContain(__('stacks.keeps.nothing_kept'))
        ->and($drawn)->toContain(__('stacks.keeps.nothing_beside'));
});

it('offers putting back each copy listed, taking a copy and asking again, and never a first run', function (): void {
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame(theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies()));

    // Each copy is a row that goes to putting it back, drawn by its name with
    // what tapping it does under the name.
    expect($drawn->offers())->toBe([
        'lemonfiber-20260924-0300-full',
        'lemonfiber-20260923-0300-full',
        __('stacks.keeps.take_a_copy'),
        __('stacks.forget.what_would_go'),
        __('health.ask_again'),
    ])
        ->and(array_keys($drawn->said(), __('stacks.keeps.put_back'), strict: true))->toHaveCount(2);
});

it('offers nothing to put back where no copy has been taken', function (): void {
    $offers = WhatTheDeviceWouldDraw::onTheSecondFrame(theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), AStackThatListsItsCopies::with(TheCopies::named())))->offers();

    expect($offers)->toBe([__('stacks.keeps.take_a_copy'), __('stacks.forget.what_would_go'), __('health.ask_again')]);
});

it('a stack that could not be asked is not a machine keeping nothing, and its copies are not asked', function (): void {
    $copying = twoCopies();
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), $copying);
    $answer = $screen->answer();
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($answer->went->isSignedIn)->toBeTrue()
        ->and($answer->roots)->toBe([])
        ->and($answer->kept)->toBe([])
        ->and($answer->beside)->toBe([])
        ->and($copying->askings())->toBe(0);
});

it('a session that has ended is not a machine keeping nothing', function (): void {
    $storing = AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps());
    $answer = theKeepingScreen($storing, twoCopies(), signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->kept)->toBe([])
        ->and($storing->askings())->toBe(0);
});

it('a credential refused for what is kept signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), twoCopies(), $keychain);

    expect($keychain->isHolding(theStackWhoseKeepingIsRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($keychain->isHolding(theStackWhoseKeepingIsRead()->id()))->toBeFalse();
});

it('a credential refused for the copies lets the session go as well', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), AStackThatListsItsCopies::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain);

    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect($screen->copies()?->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseKeepingIsRead()->id()))->toBeFalse()
        ->and($drawn->said())->toContain(__('connection.session_has_ended'))
        ->and($drawn->offers())->toContain(__('connection.sign_in'));
});

it('copies that could not be read for another reason leave the session standing', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), AStackThatListsItsCopies::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), $keychain);

    $copies = theCopiesOnTheNextFrame($screen);

    expect($copies?->went->isSignedIn)->toBeTrue()
        ->and($copies?->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($keychain->isHolding(theStackWhoseKeepingIsRead()->id()))->toBeTrue();
});

it('reads what is kept on the first frame, the copies on the next and the copy running on the third, each once, about the machine the route names', function (): void {
    $storing = AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps());
    $copying = twoCopies();
    $screen = theKeepingScreen($storing, $copying);

    $first = WhatTheDeviceWouldDraw::by($screen);
    $askedFor = howSoonItAskedForTheNextFrame($screen);

    expect($storing->askings())->toBe(1)
        ->and($copying->askings())->toBe(0)
        ->and($first->said())->toContain(__('stacks.keeps.reading_copies'))
        ->and($askedFor)->toBe([16]);

    WhatTheDeviceWouldDraw::by($screen);
    $askedForAfter = howSoonItAskedForTheNextFrame($screen);
    WhatTheDeviceWouldDraw::by($screen);
    $askedForLast = howSoonItAskedForTheNextFrame($screen);
    WhatTheDeviceWouldDraw::by($screen);

    expect($storing->askings())->toBe(1)
        ->and($copying->askings())->toBe(1)
        ->and($askedForAfter)->toBe([16])
        ->and($askedForLast)->toBe([])
        ->and($storing->wasGivenASession())->toBeTrue()
        ->and($copying->wasGivenASession())->toBeTrue()
        ->and($storing->askedAbout()?->id()->stored())->toBe(theStackWhoseKeepingIsRead()->id()->stored())
        ->and($copying->askedAbout()?->id()->stored())->toBe(theStackWhoseKeepingIsRead()->id()->stored());
});

it('asking again asks both again', function (): void {
    $storing = AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps());
    $copying = AStackThatListsItsCopies::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork));
    $screen = theKeepingScreen($storing, $copying);

    theCopiesOnTheNextFrame($screen);
    $screen->again();
    $screen->render();
    theCopiesOnTheNextFrame($screen);

    expect($storing->askings())->toBe(2)
        ->and($copying->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), twoCopies());
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies());

    expect(NativeRouter::resolve($screen->goes()->to(AStacksScreen::Health)))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->to(AStacksScreen::Keeps)))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies());

    expect($screen->render()->name())->toBe('operator::what-this-machine-keeps-here');
});

it('copies asked after the session ended between the two frames are a session that ended, and are not asked for', function (): void {
    $keychain = AKeychainInMemory::working();
    $copying = twoCopies();
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), $copying, $keychain);
    WhatTheDeviceWouldDraw::by($screen);
    $keychain->forget(theStackWhoseKeepingIsRead()->id());

    WhatTheDeviceWouldDraw::by($screen);

    expect($screen->copiesFound?->went->isSignedIn)->toBeFalse()
        ->and($screen->copiesFound?->names)->toBe([])
        ->and($copying->askings())->toBe(0);
});

it('reads what is kept again once a minute, and the copies on the frame after', function (): void {
    $storing = AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps());
    $copying = twoCopies();
    $screen = theKeepingScreen($storing, $copying);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    $screen->whileOpen();
    WhatTheDeviceWouldDraw::by($screen);
    $kept = [$storing->askings(), $copying->askings()];
    WhatTheDeviceWouldDraw::by($screen);

    expect($kept)->toBe([2, 1])
        ->and([$storing->askings(), $copying->askings()])->toBe([2, 2]);
});

function aMomentOfTheBackup(int $seconds): Instant
{
    return Instant::atEpochSeconds(1_790_000_000 + $seconds);
}

/** A backup of one service, a quarter written, started ten minutes before the screen's moment, moving as a case says. */
function aBackupOfSonarrThatIs(HowTheCopyMoves $moves): AStackThatCopies
{
    return AStackThatCopies::holdingOpen(HowTheCopyRunningWasRead::said(WhatIsBeingCopied::running(new ACopyRunning(WhatIsCopied::Backup, ScopeOfACopy::oneService(ServiceId::called('sonarr')), aMomentOfTheBackup(-600), 536_870_912, 2_147_483_648, 'sonarr database', $moves))));
}

/** @param array<string, string> $with */
function aLineOfTheBackup(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

/**
 * Every progress bar drawn, as how far each says it has got.
 *
 * @param array<mixed> $node
 *
 * @return list<mixed>
 */
function theBarsIn(array $node): array
{
    $bars = ($node['type'] ?? null) === 'progress_bar' ? [data_get($node, 'props.value')] : [];

    foreach ($node as $child) {
        if (is_array($child)) {
            $bars = [...$bars, ...theBarsIn($child)];
        }
    }

    return $bars;
}

it('draws a backup running above the copies: of what, a bar and how much of how much, the part, when it started and how it moves', function (): void {
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies(), running: aBackupOfSonarrThatIs(HowTheCopyMoves::working()));
    $drawn = WhatTheDeviceWouldDraw::onTheThirdFrame($screen)->said();
    $written = HowBig::of(536_870_912);
    $whole = HowBig::of(2_147_483_648);
    $title = aLineOfTheBackup('stacks.copy.running.what.backup', ['scope' => aLineOfTheBackup('stacks.copy.scope.service', ['name' => 'sonarr'])]);

    expect($drawn)->toContain(
        $title,
        aLineOfTheBackup('stacks.copy.running.written', ['written' => (string) $written->figure, 'written_unit' => aLineOfTheBackup($written->said), 'whole' => (string) $whole->figure, 'whole_unit' => aLineOfTheBackup($whole->said)]),
        aLineOfTheBackup('stacks.copy.running.part', ['part' => 'sonarr database']),
        trans_choice('stacks.copy.running.started.minutes', 10),
        aLineOfTheBackup(HowTheCopyRunningReads::WORKING),
    )
        ->and(theBarsIn(WhatTheDeviceWouldDraw::tree($screen)))->toBe([0.25])
        ->and(array_keys($drawn, $title, strict: true)[0] ?? null)->toBeLessThan(array_keys($drawn, 'lemonfiber-20260924-0300-full', strict: true)[0] ?? 0)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(aLineOfTheBackup('navigation.menu.copies_run'));
});

it('draws nothing of a copy where none runs, and says it cannot tell where the stack gives no reading', function (): void {
    $none = WhatTheDeviceWouldDraw::onTheThirdFrame(theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies()))->said();
    $unread = WhatTheDeviceWouldDraw::onTheThirdFrame(theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies(), running: AStackThatCopies::holdingOpen(HowTheCopyRunningWasRead::refused(Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)))))->said();

    expect($none)->not->toContain(aLineOfTheBackup(HowTheCopyRunningReads::NONE), aLineOfTheBackup(HowTheCopyRunningReads::CANNOT_TELL), aLineOfTheBackup('navigation.menu.copies_run'))
        ->and($unread)->toContain(aLineOfTheBackup(HowTheCopyRunningReads::CANNOT_TELL));
});

it('follows the copy on the stream without reading it again when it looks again, and lets go of it when the screen stops', function (): void {
    $running = aBackupOfSonarrThatIs(HowTheCopyMoves::working());
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies(), running: $running);
    WhatTheDeviceWouldDraw::onTheThirdFrame($screen);
    $screen->followTheCopy();
    $screen->whileOpen();
    WhatTheDeviceWouldDraw::onTheThirdFrame($screen);
    $screen->stop();

    expect($running->read())->toBe(1)
        ->and($running->asked())->toBe(1)
        ->and($running->lettingsGo())->toBe(1);
});
