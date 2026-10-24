<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Ending one grant of a credential to a third-party plugin: read first, ended on a yes, and followed.
 *
 * Apart from {@see Safekeeping}, which only reads. What finished work answers
 * with is what ending the grant came to, with every credential and grant
 * after it.
 */
interface EndingACredentialGrant
{
    /**
     * Ask what ending the grant would do, ending nothing.
     *
     * @return WhatBecameOfTheWork<WhatARevokeCameTo>
     */
    public function read(Stack $stack, Session $session, ACredentialGrant $grant): WhatBecameOfTheWork;

    /**
     * End the grant, as the reading agreed to described it.
     *
     * @return WhatBecameOfTheWork<WhatARevokeCameTo>
     */
    public function end(Stack $stack, Session $session, ACredentialRevokeAgreed $agreed): WhatBecameOfTheWork;

    /**
     * What became of either, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<WhatARevokeCameTo>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
