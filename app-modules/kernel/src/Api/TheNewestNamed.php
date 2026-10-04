<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The newest of each kind a stack names on its event stream, by what names each and nothing else.
 *
 * What a tab is marked from, without reading the items: the stack says it when
 * a listener arrives and whenever it changes, and the screen that lists the
 * items reads them apart. Each kind is newest first, in the order the stack
 * keeps, and a kind the stack could not read says so rather than arriving
 * empty.
 */
final readonly class TheNewestNamed
{
    /**
     * @param WhatTheStackListed<AReleaseNamed> $releases
     * @param WhatTheStackListed<RequestId>     $requests
     * @param WhatTheStackListed<AProblemNamed> $problems
     */
    public function __construct(
        private WhatTheStackListed $releases,
        private WhatTheStackListed $requests,
        private WhatTheStackListed $problems,
    ) {}

    /**
     * The newest releases in the stack's record, newest first.
     *
     * @return WhatTheStackListed<AReleaseNamed>
     */
    public function releases(): WhatTheStackListed
    {
        return $this->releases;
    }

    /**
     * The household's newest requests, highest number first.
     *
     * @return WhatTheStackListed<RequestId>
     */
    public function requests(): WhatTheStackListed
    {
        return $this->requests;
    }

    /**
     * The checks most recently found wrong, the most recent onset first.
     *
     * @return WhatTheStackListed<AProblemNamed>
     */
    public function problems(): WhatTheStackListed
    {
        return $this->problems;
    }
}
