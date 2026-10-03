<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Everything a stack lists that could be new, each kind newest first.
 *
 * A release by its place in the stack's record, a request by its number, and a
 * check found wrong by when it went wrong: the orders the stack keeps, so the
 * phone compares nothing the stack did not order. A kind the stack could not
 * read says so rather than arriving empty.
 */
final readonly class TheNewsOfAStack
{
    /**
     * @param WhatTheStackListed<AReleaseListed> $releases
     * @param WhatTheStackListed<ARequestListed> $requests
     * @param WhatTheStackListed<AProblemListed> $problems
     */
    public function __construct(
        private WhatTheStackListed $releases,
        private WhatTheStackListed $requests,
        private WhatTheStackListed $problems,
    ) {}

    /**
     * The releases in the stack's record, newest first.
     *
     * @return WhatTheStackListed<AReleaseListed>
     */
    public function releases(): WhatTheStackListed
    {
        return $this->releases;
    }

    /**
     * What the household asked for, highest number first.
     *
     * @return WhatTheStackListed<ARequestListed>
     */
    public function requests(): WhatTheStackListed
    {
        return $this->requests;
    }

    /**
     * The checks found wrong, the most recent onset first.
     *
     * @return WhatTheStackListed<AProblemListed>
     */
    public function problems(): WhatTheStackListed
    {
        return $this->problems;
    }
}
