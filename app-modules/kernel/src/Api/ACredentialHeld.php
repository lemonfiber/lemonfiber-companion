<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One credential a stack holds: what it is, where it stands, who made it and what uses it.
 *
 * **There is no value here, and nowhere to put one.** The stack describes a
 * credential without disclosing it, and this type has no field a value could
 * be carried in.
 *
 * `advisory` is empty where the stack had nothing to say.
 */
final readonly class ACredentialHeld
{
    private function __construct(
        private string $name,
        private WhereACredentialStands $state,
        private WhoMadeACredential $origin,
        private WhatUsesIt $consumers,
        private string $advisory,
    ) {}

    /** What the stack said of one credential; a blank name or a blank advisory is refused. */
    public static function described(
        string $name,
        WhereACredentialStands $state,
        WhoMadeACredential $origin,
        WhatUsesIt $consumers,
        string $advisory,
    ): self {
        if (trim($name) === '') {
            throw CredentialSaysNothing::about('name');
        }

        if ($advisory !== '' && trim($advisory) === '') {
            throw CredentialSaysNothing::about('advisory');
        }

        return new self($name, $state, $origin, $consumers, $advisory);
    }

    /** What it is, in the operator's words. */
    public function name(): string
    {
        return $this->name;
    }

    /** Where it stands. */
    public function state(): WhereACredentialStands
    {
        return $this->state;
    }

    /** Who produced it. */
    public function origin(): WhoMadeACredential
    {
        return $this->origin;
    }

    /** Everything that authenticates with it, possibly nothing. */
    public function consumers(): WhatUsesIt
    {
        return $this->consumers;
    }

    /** What is worth saying about it, or empty. */
    public function advisory(): string
    {
        return $this->advisory;
    }
}
