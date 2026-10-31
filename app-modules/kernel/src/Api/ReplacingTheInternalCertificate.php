<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Replacing the certificate the media server presents at its internal TLS port: read first, carried out on a yes, and followed.
 *
 * The core makes the new certificate and its key on the machine; neither
 * crosses to this app. What finished work answers with is what the
 * replacement came to, with every credential and grant after it.
 */
interface ReplacingTheInternalCertificate
{
    /**
     * Ask what replacing it would do, replacing nothing.
     *
     * @return WhatBecameOfTheWork<WhatAReplacementCameTo>
     */
    public function read(Stack $stack, Session $session): WhatBecameOfTheWork;

    /**
     * Replace it, as the reading agreed to described it.
     *
     * @return WhatBecameOfTheWork<WhatAReplacementCameTo>
     */
    public function replace(Stack $stack, Session $session, ACertificateReplacementAgreed $agreed): WhatBecameOfTheWork;

    /**
     * What became of either, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<WhatAReplacementCameTo>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
