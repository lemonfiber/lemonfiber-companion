<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Removing everything lemonfiber keeps on a machine: asked about first, carried out on a yes against that listing, and followed.
 */
interface Forgetting
{
    /**
     * Ask what would go, removing nothing.
     *
     * @return WhatBecameOfTheWork<TheForgetting>
     */
    public function whatWouldGo(Stack $stack, Session $session): WhatBecameOfTheWork;

    /**
     * Remove it, as the listing on the screen showed.
     *
     * @return WhatBecameOfTheWork<TheForgetting>
     */
    public function forget(Stack $stack, Session $session, ForgettingAgreed $agreed): WhatBecameOfTheWork;

    /**
     * What became of either, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<TheForgetting>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
