<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\CredentialsRevokeAction;
use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\ACredentialRevokeAgreed;
use Modules\Kernel\Api\CredentialGrantSaysNothing;
use Modules\Kernel\Api\EndingACredentialGrant;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatARevokeCameTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Internal\TheWorkAsked;

/**
 * Ending one grant through the SDK: the `credentials-revoke` action, read with no offer, sent again with the offer the reading named, and followed.
 */
final readonly class GrantEnders implements EndingACredentialGrant
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<WhatARevokeCameTo>
     */
    public function read(Stack $stack, Session $session, ACredentialGrant $grant): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, new CredentialsRevokeAction(plugin: $grant->plugin()->named(), credential: $grant->credential()));
    }

    /**
     * @return WhatBecameOfTheWork<WhatARevokeCameTo>
     */
    public function end(Stack $stack, Session $session, ACredentialRevokeAgreed $agreed): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, new CredentialsRevokeAction(
            offer: $agreed->agreement(),
            plugin: $agreed->grant()->plugin()->named(),
            credential: $agreed->grant()->credential(),
        ));
    }

    /**
     * @return WhatBecameOfTheWork<WhatARevokeCameTo>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): WhatARevokeCameTo => CredentialsKept::afterARevoke($envelope));
        } catch (CredentialsIsUnreadable|CredentialGrantSaysNothing|ServiceIsUnnamed $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy);
    }
}
