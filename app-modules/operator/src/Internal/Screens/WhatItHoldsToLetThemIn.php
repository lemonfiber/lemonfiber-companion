<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\ACredentialRevoke;
use Modules\Kernel\Api\ACredentialRevokeAgreed;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\EndingACredentialGrant;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Safekeeping;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\WhatARevokeCameTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowACredentialRevokeReads;
use Modules\Operator\Internal\Presenters\HowTheCredentialsRead;
use Modules\Operator\Internal\ViewModels\TheCredentialRevokeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheCredentialsTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * The credentials a stack holds to let services in, each with the grants of it to third-party plugins, and ending a grant.
 *
 * Each state is drawn in its own words, so a stale credential, an invalid one
 * and one mid-rotation are three different rows. A credential nothing uses
 * says so. What the store protects against, and what it does not, closes the
 * list.
 *
 * **It sets, changes and shows no value.** None reaches this app, and a
 * credential is replaced at the machine. A grant is ended by reading what
 * ending it would restart first, and the yes carries the offer that reading
 * named; the answer replaces what was read.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatItHoldsToLetThemIn extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::what-it-holds-to-let-them-in';

    /** Every credential and grant, as last read or as ending a grant answered. */
    public ?TheCredentialsHeld $held = null;

    /** The grant being ended, while one is. */
    public ?ACredentialGrant $revoking = null;

    /** What the stack read of ending it, which is what a yes agrees to. */
    public ?ACredentialRevoke $reading = null;

    /** The handle of the work being followed, while there is one. */
    public ?string $following = null;

    /** Where ending a grant has got to, once this frame has asked. Public for the same reason. */
    public ?TheCredentialRevokeTurnedOutToBe $going = null;

    public function __construct(
        private readonly Safekeeping $safekeeping,
        private readonly EndingACredentialGrant $ending,
        private readonly Clock $clock,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** What came back, after where ending a grant has got to; the machine is asked only where nothing it said is held. */
    public function answer(): TheCredentialsTurnedOutToBe
    {
        $this->howItIsGoing();

        return $this->ask();
    }

    /** Ask what ending the grant at that place would do; anywhere else, or while work runs, nothing is asked. */
    public function revoke(int $which): void
    {
        $grant = $this->theGrantAt($which);

        if (is_string($this->following) || ! $grant instanceof ACredentialGrant) {
            return;
        }

        $this->backToTheCredentials();
        $this->revoking = $grant;
        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->ending->read($stack, $session, $grant));
    }

    /** End the grant the reading on the screen described; anywhere else, nothing is sent. */
    public function agree(): void
    {
        $grant = $this->revoking;
        $reading = $this->reading;

        if (! $grant instanceof ACredentialGrant || ! $reading instanceof ACredentialRevoke || ! $reading->readsARevokeOf($grant)) {
            return;
        }

        $agreed = ACredentialRevokeAgreed::after($reading, $grant);
        $this->reading = null;
        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->ending->end($stack, $session, $agreed));
    }

    /** Read the credentials and grants again, because the operator said so. */
    public function again(): void
    {
        $this->held = null;
    }

    /** Leave ending a grant, back to the list as last read. */
    public function backToTheCredentials(): void
    {
        $this->revoking = null;
        $this->reading = null;
        $this->following = null;
        $this->going = null;
    }

    /** Ask after the work again while the stack is at it, at {@see HowOftenAScreenLooks}'s interval. */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->howItIsGoing()->stands->isWorking()) {
            $this->going = null;
        }
    }

    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->howItIsGoing()->stands->isWorking();
    }

    /** Where ending a grant has got to, asked once per frame. */
    public function howItIsGoing(): TheCredentialRevokeTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowACredentialRevokeReads()->notAsked()
            : $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->ending->whatBecameOf($stack, $session, Job::named($following)));
    }

    /** The grant at that place among those last read, or none. */
    private function theGrantAt(int $which): ?ACredentialGrant
    {
        $found = null;

        foreach ($this->held instanceof TheCredentialsHeld ? $this->held->grants() : [] as $at => $grant) {
            $found = $at === $which ? $grant : $found;
        }

        return $found;
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

    /** What the machine said it holds, or what the operator met instead; what ending a grant answered stands for it. */
    private function asked(Stack $stack, Session $session): TheCredentialsTurnedOutToBe
    {
        $held = $this->held;

        if ($held instanceof TheCredentialsHeld) {
            return new HowTheCredentialsRead()->these($held, $this->clock->now());
        }

        return $this->safekeeping->heldOn($stack, $session)->either(
            found: function (TheCredentialsHeld $held): TheCredentialsTurnedOutToBe {
                $this->held = $held;

                return new HowTheCredentialsRead()->these($held, $this->clock->now());
            },
            met: $this->lettingGoIfRefused($stack, new HowTheCredentialsRead()->met(...)),
        );
    }

    /**
     * One act put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheWork<WhatARevokeCameTo> $asking
     */
    private function put(Closure $asking): TheCredentialRevokeTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheCredentialRevokeTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheCredentialRevokeTurnedOutToBe => new HowACredentialRevokeReads()->signedOut(),
        );
    }

    /**
     * What the stack said, as the screen draws it, holding what to follow or to agree to.
     *
     * Every answer carries the credentials and grants as they stand, which
     * replace what was read. A refusal reads them again, so a grant already
     * ended is drawn as gone.
     *
     * @param WhatBecameOfTheWork<WhatARevokeCameTo> $became
     */
    private function shown(WhatBecameOfTheWork $became, Stack $stack): TheCredentialRevokeTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheCredentialRevokeTurnedOutToBe {
                $this->following = $job->shown();

                return new HowACredentialRevokeReads()->running();
            },
            answered: function (WhatARevokeCameTo $came): TheCredentialRevokeTurnedOutToBe {
                $this->following = null;
                $this->held = $came->held();
                $this->reading = $came->revoke();

                return new HowACredentialRevokeReads()->answered($came, $this->clock->now());
            },
            ended: function (): TheCredentialRevokeTurnedOutToBe {
                $this->following = null;

                return new HowACredentialRevokeReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheCredentialRevokeTurnedOutToBe {
                $this->following = null;
                $this->held = null;

                return new HowACredentialRevokeReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheCredentialRevokeTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowACredentialRevokeReads()->met($why);
            },
        );
    }
}
