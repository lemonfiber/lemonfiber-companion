<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AskingHowItIsServed;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowItIsServed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowServingReads;
use Modules\Operator\Internal\ViewModels\HowItIsServedAsShown;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * How a stack serves its web surface, and the certificate it presents beside the pin this phone holds, to the operator alone. It offers no replacing.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class HowThisStackIsServed extends NativeComponent
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /** How it is served, once the frame has asked. */
    public ?HowItIsServedAsShown $answered = null;

    public function __construct(
        protected readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        private readonly AskingHowItIsServed $asking,
    ) {}

    /** Let go of the reading, so the next frame takes it again. */
    public function again(): void
    {
        $this->answered = null;
    }

    public function render(): View
    {
        return view('operator::how-this-stack-is-served');
    }

    /** How it is served, asked once. */
    public function answer(): HowItIsServedAsShown
    {
        if ($this->answered instanceof HowItIsServedAsShown) {
            return $this->answered;
        }

        $stack = $this->stack();

        return $this->answered = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowItIsServedAsShown => $this->asking->served($stack, $session)->either(
                said: fn(HowItIsServed $served): HowItIsServedAsShown => new HowServingReads()->this($served, $stack->presents(), $this->listening->clock->now()),
                met: $this->lettingGoIfRefused($stack, new HowServingReads()->met(...)),
            ),
            notHeld: static fn(): HowItIsServedAsShown => new HowServingReads()->signedOut(),
        );
    }
}
