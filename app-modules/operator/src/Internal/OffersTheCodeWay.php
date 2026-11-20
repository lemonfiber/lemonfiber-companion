<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Connection\Api\HowTheSignInWent;
use Modules\Connection\Api\TheWayIn;
use Modules\Kernel\Api\StackId;
use Modules\Operator\Internal\ViewModels\TheCodeWay;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeComponent;

/**
 * The way to sign in by a code, as a member's password sign-in offers it.
 *
 * @phpstan-require-extends NativeComponent
 */
trait OffersTheCodeWay
{
    /** The way that last let a member in on the house, read once. */
    private ?TheWayIn $ledWith = null;

    /** Whether the code way is offered, whether it leads, and whether a refused password stands beside it. */
    public function theCodeWay(): TheCodeWay
    {
        $house = $this->houseTheCodeWayIsFor();

        if (! $house instanceof StackId) {
            return TheCodeWay::notOffered();
        }

        $this->ledWith ??= $this->waysIn->ledWithOn($house);

        return TheCodeWay::offered(
            leads: $this->ledWith === TheWayIn::Code && $this->went === HowTheSignInWent::NotYet,
            besideARefusal: $this->went === HowTheSignInWent::ThePairWasRefused,
        );
    }

    /** Where the code way goes, or nowhere while it is not offered. */
    public function codeWayAt(): string
    {
        $house = $this->houseTheCodeWayIsFor();

        return $house instanceof StackId ? AStacksScreen::SignInByCode->forTheStack($house) : '';
    }

    /** The house a member is signing in to with a password, or nothing where the code way is not offered. */
    abstract protected function houseTheCodeWayIsFor(): ?StackId;
}
