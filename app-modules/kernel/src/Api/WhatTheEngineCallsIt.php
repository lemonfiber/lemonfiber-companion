<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The name the container engine reports something under.
 *
 * Deliberately not a {@see ServiceId}, though both wrap the same kind of word.
 * Every verb this app can send takes a `ServiceId`, so giving one to a
 * container the stack never declared would make *start this* spellable — and
 * A container nobody declared is never offered a verb. Two types
 * that cannot be substituted is what turns that from a rule every screen has to
 * remember into a sentence that will not compile.
 *
 * It answers no comparison with a `ServiceId` for the same reason. Asking
 * whether an undeclared container shares a name with a declared service is a
 * real question, and it is not this type's — a match would be something for the
 * stack to explain, not for the app to act on.
 */
final readonly class WhatTheEngineCallsIt
{
    private function __construct(private string $name) {}

    public static function called(string $name): self
    {
        return new self($name);
    }

    public function named(): string
    {
        return $this->name;
    }
}
