<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;

/**
 * A paired stack written down, and the session held for it settled.
 *
 * Pairing a machine this device already holds replaces what is held for it:
 * its address, its pinned certificate and its name. What happens to the
 * session depends on one of those. A session was granted over the pinned
 * certificate's key, and a different certificate is a different key, so a
 * re-pairing that changes the pin lets go of the session and the operator signs
 * in again. One that keeps the pin keeps the session, because nothing it was
 * agreed under has changed.
 *
 * **A re-pairing is told from a first pairing here.** The identifier the
 * material carries decides it and nothing else does: an address or a
 * certificate that differs from the one held is what re-pairing exists to
 * change, so neither can say whether this is a machine the device already holds.
 *
 * **Both pairing roads come through here.** Deciding it on each screen would be
 * the same rule written twice, and the copy nobody tests is the one that keeps
 * a session across a new certificate.
 *
 * **The session goes only once the new pin is written.** Were the write
 * refused, the device would still hold the old certificate, the session agreed
 * under it would still be good, and letting it go would sign the operator out
 * of a machine whose pairing never changed.
 */
final readonly class Remembering
{
    public function __construct(
        private Stacks $stacks,
        private SecureStorage $sessions,
    ) {}

    /**
     * Write the stack down, letting go of its session where its certificate changed.
     *
     * Answers what became of it in the operator's terms, which is where a
     * re-pairing is told from a first one: a machine already held is updated
     * rather than added, and the screen says so rather than announcing a second.
     */
    public function stack(Stack $stack): HowThePairingWent
    {
        $went = $this->whatItIs($this->stacks->configured(), $stack);

        return $this->stacks->remember($stack)->either(
            remembered: fn(): HowThePairingWent => $this->settled($stack, $went),
            refused: static fn(WhyAStackCannotBeRemembered $why): HowThePairingWent => HowThePairingWent::refused($why),
        );
    }

    /** A first pairing, or a machine already held paired again with or without a new certificate. */
    private function whatItIs(Configured $held, Stack $stack): HowThePairingWent
    {
        if ($held->wouldRepin($stack)) {
            return HowThePairingWent::PairedAgainOnANewCertificate;
        }

        return $held->knows($stack->id()) ? HowThePairingWent::PairedAgain : HowThePairingWent::Paired;
    }

    /** The session let go of where the certificate it was agreed under is gone. */
    private function settled(Stack $stack, HowThePairingWent $went): HowThePairingWent
    {
        if ($went === HowThePairingWent::PairedAgainOnANewCertificate) {
            $this->sessions->forget($stack->id());
        }

        return $went;
    }
}
