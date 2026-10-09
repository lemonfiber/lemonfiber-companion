<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackName;

/**
 * Pairing material becoming a machine this device knows.
 *
 * The step between somebody reading a code off their stack and the app holding
 * a stack. Everything the material carries travels across unchanged — the
 * address it named, the certificate it promised and the identity the stack
 * gave itself — and the one thing added here is the name the operator picked.
 *
 * **The fingerprint is carried rather than looked up.** It
 * comes from the material and never from the network: a fingerprint learned
 * from the connection it is meant to validate proves nothing at all. Nothing
 * in this class reads anything.
 *
 * **The identity is the stack's, and nothing here decides it.** It is not the
 * address, because trust is pinned to the stack rather than to where it
 * answers, and not the fingerprint, which a certificate renewal replaces. It is
 * the identifier the stack wrote into the material, which stays the same across
 * every code that machine issues. So scanning a second code for a machine this
 * device holds names the machine it holds, and writing it down replaces what is
 * held for it instead of adding a second.
 *
 * **Two roads, and they are not equally safe.** A
 * camera comparing a digest is the software comparison the pairing design
 * was built around; a person typing one is the route required to
 * exist on a device whose operator declined the camera, and it has no software
 * comparison in it. So {@see Stack()} is the scanned road and refuses typed
 * material outright, and {@see confirmed()} is the road that takes the
 * operator's own answer. The *must not proceed on an unconfirmed
 * fingerprint* is then a thing the type system says rather than a thing a
 * reviewer checks.
 *
 * **Re-pairing is this same road.** Material for a machine already held
 * carries its identity, so it arrives here exactly as a first pairing does, and
 * the difference is made where the stack is written down.
 */
final readonly class Introducing
{
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

    /** What the material carries, under the name the operator picked. */
    private function built(Pairing $said, StackName $called): Stack
    {
        return Stack::of(
            $said->stack(),
            $called,
            $said->at(),
            $said->presenting(),
        );
    }
}
