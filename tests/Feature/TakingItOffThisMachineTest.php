<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\AnUninstall;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\HowMuchWasRead;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\NamedOnTheManifest;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OneThingItReaches;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomethingItCannotTake;
use Modules\Kernel\Api\SomethingLeftBehind;
use Modules\Kernel\Api\SomethingNotLemonfibers;
use Modules\Kernel\Api\SomethingStillComing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatBecameOfTheUninstall;
use Modules\Kernel\Api\WhatGoesAndWhatStays;
use Modules\Kernel\Api\WhatIsNotLemonfibers;
use Modules\Kernel\Api\WhatIsStillComing;
use Modules\Kernel\Api\WhatItCannotTake;
use Modules\Kernel\Api\WhatItReaches;
use Modules\Kernel\Api\WhatSortItIs;
use Modules\Kernel\Api\WhatTakingItOffComesTo;
use Modules\Kernel\Api\WhatToKnowFirst;
use Modules\Kernel\Api\WhatWasLeftBehind;
use Modules\Kernel\Api\WhereTakingItOffGot;
use Modules\Kernel\Api\WhichRemoval;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\TakingItOffThisMachine;
use Modules\Operator\Internal\ViewModels\ASizeAsShown;
use Modules\Operator\Internal\ViewModels\OneLineItReachesAsShown;
use Modules\Operator\Internal\ViewModels\SomethingComingAsShown;
use Modules\Operator\Internal\ViewModels\SomethingNotOursAsShown;
use Modules\Operator\Internal\ViewModels\SomethingOutsideAsShown;
use Modules\Operator\Internal\ViewModels\TakingItOffTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheReadingAsShown;
use Modules\Operator\Internal\ViewModels\WhatTakingItOffDidAsShown;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Internal\TheMenu;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatTakesItOff;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\NoticingWhatIsNew;
use Tests\Support\WhatTheDeviceWouldDraw;

// Taking lemonfiber off this machine: four removals, each read on its own and
// agreed to against the reading on the screen, then followed to what it did.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.

/** The machine lemonfiber is taken off. */
function theMachineLemonfiberLeaves(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The screen, with a keychain holding whatever a case says. */
function theTakingItOffScreen(AStackThatTakesItOff $removing, ?AKeychainInMemory $keychain = null, bool $signedIn = true, ?StacksInMemory $stacks = null): TakingItOffThisMachine
{
    $stack = theMachineLemonfiberLeaves();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new TakingItOffThisMachine($removing, $keychain, AroundThePhone::holding($stacks ?? StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening(), NoticingWhatIsNew::fromNothing());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** The screen, with a removal chosen and its reading drawn. */
function aRemovalChosenAndRead(AStackThatTakesItOff $removing, WhichRemoval $tier, ?AKeychainInMemory $keychain = null): TakingItOffThisMachine
{
    $screen = theTakingItOffScreen($removing, keychain: $keychain);
    $screen->choose($tier->value);
    $screen->answer();

    return $screen;
}

/**
 * The reading of one removal, with a line going and a line kept, read in full unless a case says otherwise.
 *
 * @param list<SomethingStillComing> $coming
 */
function aReadingOfTakingItOff(
    WhichRemoval $tier = WhichRemoval::Services,
    ?HowMuchWasRead $confidence = null,
    array $coming = [],
    string $volume = '',
    string $copyFirst = '',
): WhatTakingItOffComesTo {
    return WhatTakingItOffComesTo::read(
        $tier,
        WhatGoesAndWhatStays::said(
            'The containers and the images',
            'Your configuration and your library',
        ),
        WhatItReaches::of(
            OneThingItReaches::going('lemonfiber-gluetun', WhatSortItIs::Container, 'The VPN', holdsACredential: true, size: AnAmountOfRoom::of(3_221_225_472, 'bytes')),
            OneThingItReaches::going('lemonfiber-net', WhatSortItIs::Network, 'The stack\'s network', holdsACredential: false, size: AnAmountOfRoom::unread()),
            OneThingItReaches::kept('ghcr.io/example/shared:1', WhatSortItIs::Image, 'A shared image', holdsACredential: false, size: AnAmountOfRoom::of(1_073_741_824, 'bytes'), because: 'Another project stands on it'),
        ),
        3_221_225_472,
        WhatToKnowFirst::said(
            WhatIsNotLemonfibers::of(SomethingNotLemonfibers::at('photos', 12, 2_147_483_648)),
            WhatIsStillComing::of(...$coming),
            WhatItCannotTake::of(
                SomethingItCannotTake::found('Docker', 'lemonfiber did not install it', 'Uninstall Docker Desktop'),
                SomethingItCannotTake::notFound('Tailscale', 'It is a separate client', 'Remove the Tailscale app'),
            ),
            volume: $volume,
            copyFirst: $copyFirst,
        ),
        $confidence ?? HowMuchWasRead::everything(),
        sprintf('%s-3-lines', $tier->value),
    );
}

/** A stack reading that removal, then answering the yes with work and what the work came to. */
function aStackTakingItOffTo(WhatTakingItOffComesTo $reading, WhereTakingItOffGot $got): AStackThatTakesItOff
{
    return AStackThatTakesItOff::reading(
        AnUninstall::of($reading, WhereTakingItOffGot::surveyed()),
        WhatBecameOfTheUninstall::underway(Job::named('j-1')),
        WhatBecameOfTheUninstall::answered(AnUninstall::of($reading, $got)),
    );
}

/** A stack reading that removal, then answering the yes as a case says. */
function aStackReadingTheRemoval(WhatTakingItOffComesTo $reading, WhatBecameOfTheUninstall ...$answers): AStackThatTakesItOff
{
    return AStackThatTakesItOff::reading(AnUninstall::of($reading, WhereTakingItOffGot::surveyed()), ...$answers);
}

/** A figure in bytes, as the screen says it. */
function aSizeOnTheUninstallScreen(int $bytes): ASizeAsShown
{
    $big = HowBig::of($bytes);

    return new ASizeAsShown(figure: $big->figure, unit: $big->said);
}

/**
 * A size as drawn, as a row two sizes can be compared by, or nothing where none is drawn.
 *
 * @return list<int|string>|null
 */
function aSizeHeldOnTheUninstallScreen(?ASizeAsShown $size): ?array
{
    return $size instanceof ASizeAsShown ? [$size->figure, $size->unit] : null;
}

/**
 * One line of the catalogue, with the words given put in, as the words it holds.
 *
 * @param array<string, int|string> $with
 */
function aLineOnTheUninstallScreen(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : '';
}

/** The words the screen draws for a figure in bytes, under a catalogue key taking `figure` and `unit`. */
function aSizeSaidOnTheUninstallScreen(string $key, int $bytes): string
{
    $size = aSizeOnTheUninstallScreen($bytes);

    return aLineOnTheUninstallScreen($key, ['figure' => $size->figure, 'unit' => aLineOnTheUninstallScreen($size->unit)]);
}

/** Whether the first line is drawn above the second, both being drawn. */
function drawnAboveOnTheUninstallScreen(string $first, string $second, WhatTheDeviceWouldDraw $drawn): bool
{
    $above = array_search($first, $drawn->said(), strict: true);
    $below = array_search($second, $drawn->said(), strict: true);

    return is_int($above) && is_int($below) && $above < $below;
}

/**
 * Every field of a state as drawn, in one comparable row, down to each line of the reading.
 *
 * @return array<string, mixed>
 */
function everythingTheUninstallScreenHolds(TakingItOffTurnedOutToBe $shown): array
{
    $reading = $shown->reading;
    $did = $shown->did;

    return [
        'cameBack' => $shown->went->cameBack(),
        'isSignedIn' => $shown->went->isSignedIn,
        'met' => $shown->went->met,
        'chosen' => $shown->chosen,
        'wasAgreed' => $shown->wasAgreed,
        'isWorking' => $shown->isWorking,
        'hasEnded' => $shown->hasEnded,
        'refusal' => $shown->refusal,
        'endsThisSession' => $shown->endsThisSession,
        'reading' => $reading instanceof TheReadingAsShown ? [
            $reading->tierSaid,
            $reading->agreeSaid,
            $reading->takesTheLibrary,
            $reading->endsThisSession,
            $reading->removes,
            $reading->keeps,
            $reading->isComplete,
            $reading->unread,
            array_map(static fn(OneLineItReachesAsShown $line): array => [$line->name, $line->sortSaid, $line->what, $line->holdsACredential, aSizeHeldOnTheUninstallScreen($line->size), $line->whyKept], $reading->going),
            array_map(static fn(OneLineItReachesAsShown $line): array => [$line->name, $line->sortSaid, $line->what, $line->holdsACredential, aSizeHeldOnTheUninstallScreen($line->size), $line->whyKept], $reading->kept),
            aSizeHeldOnTheUninstallScreen($reading->frees),
            array_map(static fn(SomethingNotOursAsShown $foreign): array => [$foreign->at, $foreign->files, aSizeHeldOnTheUninstallScreen($foreign->size)], $reading->foreign),
            array_map(static fn(SomethingComingAsShown $coming): array => [$coming->name, $coming->progress], $reading->coming),
            array_map(static fn(SomethingOutsideAsShown $outside): array => [$outside->what, $outside->why, $outside->byHand, $outside->foundSaid], $reading->outside),
            $reading->volume,
            $reading->copyFirst,
        ] : null,
        'did' => $did instanceof WhatTakingItOffDidAsShown ? [
            $did->saidAs,
            $did->isFinished,
            $did->gone,
            $did->credentials,
            array_map(static fn(SomethingOutsideAsShown $left): array => [$left->what, $left->why, $left->byHand, $left->foundSaid], $did->left),
        ] : null,
    ];
}

/**
 * What a state with nothing in it holds, changed where a case says.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function nothingHeldOfTakingItOff(array $changed): array
{
    return [
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'chosen' => true,
        'wasAgreed' => false,
        'isWorking' => false,
        'hasEnded' => false,
        'refusal' => '',
        'endsThisSession' => false,
        'reading' => null,
        'did' => null,
        ...$changed,
    ];
}

/**
 * The services' reading from {@see aReadingOfTakingItOff()}, as the row {@see everythingTheUninstallScreenHolds()} makes of it.
 *
 * @return list<mixed>
 */
function theServicesReadingAsHeld(): array
{
    return [
        'uninstall.tier.services',
        'uninstall.agree.services',
        false,
        false,
        'The containers and the images',
        'Your configuration and your library',
        true,
        [],
        [
            ['lemonfiber-gluetun', 'uninstall.sort.container', 'The VPN', true, aSizeHeldOnTheUninstallScreen(aSizeOnTheUninstallScreen(3_221_225_472)), ''],
            ['lemonfiber-net', 'uninstall.sort.network', 'The stack\'s network', false, null, ''],
        ],
        [['ghcr.io/example/shared:1', 'uninstall.sort.image', 'A shared image', false, aSizeHeldOnTheUninstallScreen(aSizeOnTheUninstallScreen(1_073_741_824)), 'Another project stands on it']],
        aSizeHeldOnTheUninstallScreen(aSizeOnTheUninstallScreen(3_221_225_472)),
        [['photos', 12, aSizeHeldOnTheUninstallScreen(aSizeOnTheUninstallScreen(2_147_483_648))]],
        [],
        [
            ['Docker', 'lemonfiber did not install it', 'Uninstall Docker Desktop', 'uninstall.was_found'],
            ['Tailscale', 'It is a separate client', 'Remove the Tailscale app', 'uninstall.not_found'],
        ],
        '',
        '',
    ];
}

it('opens on reading stopping everything, which removes nothing, with the way to the other three', function (): void {
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff(WhichRemoval::Stop));
    $screen = theTakingItOffScreen($removing);
    $opened = WhatTheDeviceWouldDraw::by($screen);

    expect($removing->asked())->toBe(['read:stop'])
        ->and($opened->said())->toContain(aLineOnTheUninstallScreen('uninstall.tier.stop'))
        ->and($opened->offers())->toContain(aLineOnTheUninstallScreen('uninstall.agree.stop'))
        ->and($opened->offers())->toContain(aLineOnTheUninstallScreen('uninstall.choose_again'));
});

it('offers the four removals to choose from, the library apart, and reads none of them until one is chosen', function (): void {
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff());
    $screen = theTakingItOffScreen($removing);
    $screen->chooseAgain();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($removing->asked())->toBe([])
        ->and($drawn->said())->toContain(__('uninstall.four'))
        ->and($drawn->said())->toContain(__('uninstall.the_library_alone'))
        ->and($drawn->offers())->toBe([
            aLineOnTheUninstallScreen('uninstall.read', ['tier' => aLineOnTheUninstallScreen('uninstall.tier.stop')]),
            aLineOnTheUninstallScreen('uninstall.read', ['tier' => aLineOnTheUninstallScreen('uninstall.tier.services')]),
            aLineOnTheUninstallScreen('uninstall.read', ['tier' => aLineOnTheUninstallScreen('uninstall.tier.configuration')]),
            aLineOnTheUninstallScreen('uninstall.read', ['tier' => aLineOnTheUninstallScreen('uninstall.tier.media')]),
            ...array_slice($drawn->offers(), 4),
        ])
        ->and(drawnAboveOnTheUninstallScreen(aLineOnTheUninstallScreen('uninstall.four'), aLineOnTheUninstallScreen('uninstall.the_library'), $drawn))->toBeTrue();
});

it('reads the removal chosen, and only a word naming one of the four', function (): void {
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff());
    $screen = theTakingItOffScreen($removing);
    $screen->choose('everything');
    $screen->answer();
    $screen->choose('media');
    $screen->answer();

    expect($removing->asked())->toBe(['read:stop', 'read:media'])
        ->and($screen->tier)->toBe('media');
});

it('says how much of it was read before anything else, then every line going, what is kept and why, and what goes takes', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff()), WhichRemoval::Services));
    $said = $drawn->said();

    expect(drawnAboveOnTheUninstallScreen(aLineOnTheUninstallScreen('uninstall.complete'), aLineOnTheUninstallScreen('uninstall.removes'), $drawn))->toBeTrue()
        ->and($said)->toContain(__('uninstall.tier.services'))
        ->and($said)->toContain('The containers and the images')
        ->and($said)->toContain('Your configuration and your library')
        ->and($said)->toContain('lemonfiber-gluetun')
        ->and($said)->toContain(__('uninstall.sort.container'))
        ->and($said)->toContain(aSizeSaidOnTheUninstallScreen('uninstall.takes', 3_221_225_472))
        ->and($said)->toContain(__('uninstall.holds_a_credential'))
        ->and($said)->toContain(__('uninstall.size_unread'))
        ->and($said)->toContain('Another project stands on it')
        ->and($said)->toContain(aSizeSaidOnTheUninstallScreen('uninstall.frees', 3_221_225_472))
        ->and($said)->not->toContain(__('uninstall.incomplete'))
        ->and($said)->not->toContain(__('uninstall.unread'))
        ->and($drawn->offers())->toContain(__('uninstall.agree.services'))
        ->and($drawn->offers())->toContain(__('uninstall.choose_again'));
});

it('says a list that could not be read in full is not complete, and what could not be read, and counts only what was read', function (): void {
    $reading = aReadingOfTakingItOff(confidence: HowMuchWasRead::notEverything('The container engine did not answer'));
    $drawn = WhatTheDeviceWouldDraw::by(aRemovalChosenAndRead(aStackReadingTheRemoval($reading), WhichRemoval::Services));
    $said = $drawn->said();

    expect(drawnAboveOnTheUninstallScreen(aLineOnTheUninstallScreen('uninstall.incomplete'), aLineOnTheUninstallScreen('uninstall.removes'), $drawn))->toBeTrue()
        ->and(drawnAboveOnTheUninstallScreen(aLineOnTheUninstallScreen('uninstall.tier.services'), aLineOnTheUninstallScreen('uninstall.incomplete'), $drawn))->toBeTrue()
        ->and($said)->toContain(__('uninstall.unread'))
        ->and($said)->toContain('The container engine did not answer')
        ->and($said)->toContain(aSizeSaidOnTheUninstallScreen('uninstall.frees_as_read', 3_221_225_472))
        ->and($said)->not->toContain(__('uninstall.complete'));
});

it('names what is not lemonfiber\'s apart, and what it cannot take with whether it was found and how by hand', function (): void {
    $said = WhatTheDeviceWouldDraw::by(aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff()), WhichRemoval::Services))->said();
    $foreign = aSizeOnTheUninstallScreen(2_147_483_648);

    expect($said)->toContain(__('uninstall.foreign_is'))
        ->and($said)->toContain(trans_choice('uninstall.foreign_line', 12, ['at' => 'photos', 'figure' => $foreign->figure, 'unit' => aLineOnTheUninstallScreen($foreign->unit)]))
        ->and($said)->toContain(__('uninstall.outside_is'))
        ->and($said)->toContain(__('uninstall.was_found'))
        ->and($said)->toContain(__('uninstall.not_found'))
        ->and($said)->toContain(__('uninstall.by_hand', ['how' => 'Uninstall Docker Desktop']))
        ->and($said)->toContain(__('uninstall.by_hand', ['how' => 'Remove the Tailscale app']));
});

it('says so where nothing goes, nothing is kept, nothing is foreign and nothing is outside', function (): void {
    $empty = WhatTakingItOffComesTo::read(WhichRemoval::Stop, WhatGoesAndWhatStays::said('Nothing', 'Everything'), WhatItReaches::of(), 0, WhatToKnowFirst::said(WhatIsNotLemonfibers::of(), WhatIsStillComing::of(), WhatItCannotTake::of()), HowMuchWasRead::everything(), 'stop-0');
    $said = WhatTheDeviceWouldDraw::by(aRemovalChosenAndRead(aStackReadingTheRemoval($empty), WhichRemoval::Stop))->said();

    expect($said)->toContain(__('uninstall.nothing_going'))
        ->and($said)->toContain(__('uninstall.nothing_kept'))
        ->and($said)->toContain(__('uninstall.nothing_foreign'))
        ->and($said)->toContain(__('uninstall.nothing_outside'))
        ->and($said)->toContain(__('uninstall.nothing_coming'))
        ->and($said)->not->toContain(__('uninstall.foreign_is'))
        ->and($said)->not->toContain(__('uninstall.outside_is'));
});

it('agrees to the library with how much data goes, under its own reading', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff(WhichRemoval::Media)), WhichRemoval::Media));

    expect($drawn->said())->toContain(__('uninstall.tier.media'))
        ->and($drawn->said())->toContain(__('uninstall.the_library_alone'))
        ->and(WhatTheDeviceWouldDraw::by(aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff()), WhichRemoval::Services))->said())->not->toContain(__('uninstall.the_library_alone'))
        ->and($drawn->offers())->toContain(aSizeSaidOnTheUninstallScreen('uninstall.agree.media', 3_221_225_472));
});

it('takes it off against the reading on the screen, follows the work, and draws what went', function (): void {
    $removing = aStackTakingItOffTo(aReadingOfTakingItOff(), WhereTakingItOffGot::complete(
        NamedOnTheManifest::under('gone', 'lemonfiber-gluetun', 'lemonfiber-net'),
        NamedOnTheManifest::under('credentials', 'The VPN key'),
    ));
    $screen = aRemovalChosenAndRead($removing, WhichRemoval::Services);
    $screen->goAhead();
    $running = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->isWorking)->toBeTrue()
        ->and($running->said())->toContain(__('uninstall.removing'))
        ->and($screen->surveyed)->toBeNull();

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($removing->asked())->toBe(['read:services', 'take:services:services-3-lines:go_ahead_now', 'after:j-1'])
        ->and($drawn->said())->toContain(__('uninstall.did.complete'))
        ->and($drawn->said())->toContain('lemonfiber-net')
        ->and($drawn->said())->toContain('The VPN key')
        ->and($drawn->said())->toContain(__('uninstall.nothing_left'))
        ->and($drawn->said())->not->toContain(__('uninstall.after_configuration'))
        ->and($drawn->offers())->toBe([__('uninstall.choose_again'), ...array_slice($drawn->offers(), 1)])
        ->and($drawn->offers())->not->toContain(__('uninstall.agree.services'))
        ->and($screen->following)->toBeNull();
});

it('never draws a partial removal as complete, and names each thing left with how to finish it by hand', function (): void {
    $removing = aStackTakingItOffTo(aReadingOfTakingItOff(), WhereTakingItOffGot::partial(
        NamedOnTheManifest::under('gone'),
        NamedOnTheManifest::under('credentials'),
        WhatWasLeftBehind::of(SomethingLeftBehind::named('lemonfiber-net', 'The network is in use', 'docker network rm lemonfiber-net')),
    ));
    $screen = aRemovalChosenAndRead($removing, WhichRemoval::Services);
    $screen->goAhead();
    $screen->whileItRuns();
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(__('uninstall.did.partial'))
        ->and($said)->not->toContain(__('uninstall.did.complete'))
        ->and($said)->toContain(__('uninstall.left_why', ['why' => 'The network is in use']))
        ->and($said)->toContain(__('uninstall.by_hand', ['how' => 'docker network rm lemonfiber-net']))
        ->and($said)->toContain(__('uninstall.no_credentials'))
        ->and($said)->toContain(__('uninstall.nothing_gone'));
});

it('draws a yes the stack only rehearsed as that, with nothing gone', function (): void {
    $screen = aRemovalChosenAndRead(aStackTakingItOffTo(aReadingOfTakingItOff(), WhereTakingItOffGot::rehearsed()), WhichRemoval::Services);
    $screen->goAhead();
    $screen->whileItRuns();
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(__('uninstall.did.rehearsed'))
        ->and($said)->not->toContain(__('uninstall.gone'))
        ->and($said)->not->toContain(__('uninstall.did.complete'));
});

it('offers waiting and going ahead where downloads are still coming, chooses neither, and sends the one tapped', function (): void {
    $reading = aReadingOfTakingItOff(coming: [SomethingStillComing::named('A film', 40)]);
    $waiting = aStackReadingTheRemoval($reading, WhatBecameOfTheUninstall::underway(Job::named('j-1')));
    $screen = aRemovalChosenAndRead($waiting, WhichRemoval::Services);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('uninstall.coming_line', ['name' => 'A film', 'progress' => 40]))
        ->and($drawn->said())->toContain(__('uninstall.interrupts'))
        ->and($drawn->offers())->toContain(aLineOnTheUninstallScreen('uninstall.wait', ['agree' => aLineOnTheUninstallScreen('uninstall.agree.services')]))
        ->and($drawn->offers())->toContain(aLineOnTheUninstallScreen('uninstall.go_ahead_now', ['agree' => aLineOnTheUninstallScreen('uninstall.agree.services')]))
        ->and($drawn->offers())->not->toContain(__('uninstall.agree.services'));

    $screen->waitThenGo();

    $now = aStackReadingTheRemoval($reading, WhatBecameOfTheUninstall::underway(Job::named('j-1')));
    aRemovalChosenAndRead($now, WhichRemoval::Services)->goAhead();

    expect($waiting->asked())->toBe(['read:services', 'take:services:services-3-lines:for_the_downloads'])
        ->and($now->asked())->toBe(['read:services', 'take:services:services-3-lines:go_ahead_now']);
});

it('holds the yes until a data location on a volume that unplugs is acknowledged apart', function (): void {
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff(WhichRemoval::Media, volume: 'The data is on a network share'), WhatBecameOfTheUninstall::underway(Job::named('j-1')));
    $screen = aRemovalChosenAndRead($removing, WhichRemoval::Media);
    $before = WhatTheDeviceWouldDraw::by($screen);
    $screen->goAhead();
    $screen->waitThenGo();

    expect($screen->stillToAcknowledge())->toBeTrue()
        ->and($before->said())->toContain('The data is on a network share')
        ->and($before->offers())->toContain(__('uninstall.acknowledge_the_volume'))
        ->and($removing->asked())->toBe(['read:media']);

    $screen->acknowledgeTheVolume();
    $after = WhatTheDeviceWouldDraw::by($screen);
    $screen->goAhead();

    expect($after->said())->toContain(__('uninstall.volume_acknowledged'))
        ->and($after->offers())->not->toContain(__('uninstall.acknowledge_the_volume'))
        ->and($removing->asked())->toBe(['read:media', 'take:media:media-3-lines:go_ahead_now']);
});

it('owes no acknowledgement where the reading names no volume, or there is no reading', function (): void {
    $screen = aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff()), WhichRemoval::Services);

    expect($screen->stillToAcknowledge())->toBeFalse()
        ->and(theTakingItOffScreen(aStackReadingTheRemoval(aReadingOfTakingItOff()))->stillToAcknowledge())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('uninstall.volume'));
});

it('lets go of the volume acknowledged, and the reading, when another removal is chosen', function (): void {
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff(WhichRemoval::Media, volume: 'On a drive that unplugs'));
    $screen = aRemovalChosenAndRead($removing, WhichRemoval::Media);
    $screen->acknowledgeTheVolume();
    $screen->choose('services');

    expect($screen->volumeAcknowledged)->toBeFalse()
        ->and($screen->surveyed)->toBeNull();

    $screen->answer();
    $screen->acknowledgeTheVolume();
    $screen->chooseAgain();

    expect($screen->volumeAcknowledged)->toBeFalse()
        ->and($screen->surveyed)->toBeNull()
        ->and($screen->tier)->toBe('')
        ->and($screen->answer()->chosen)->toBeFalse();
});

it('says, before the configuration is agreed to, the copy the stack takes first and that this app\'s way in goes with it', function (): void {
    $copy = WhatTheDeviceWouldDraw::by(aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff(WhichRemoval::Configuration, copyFirst: 'A copy of the configuration is taken first')), WhichRemoval::Configuration));
    $none = WhatTheDeviceWouldDraw::by(aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff(WhichRemoval::Configuration)), WhichRemoval::Configuration));
    $services = WhatTheDeviceWouldDraw::by(aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff()), WhichRemoval::Services));

    expect($copy->said())->toContain(__('uninstall.copy_first', ['said' => 'A copy of the configuration is taken first']))
        ->and($copy->said())->toContain(__('uninstall.ends_this_session'))
        ->and($copy->offers())->not->toContain(__('uninstall.take_a_copy'))
        ->and($none->said())->toContain(__('uninstall.no_copy_first'))
        ->and($none->offers())->toContain(__('uninstall.take_a_copy'))
        ->and($none->said())->toContain(__('uninstall.ends_this_session'))
        ->and($services->said())->not->toContain(__('uninstall.ends_this_session'))
        ->and($services->said())->not->toContain(__('uninstall.no_copy_first'));
});

it('says after the configuration went that reaching the machine again needs it set up and paired again, and offers nothing more', function (): void {
    $removing = aStackTakingItOffTo(aReadingOfTakingItOff(WhichRemoval::Configuration), WhereTakingItOffGot::complete(NamedOnTheManifest::under('gone', 'config'), NamedOnTheManifest::under('credentials')));
    $screen = aRemovalChosenAndRead($removing, WhichRemoval::Configuration);
    $screen->goAhead();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('uninstall.after_configuration'));

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->endsThisSession)->toBeTrue()
        ->and($drawn->said())->toContain(__('uninstall.after_configuration'))
        ->and($drawn->offers())->not->toContain(__('uninstall.choose_again'));
});

it('says an unread answer after the configuration\'s yes is the way in having gone, lets the session go, and keeps the pairing', function (): void {
    $keychain = AKeychainInMemory::working();
    $stacks = StacksInMemory::holding(theMachineLemonfiberLeaves());
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff(WhichRemoval::Configuration), WhatBecameOfTheUninstall::underway(Job::named('j-1')), WhatBecameOfTheUninstall::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)));
    $screen = theTakingItOffScreen($removing, keychain: $keychain, stacks: $stacks);
    $screen->choose('configuration');
    $screen->answer();
    $screen->goAhead();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('uninstall.unread_after_yes'))
        ->and($drawn->said())->toContain(__('uninstall.after_configuration'))
        ->and($drawn->said())->not->toContain(__('connection.session_has_ended'))
        ->and($drawn->said())->not->toContain(__(KindOfObstacle::CredentialWasRefused->said()))
        ->and($keychain->isHolding(theMachineLemonfiberLeaves()->id()))->toBeFalse()
        ->and($stacks->holdsAny())->toBeTrue()
        ->and(everythingTheUninstallScreenHolds($screen->answer()))->toBe(nothingHeldOfTakingItOff(['wasAgreed' => true, 'endsThisSession' => true]));
});

it('says the stack has no outcome for a yes it no longer knows, the way in gone with it where the configuration was agreed to', function (): void {
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff(WhichRemoval::Configuration), WhatBecameOfTheUninstall::underway(Job::named('j-1')), WhatBecameOfTheUninstall::ended());
    $screen = aRemovalChosenAndRead($removing, WhichRemoval::Configuration);
    $screen->goAhead();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('uninstall.no_outcome'))
        ->and($drawn->said())->toContain(__('uninstall.after_configuration'))
        ->and($drawn->offers())->toContain(__('uninstall.read_again'))
        ->and($screen->following)->toBeNull();

    $screen->again();
    $screen->answer();

    expect($removing->asked())->toBe(['read:configuration', 'take:configuration:configuration-3-lines:go_ahead_now', 'after:j-1', 'read:configuration'])
        ->and($screen->agreedTo)->toBe('');
});

it('says no outcome without the way in where another removal was agreed to', function (): void {
    $screen = aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff(), WhatBecameOfTheUninstall::ended()), WhichRemoval::Services);
    $screen->goAhead();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('uninstall.after_configuration'))
        ->and(everythingTheUninstallScreenHolds($screen->answer()))->toBe(nothingHeldOfTakingItOff(['wasAgreed' => true, 'hasEnded' => true]));
});

it('draws a refusal in the stack\'s words, and offers choosing again rather than sending it again', function (): void {
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff(), WhatBecameOfTheUninstall::refused('The reading you agreed to no longer stands'));
    $screen = aRemovalChosenAndRead($removing, WhichRemoval::Services);
    $screen->goAhead();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('uninstall.refused'))
        ->and($drawn->said())->toContain('The reading you agreed to no longer stands')
        ->and($drawn->offers())->toContain(__('uninstall.choose_again'))
        ->and($drawn->offers())->not->toContain(__('uninstall.agree.services'));

    $screen->chooseAgain();

    expect($screen->answer()->chosen)->toBeFalse()
        ->and($removing->asked())->toBe(['read:services', 'take:services:services-3-lines:go_ahead_now']);
});

it('says whether it was taken off could not be read where the yes met something, and never sends it again on its own', function (): void {
    $keychain = AKeychainInMemory::working();
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff(), WhatBecameOfTheUninstall::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $screen = aRemovalChosenAndRead($removing, WhichRemoval::Services, keychain: $keychain);
    $screen->goAhead();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('uninstall.unread_after_yes'))
        ->and($drawn->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($drawn->said())->not->toContain(__('uninstall.after_configuration'))
        ->and($drawn->offers())->toContain(__('health.ask_again'))
        ->and($keychain->isHolding(theMachineLemonfiberLeaves()->id()))->toBeTrue();

    $screen->again();
    $screen->answer();

    expect($removing->asked())->toBe(['read:services', 'take:services:services-3-lines:go_ahead_now', 'read:services']);
});

it('asks after the same work again where following it met something, and changes nothing else while it runs', function (): void {
    $removing = aStackReadingTheRemoval(
        aReadingOfTakingItOff(),
        WhatBecameOfTheUninstall::underway(Job::named('j-1')),
        WhatBecameOfTheUninstall::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        WhatBecameOfTheUninstall::underway(Job::named('j-1')),
    );
    $screen = aRemovalChosenAndRead($removing, WhichRemoval::Services);
    $screen->goAhead();
    $screen->whileItRuns();
    $met = $screen->answer();
    $screen->again();
    $screen->answer();
    $screen->choose('media');
    $screen->chooseAgain();

    expect($removing->asked())->toBe(['read:services', 'take:services:services-3-lines:go_ahead_now', 'after:j-1', 'after:j-1'])
        ->and($met->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($screen->tier)->toBe('services')
        ->and($screen->agreedTo)->toBe('services')
        ->and($screen->following)->toBe('j-1');
});

it('sends the yes once, however often it is tapped', function (): void {
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff(), WhatBecameOfTheUninstall::underway(Job::named('j-1')));
    $screen = aRemovalChosenAndRead($removing, WhichRemoval::Services);
    $screen->goAhead();
    $screen->goAhead();
    $screen->waitThenGo();

    expect($removing->asked())->toBe(['read:services', 'take:services:services-3-lines:go_ahead_now']);
});

it('sends no yes where nothing was read, or what came back was not a reading', function (): void {
    $unread = aStackReadingTheRemoval(aReadingOfTakingItOff());
    theTakingItOffScreen($unread)->goAhead();

    $answered = AnUninstall::of(aReadingOfTakingItOff(), WhereTakingItOffGot::rehearsed());
    $notAReading = AStackThatTakesItOff::reading($answered);
    $screen = aRemovalChosenAndRead($notAReading, WhichRemoval::Services);
    $held = $screen->surveyed;
    $screen->goAhead();
    $screen->surveyed = $answered;
    $screen->goAhead();

    expect($unread->asked())->toBe([])
        ->and($held)->toBeNull()
        ->and($notAReading->asked())->toBe(['read:services'])
        ->and($screen->agreedTo)->toBe('');
});

it('asks after nothing while nothing is running', function (): void {
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff());
    $screen = aRemovalChosenAndRead($removing, WhichRemoval::Services);
    $screen->whileItRuns();
    $screen->answer();

    expect($removing->asked())->toBe(['read:services']);
});

it('a session that has ended asks the stack nothing, before the yes and at it', function (): void {
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff());
    $screen = theTakingItOffScreen($removing, signedIn: false);
    $screen->choose('services');

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('connection.session_has_ended'))
        ->and($removing->asked())->toBe([]);

    $screen->surveyed = AnUninstall::of(aReadingOfTakingItOff(), WhereTakingItOffGot::surveyed());
    $screen->goAhead();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($removing->asked())->toBe([]);
});

it('a credential the stack refused while reading signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theTakingItOffScreen(AStackThatTakesItOff::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), keychain: $keychain);
    $screen->choose('stop');

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theMachineLemonfiberLeaves()->id()))->toBeFalse();
});

it('holds each state and only its own', function (): void {
    $four = theTakingItOffScreen(aStackReadingTheRemoval(aReadingOfTakingItOff()));
    $four->chooseAgain();
    $read = aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff()), WhichRemoval::Services);
    $met = aRemovalChosenAndRead(AStackThatTakesItOff::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), WhichRemoval::Services);
    $signedOut = theTakingItOffScreen(aStackReadingTheRemoval(aReadingOfTakingItOff()), signedIn: false);
    $signedOut->choose('services');
    $running = aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff(), WhatBecameOfTheUninstall::underway(Job::named('j-1'))), WhichRemoval::Services);
    $running->goAhead();
    $refused = aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff(), WhatBecameOfTheUninstall::refused('No')), WhichRemoval::Services);
    $refused->goAhead();
    $metAfter = aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff(), WhatBecameOfTheUninstall::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))), WhichRemoval::Services);
    $metAfter->goAhead();
    $done = aRemovalChosenAndRead(aStackTakingItOffTo(aReadingOfTakingItOff(), WhereTakingItOffGot::partial(
        NamedOnTheManifest::under('gone', 'lemonfiber-gluetun'),
        NamedOnTheManifest::under('credentials', 'The VPN key'),
        WhatWasLeftBehind::of(SomethingLeftBehind::named('lemonfiber-net', 'In use', 'docker network rm lemonfiber-net')),
    )), WhichRemoval::Services);
    $done->goAhead();
    $done->whileItRuns();
    $media = aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff(WhichRemoval::Media, HowMuchWasRead::notEverything('Slow'), [SomethingStillComing::named('A film', 40)], 'On a share', 'Unused')), WhichRemoval::Media);
    $mediaHeld = theServicesReadingAsHeld();
    $mediaHeld[0] = 'uninstall.tier.media';
    $mediaHeld[1] = 'uninstall.agree.media';
    $mediaHeld[2] = true;
    $mediaHeld[6] = false;
    $mediaHeld[7] = ['Slow'];
    $mediaHeld[12] = [['A film', 40]];
    $mediaHeld[14] = 'On a share';
    $mediaHeld[15] = 'Unused';
    $configuration = aRemovalChosenAndRead(aStackReadingTheRemoval(aReadingOfTakingItOff(WhichRemoval::Configuration)), WhichRemoval::Configuration);
    $configurationHeld = theServicesReadingAsHeld();
    $configurationHeld[0] = 'uninstall.tier.configuration';
    $configurationHeld[1] = 'uninstall.agree.configuration';
    $configurationHeld[3] = true;

    expect(everythingTheUninstallScreenHolds($four->answer()))->toBe(nothingHeldOfTakingItOff(['chosen' => false]))
        ->and(everythingTheUninstallScreenHolds($read->answer()))->toBe(nothingHeldOfTakingItOff(['reading' => theServicesReadingAsHeld()]))
        ->and(everythingTheUninstallScreenHolds($met->answer()))->toBe(nothingHeldOfTakingItOff(['cameBack' => false, 'met' => KindOfObstacle::StackDidNotAnswer->said()]))
        ->and(everythingTheUninstallScreenHolds($signedOut->answer()))->toBe(nothingHeldOfTakingItOff(['cameBack' => false, 'isSignedIn' => false]))
        ->and(everythingTheUninstallScreenHolds($running->answer()))->toBe(nothingHeldOfTakingItOff(['wasAgreed' => true, 'isWorking' => true]))
        ->and(everythingTheUninstallScreenHolds($refused->answer()))->toBe(nothingHeldOfTakingItOff(['wasAgreed' => true, 'refusal' => 'No']))
        ->and(everythingTheUninstallScreenHolds($metAfter->answer()))->toBe(nothingHeldOfTakingItOff(['cameBack' => false, 'met' => KindOfObstacle::StackDidNotAnswer->said(), 'wasAgreed' => true]))
        ->and(everythingTheUninstallScreenHolds($done->answer()))->toBe(nothingHeldOfTakingItOff([
            'wasAgreed' => true,
            'reading' => theServicesReadingAsHeld(),
            'did' => ['uninstall.did.partial', true, ['lemonfiber-gluetun'], ['The VPN key'], [['lemonfiber-net', 'In use', 'docker network rm lemonfiber-net', '']]],
        ]))
        ->and(everythingTheUninstallScreenHolds($media->answer()))->toBe(nothingHeldOfTakingItOff(['reading' => $mediaHeld]))
        ->and(everythingTheUninstallScreenHolds($configuration->answer()))->toBe(nothingHeldOfTakingItOff(['reading' => $configurationHeld]));
});

it('holds a rehearsal and a finished configuration each as its own', function (): void {
    $rehearsed = aRemovalChosenAndRead(aStackTakingItOffTo(aReadingOfTakingItOff(WhichRemoval::Configuration), WhereTakingItOffGot::rehearsed()), WhichRemoval::Configuration);
    $rehearsed->goAhead();
    $rehearsed->whileItRuns();
    $complete = aRemovalChosenAndRead(aStackTakingItOffTo(aReadingOfTakingItOff(WhichRemoval::Configuration), WhereTakingItOffGot::complete(NamedOnTheManifest::under('gone', 'config'), NamedOnTheManifest::under('credentials'))), WhichRemoval::Configuration);
    $complete->goAhead();
    $complete->whileItRuns();
    $held = theServicesReadingAsHeld();
    $held[0] = 'uninstall.tier.configuration';
    $held[1] = 'uninstall.agree.configuration';
    $held[3] = true;

    expect(everythingTheUninstallScreenHolds($rehearsed->answer()))->toBe(nothingHeldOfTakingItOff(['wasAgreed' => true, 'reading' => $held, 'did' => ['uninstall.did.rehearsed', false, [], [], []]]))
        ->and(everythingTheUninstallScreenHolds($complete->answer()))->toBe(nothingHeldOfTakingItOff(['wasAgreed' => true, 'endsThisSession' => true, 'reading' => $held, 'did' => ['uninstall.did.complete', true, ['config'], [], []]]));
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theTakingItOffScreen(aStackReadingTheRemoval(aReadingOfTakingItOff()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way here and the way to a copy are routes', function (): void {
    $screen = theTakingItOffScreen(aStackReadingTheRemoval(aReadingOfTakingItOff()));
    $stack = theMachineLemonfiberLeaves()->id();

    expect(NativeRouter::resolve(TheMenu::Uninstall->screen()->forTheStack($screen->stack()->id())))->not->toBeNull()
        ->and(TheMenu::Uninstall->screen()->forTheStack($screen->stack()->id()))->toBe(AStacksScreen::Uninstall->forTheStack($stack))
        ->and(TheMenu::Uninstall->screen()->forTheStack($screen->stack()->id()))->toBe(sprintf('/stacks/%s/uninstall', $stack->stored()))
        ->and(NativeRouter::resolve($screen->goes()->ofItself()->changing()->copy()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theTakingItOffScreen(aStackReadingTheRemoval(aReadingOfTakingItOff()))->render()->name())->toBe('operator::taking-it-off-this-machine');
});

it('follows a handle the frame holds with no removal agreed to, and ends no session over it', function (): void {
    $removing = aStackReadingTheRemoval(aReadingOfTakingItOff(), WhatBecameOfTheUninstall::underway(Job::named('j-1')));
    $screen = theTakingItOffScreen($removing);
    $screen->following = 'j-1';

    expect($screen->answer()->isWorking)->toBeTrue()
        ->and($removing->asked())->toBe(['after:j-1'])
        ->and($screen->agreedTo)->toBe('');
});
