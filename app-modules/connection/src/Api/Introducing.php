<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;

/**
 * Pairing material becoming a machine this device knows.
 *
 * The step between somebody reading a code off their stack and the app holding
 * a stack. Two things travel across unchanged — the address the material named
 * and the certificate it promised — and exactly one is decided here, which is
 * the identity.
 *
 * **The fingerprint is carried rather than looked up.** It
 * comes from the material and never from the network: a fingerprint learned
 * from the connection it is meant to validate proves nothing at all. Nothing
 * in this class reads anything.
 *
 * **The identity is minted from entropy, and that is three decisions in one.**
 * It is not the address, because trust is pinned to the stack rather than
 * to where it answers — a stack keyed on its address becomes a different stack
 * the morning the router renumbers it, and every reading retained against it
 * silently belongs to something else. It is not the fingerprint either, which
 * changes on a certificate renewal that `ADR-0018` makes a re-pairing rather
 * than a new machine — an identity derived from it would orphan the stack's
 * own history at the exact moment the operator was told nothing had happened.
 * And it is not taken from the wire: a server that has never met this app
 * cannot have named itself to it, and two stacks that never met each other
 * could otherwise arrive carrying the same identifier.
 *
 * **Two roads, and they are not equally safe.** A
 * camera comparing a digest is the software comparison `ADR-0018` chose the
 * whole design around; a person typing one is the route required to
 * exist on a device whose operator declined the camera, and it has no software
 * comparison in it. So {@see Stack()} is the scanned road and refuses typed
 * material outright, and {@see confirmed()} is the road that takes the
 * operator's own answer. The *must not proceed on an unconfirmed
 * fingerprint* is then a thing the type system says rather than a thing a
 * reviewer checks.
 *
 * **Re-pairing is deliberately not here.** It is the remedy for
 * a certificate that changed, and it differs from this by one thing: the
 * identity is kept rather than minted, because the machine is the one the
 * operator has been using. Writing it now would mean a second pair of roads —
 * four methods — for a screen this app does not have, which is the argument
 * {@see \Modules\Kernel\Api\Stacks} makes for having no `forget()` yet. It
 * arrives with the screen that offers it.
 */
final readonly class Introducing
{
    public function __construct(private Entropy $entropy) {}

    /**
     * The stack this scanned material describes, under a name the operator picked.
     *
     * Scanned material only. A camera reading a code is the software comparison
     * the confirmation contrasts with: the digest arrived in the payload rather than
     * being read off a screen by a person, so there is nothing for anybody to
     * confirm and nothing this class would be assuming.
     *
     * Raises {@see PairingWasNotConfirmed} for typed material, which is this
     * module's exception rather than its rule: there is no half-paired stack to
     * carry on with, so there is nothing for a caller to do with a value except
     * stop. A `Reach`-shaped outcome here would make *proceed anyway* a branch
     * somebody could take.
     *
     * The name is asked for rather than derived. A device holds more than one
     * stack and a person has to tell them apart, and the two things the material
     * carries are both unusable for that — `192.168.1.42` and `192.168.1.43` are
     * not two names, and a certificate digest is sixty-four hex characters.
     */
    public function stack(Pairing $said, StackName $called): Stack
    {
        if ($said->how() !== HowItWasRead::Scanned) {
            throw PairingWasNotConfirmed::becauseItWasTyped();
        }

        return $this->built($said, $called);
    }

    /**
     * The same, where the operator has confirmed the fingerprint they were shown.
     *
     * The typed road, and the reason {@see
     * FingerprintWasConfirmed} is a type rather than a flag: a caller that has
     * not been told yes has nothing to pass, so `confirmed: false` is a sentence
     * with no spelling and the confirmation is structural rather than checked.
     *
     * **The confirmation has to be about this material.** It carries the form
     * that was compared, and a confirmation naming another certificate is
     * refused — which is not a theoretical case: a screen that re-parses after
     * the operator edits the code, and still holds the answer they gave about
     * what it read a moment ago, is one `if` away from pairing the new material
     * on the strength of the old confirmation. That check is why this takes a
     * value rather than a `true` nobody can interrogate.
     *
     * Scanned material is accepted here too. A screen that both scanned and
     * showed the fingerprint has done more than is asked rather than less,
     * and refusing the extra care would be this class having an opinion about
     * how careful a surface may be.
     */
    public function confirmed(Pairing $said, StackName $called, FingerprintWasConfirmed $by): Stack
    {
        if (! $by->covers(AtAGlance::of($said->presenting()))) {
            throw PairingWasNotConfirmed::aboutAnotherCertificate();
        }

        return $this->built($said, $called);
    }

    /** The two things the material carries, under an identity minted for it. */
    private function built(Pairing $said, StackName $called): Stack
    {
        return Stack::of(
            StackId::of($this->entropy->nonce()),
            $called,
            $said->at(),
            $said->presenting(),
        );
    }
}
