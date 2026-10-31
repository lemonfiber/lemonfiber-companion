<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\InternalCertificateReplaceAction;
use Lemonfiber\Sdk\Generated\RefusalCode;
use Modules\Kernel\Api\ACertificateReplacementAgreed;
use Modules\Kernel\Api\CredentialGrantSaysNothing;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\ReplacingTheInternalCertificate;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAReplacementCameTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Internal\TheWorkAsked;

/**
 * Replacing the internal certificate through the SDK: the `internal-certificate-replace` action, read with no offer, sent again with the offer the reading named, and followed.
 *
 * A yes refused because the reading moved answers as moved, so the screen
 * reads it again rather than reporting a failure.
 */
final readonly class CertificateReplacers implements ReplacingTheInternalCertificate
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<WhatAReplacementCameTo>
     */
    public function read(Stack $stack, Session $session): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, new InternalCertificateReplaceAction());
    }

    /**
     * @return WhatBecameOfTheWork<WhatAReplacementCameTo>
     */
    public function replace(Stack $stack, Session $session, ACertificateReplacementAgreed $agreed): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, new InternalCertificateReplaceAction(offer: $agreed->agreement()));
    }

    /**
     * @return WhatBecameOfTheWork<WhatAReplacementCameTo>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): WhatAReplacementCameTo => CredentialsKept::afterAReplacement($envelope));
        } catch (CredentialsIsUnreadable|CredentialGrantSaysNothing|ServiceIsUnnamed $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy, RefusalCode::ReplacementMoved);
    }
}
