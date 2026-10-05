<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Safekeeping;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheCredentialsRead;
use Modules\Operator\Internal\ViewModels\TheCredentialsTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * The credentials a stack holds to let services in: where each stands, who made it and what uses it.
 *
 * Each state is drawn in its own words, so a stale credential, an invalid one
 * and one mid-rotation are three different rows. A credential nothing uses
 * says so. What the store protects against, and what it does not, closes the
 * list.
 *
 * **It sets, changes and shows no value.** None reaches this app, and a
 * credential is replaced at the machine. It asks once, when the frame is
 * built.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatItHoldsToLetThemIn extends NativeComponent
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;
    use AsksAgain;

    public const string TEMPLATE = 'operator::what-it-holds-to-let-them-in';

    /**
     * What came back, once the frame has asked.
     *
     * `public`, for the reason {@see WhatIsRunningHere::$answered} gives.
     */
    public ?TheCredentialsTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Safekeeping $safekeeping,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** What came back, asked once per frame. */
    public function answer(): TheCredentialsTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheCredentialsTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheCredentialsTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheCredentialsTurnedOutToBe => new HowTheCredentialsRead()->signedOut(),
        );
    }

    /** What the machine said it holds, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheCredentialsTurnedOutToBe
    {
        return $this->safekeeping->heldOn($stack, $session)->either(
            found: static fn(TheCredentialsHeld $held): TheCredentialsTurnedOutToBe => new HowTheCredentialsRead()->these($held),
            met: $this->lettingGoIfRefused($stack, new HowTheCredentialsRead()->met(...)),
        );
    }
}
