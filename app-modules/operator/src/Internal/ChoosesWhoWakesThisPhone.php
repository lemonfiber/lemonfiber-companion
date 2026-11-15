<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Connection\Api\WhyWakingStayedOff;
use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\HowThisPhoneIsWoken;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowWakingReads;
use Modules\Operator\Internal\ViewModels\ASettingAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * Wake through: which UnifiedPush distributor wakes this phone, on the app's settings, where more than one is installed.
 *
 * Changing it turns waking off on every stack that wakes the phone, and then
 * on again through the new distributor, so no stack keeps the old endpoint. A
 * session a stack refuses on the way is let go of.
 *
 * The screen that uses it takes {@see \Modules\Connection\Api\ChoosingWhoWakesThisPhone}
 * as a protected `$wakers`, {@see \Modules\Connection\Api\WakingThisPhone} as a
 * protected `$woken`, {@see \Modules\Kernel\Api\Stacks} as `$stacks` and
 * {@see \Modules\Kernel\Api\SecureStorage} as `$storage`.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ChoosesWhoWakesThisPhone
{
    /** The distributors to choose among and the one chosen, with nothing offered where fewer than two are installed. */
    public function wakeThroughChoice(): ASettingAsShown
    {
        return new HowWakingReads()->choiceAmong($this->wakers->who());
    }

    /** The operator chose another distributor; every stack that wakes the phone is registered again through it. A package not installed changes nothing. */
    public function wakeThroughChosen(string $package): void
    {
        foreach ($this->wakers->installedAs(ADistributor::of($package, '')) as $through) {
            if (! $this->wakers->wakesThrough($through)) {
                $this->wakers->choose($through);
                $this->moveEveryStackTo($through);
            }
        }
    }

    /** Turn waking off on every stack that wakes the phone, then on again through `$through`. */
    private function moveEveryStackTo(ADistributor $through): void
    {
        $moving = [];

        foreach ($this->stacks->configured() as $stack) {
            $this->storage->resume($stack->id())->either(
                held: function (Session $session, Whose $whose) use ($stack, &$moving): HowThisPhoneIsWoken {
                    $woken = $this->woken->forTheStack($stack->id());

                    if ($woken->isOn() && $whose->is(Whose::theOperator())) {
                        $moving[] = [$stack, $session];

                        return $this->woken->turnOff($stack, $session);
                    }

                    return $woken;
                },
                notHeld: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
            );
        }

        foreach ($moving as [$stack, $session]) {
            $this->woken->turnOn($stack, $session, $through)->either(
                on: static fn(): WhyWakingStayedOff => WhyWakingStayedOff::noKeyName(),
                off: fn(WhyWakingStayedOff $why): WhyWakingStayedOff => $why->either(
                    noKeyName: static fn(): WhyWakingStayedOff => $why,
                    noEndpoint: static fn(): WhyWakingStayedOff => $why,
                    refused: static fn(): WhyWakingStayedOff => $why,
                    met: $this->lettingGoIfRefused($stack, static fn(): WhyWakingStayedOff => $why),
                    notKept: static fn(): WhyWakingStayedOff => $why,
                ),
            );
        }
    }
}
