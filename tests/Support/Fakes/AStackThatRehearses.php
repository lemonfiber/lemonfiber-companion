<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Rehearsing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Kernel\Api\WhatTheRehearsalFound;

/** A stack that rehearses a start, for tests; it records every form it was asked about. */
final class AStackThatRehearses implements Rehearsing
{
    /** @var list<Form> */
    private array $asked = [];

    /** @param Closure(): WhatTheRehearsalFound $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack rehearsing any start as this. */
    public static function with(WhatStartingItWouldComeTo $rehearsal): self
    {
        return new self(static fn(): WhatTheRehearsalFound => WhatTheRehearsalFound::found($rehearsal));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatTheRehearsalFound => WhatTheRehearsalFound::met($why));
    }

    public function whatStarting(Stack $stack, Session $session, Form $form): WhatTheRehearsalFound
    {
        $this->asked[] = $form;

        return ($this->answer)();
    }

    /** @return list<Form> every form a rehearsal was asked about, in order */
    public function asked(): array
    {
        return $this->asked;
    }
}
