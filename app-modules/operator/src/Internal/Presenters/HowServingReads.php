<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItIsServed;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheCertificateItPresents;
use Modules\Operator\Internal\ViewModels\HowItIsServedAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheCertificateAsShown;

use function sprintf;

/**
 * Turns how a stack serves its web surface into what its screen draws.
 */
final readonly class HowServingReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): HowItIsServedAsShown
    {
        return $this->nothing(HowTheReadingWent::theSessionEnded());
    }

    /** It did not answer, and this is what the operator met. */
    public function met(Obstacle $why): HowItIsServedAsShown
    {
        return $this->nothing(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack said how it is served, read against the pin this phone holds and the frame's moment. */
    public function this(HowItIsServed $served, Fingerprint $pinned, Instant $now): HowItIsServedAsShown
    {
        return $served->whatItPresents(
            presenting: fn(TheCertificateItPresents $certificate): HowItIsServedAsShown => $this->shown($served, $this->certificate($certificate, $pinned, $now)),
            plainly: fn(): HowItIsServedAsShown => $this->shown($served, null),
        );
    }

    private function shown(HowItIsServed $served, ?TheCertificateAsShown $certificate): HowItIsServedAsShown
    {
        return new HowItIsServedAsShown(
            went: HowTheReadingWent::itCameBack(),
            port: sprintf('%d', $served->port()),
            encrypted: $served->isEncrypted() ? 'served.encrypted.yes' : 'served.encrypted.no',
            network: $served->isOnTheNetwork() ? 'served.network.yes' : 'served.network.no',
            named: $served->named(),
            unrecorded: $served->unrecorded(),
            certificate: $certificate,
        );
    }

    private function certificate(TheCertificateItPresents $certificate, Fingerprint $pinned, Instant $now): TheCertificateAsShown
    {
        $from = $certificate->validFrom();
        $until = $certificate->validUntil();

        return new TheCertificateAsShown(
            atAGlance: AtAGlance::of($certificate->fingerprint())->shown(),
            pinned: AtAGlance::of($pinned)->shown(),
            comparing: $pinned->is($certificate->fingerprint()) ? 'served.pin.same' : 'served.pin.different',
            subject: $certificate->subject()->text(),
            subjectCut: $certificate->subject()->isCut(),
            issuer: $certificate->isSelfSigned() ? '' : $certificate->issuer()->text(),
            issuerCut: ! $certificate->isSelfSigned() && $certificate->issuer()->isCut(),
            names: $this->names($certificate),
            namesMore: $certificate->names()->more(),
            from: $from->isAfter($now) ? AgoAsShown::until($from, $now) : AgoAsShown::since($from, $now),
            fromSaid: $from->isAfter($now) ? 'served.valid.from_ahead' : 'served.valid.from',
            until: $until->isAfter($now) ? AgoAsShown::until($until, $now) : AgoAsShown::since($until, $now),
            untilSaid: $until->isAfter($now) ? 'served.valid.until' : 'served.valid.lapsed',
        );
    }

    /** @return list<string> */
    private function names(TheCertificateItPresents $certificate): array
    {
        $names = [];

        foreach ($certificate->names() as $name) {
            $names[] = $name->text();
        }

        return $names;
    }

    private function nothing(HowTheReadingWent $went): HowItIsServedAsShown
    {
        return new HowItIsServedAsShown($went, port: '', encrypted: '', network: '', named: '', unrecorded: '', certificate: null);
    }
}
