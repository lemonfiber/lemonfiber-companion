<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A member asking for a title their search found, through the core's own action.
 *
 * Rehearsed first, which sends nothing and answers what the ask would leave of
 * their allowance and whether it would wait. The ask itself carries a key the
 * screen holds, so an ask retried after no answer is carried out once. The
 * core asks as the member the session belongs to.
 */
interface AskingForTitles
{
    /** What asking for this title would come to, sending nothing. */
    public function rehearse(Stack $stack, Session $session, AFoundTitle $title): HowAnAskWent;

    /** Ask for it, once under this key however often it is sent. */
    public function ask(Stack $stack, Session $session, AFoundTitle $title, IdempotencyKey $key): HowAnAskWent;
}
