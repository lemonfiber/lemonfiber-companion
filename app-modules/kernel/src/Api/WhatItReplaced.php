<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * The value a plugin's change replaced, and where that value came from.
 *
 * Its own origin rather than assumed to be the stack's default: before a
 * plugin set a value the operator may have, or another plugin, and calling
 * that value the stack's own would tell somebody putting it back that they are
 * returning to a default when they are returning to a choice.
 *
 * Three cases, because the stack says three things: the value it held, that
 * nothing was set so the default was in force, or that a value is withheld
 * because the setting holds a credential.
 */
final readonly class WhatItReplaced
{
    private function __construct(
        private WhoPutItThere $from,
        private ?string $value,
        private bool $withheld,
    ) {}

    /** It held this value; a blank one is refused. */
    public static function held(string $value, WhoPutItThere $from): self
    {
        if (trim($value) === '') {
            throw AnOriginIsUnnamed::replacingABlank();
        }

        return new self($from, $value, withheld: false);
    }

    /** Nothing was set, so the stack's default was in force. */
    public static function nothingSet(WhoPutItThere $from): self
    {
        return new self($from, null, withheld: false);
    }

    /** A credential, which is never shown. */
    public static function withheld(WhoPutItThere $from): self
    {
        return new self($from, null, withheld: true);
    }

    /** Where the replaced value came from. */
    public function from(): WhoPutItThere
    {
        return $this->from;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(string): T $held
     * @param Closure(): T $nothingSet
     * @param Closure(): T $withheld
     *
     * @return T
     */
    public function whichever(Closure $held, Closure $nothingSet, Closure $withheld): object
    {
        return match (true) {
            $this->withheld => $withheld(),
            $this->value === null => $nothingSet(),
            default => $held($this->value),
        };
    }
}
