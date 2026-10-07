<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SelfChecking;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheReadingWaitsAFrame;
use Modules\Kernel\Api\WhatWasFoundOfItself;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatIsRunningHere;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatChecksItself;
use Tests\Support\Fakes\AStackThatOffers;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// The frame a screen draws where asking the stack what it serves was the
// frame's one reading: drawn the same way for every screen, asking for the
// next frame at once, and holding nothing of the reading that waited.
//
// Here rather than in a module's own tests because a screen renders, and
// rendering needs the application.

/** The stack a waiting screen is about. Named for this file. */
function aStackAScreenWaitsOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('w', Nonce::SHORTEST))),
        StackName::of('Waited on'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/**
 * A stack whose first reading waits a frame, and which answers every reading after with this, each asking counted.
 *
 * @param ArrayObject<int, string> $askings
 */
function aStackWhoseFirstReadingWaits(Obstacle $then, ArrayObject $askings = new ArrayObject()): SelfChecking
{
    return new readonly class ($then, $askings) implements SelfChecking {
        /** @param ArrayObject<int, string> $askings */
        public function __construct(private Obstacle $then, private ArrayObject $askings) {}

        public function checkedOn(Stack $stack, Session $session): WhatWasFoundOfItself
        {
            $this->askings->append($stack->id()->stored());

            if ($this->askings->count() === 1) {
                throw TheReadingWaitsAFrame::becauseTheStackWasAskedWhatItServes();
            }

            return AStackThatChecksItself::met($this->then)->checkedOn($stack, $session);
        }
    };
}

/** A screen reading the stack through this, with the stack's offers asked of this. */
function aScreenThatWaits(SelfChecking $checking, AStackThatOffers $offering): WhatIsRunningHere
{
    $stack = aStackAScreenWaitsOn();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $screen = new WhatIsRunningHere(
        $checking,
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain, offering: $offering),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/**
 * How soon the frame drawn last asked to be drawn again, in milliseconds, if it asked.
 *
 * @return list<int>
 */
function howSoonAWaitingScreenAskedAgain(NativeComponent $screen): array
{
    $asked = Closure::bind(static fn(NativeComponent $drawn): array => array_keys($drawn->bladePollDeadlines), null, NativeComponent::class)($screen);

    return array_values(array_filter($asked, is_int(...)));
}

it('draws the frame that waits instead of the screen, asking for the next frame at once', function (): void {
    $screen = aScreenThatWaits(aStackWhoseFirstReadingWaits(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), AStackThatOffers::everything());

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->not->toContain(__('health.ask_again'))
        ->and(howSoonAWaitingScreenAskedAgain($screen))->toBe([16]);
});

it('holds nothing of the reading that waited, so the next frame takes it and draws the screen', function (): void {
    /** @var ArrayObject<int, string> $askings */
    $askings = new ArrayObject();
    $screen = aScreenThatWaits(aStackWhoseFirstReadingWaits(Obstacle::of(KindOfObstacle::StackDidNotAnswer), $askings), AStackThatOffers::everything());

    WhatTheDeviceWouldDraw::by($screen);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('health.ask_again'))
        ->and($askings->count())->toBe(2)
        ->and(howSoonAWaitingScreenAskedAgain($screen))->toBe([]);
});

it('ends a tap whose reading waits there, rather than putting the package\'s error on the glass', function (): void {
    $screen = aScreenThatWaits(aStackWhoseFirstReadingWaits(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), AStackThatOffers::everything());

    // The tap as the device sends it: a press on a callback the screen holds.
    $tapped = Closure::bind(static function (NativeComponent $tapped): void {
        $tapped->nativeCallbacks = new CallbackRegistry();
        $tapped->dispatchUiEvent(['callback_id' => $tapped->nativeCallbacks->register('answer'), 'type' => 1]);
    }, null, NativeComponent::class);

    $tapped($screen);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.ask_again'));
});

it('opens once, on its first frame, which is where what was said before a break is asked again', function (): void {
    $offering = AStackThatOffers::everything();
    $screen = aScreenThatWaits(aStackWhoseFirstReadingWaits(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), $offering);

    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);

    expect($offering->screensOpened())->toBe(1);
});
