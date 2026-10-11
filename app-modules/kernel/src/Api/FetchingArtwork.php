<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A title's pictures, asked of the core's own API on the session they are drawn for and pinned as every request is.
 *
 * Only the core's paths for a title it listed are asked; no address is
 * composed beyond them.
 */
interface FetchingArtwork
{
    /** The poster of a title the house holds, as the member it is drawn for may see it. */
    public function posterOfAHolding(Stack $stack, Session $session, Whose $whose, HoldingId $title): HowArtworkCame;

    /** The backdrop of a title the house holds, as the member it is drawn for may see it. */
    public function backdropOfAHolding(Stack $stack, Session $session, Whose $whose, HoldingId $title): HowArtworkCame;

    /** The poster of a title a member's search offered. */
    public function posterOfAFoundTitle(Stack $stack, Session $session, AFoundTitle $title): HowArtworkCame;
}
