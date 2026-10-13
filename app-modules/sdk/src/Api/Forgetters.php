<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ForgetAction;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Forgetting;
use Modules\Kernel\Api\ForgettingAgreed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheForgetting;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Internal\TheWorkAsked;

/**
 * Removing everything lemonfiber keeps through the SDK: the `forget` action without the yes, then with it, and followed.
 */
final readonly class Forgetters implements Forgetting
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<TheForgetting>
     */
    public function whatWouldGo(Stack $stack, Session $session): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, new ForgetAction());
    }

    /**
     * @return WhatBecameOfTheWork<TheForgetting>
     */
    public function forget(Stack $stack, Session $session, ForgettingAgreed $agreed): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, new ForgetAction(confirm: true));
    }

    /**
     * @return WhatBecameOfTheWork<TheForgetting>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): TheForgetting => WhatForgettingCameTo::in($envelope));
        } catch (StoredIsUnreadable $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy);
    }
}
