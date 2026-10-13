<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\SwitchAction;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Switching;
use Modules\Kernel\Api\TheFormsToSwitchTo;
use Modules\Kernel\Api\TheSwitch;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Internal\TheWorkAsked;

/**
 * Making a set of forms the active set through the SDK: the `switch` action, rehearsed, carried out, and followed.
 */
final readonly class Switchers implements Switching
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<TheSwitch>
     */
    public function rehearse(Stack $stack, Session $session, TheFormsToSwitchTo $forms): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, $this->action($forms)->rehearsed());
    }

    /**
     * @return WhatBecameOfTheWork<TheSwitch>
     */
    public function carryOut(Stack $stack, Session $session, TheFormsToSwitchTo $forms): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, $this->action($forms));
    }

    /**
     * @return WhatBecameOfTheWork<TheSwitch>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): TheSwitch => WhatTheSwitchCameTo::in($envelope));
        } catch (LifecycleIsUnreadable $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function action(TheFormsToSwitchTo $chosen): SwitchAction
    {
        $forms = [];

        foreach ($chosen->forms() as $form) {
            $forms[] = $form->named();
        }

        return new SwitchAction(forms: $forms);
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy);
    }
}
