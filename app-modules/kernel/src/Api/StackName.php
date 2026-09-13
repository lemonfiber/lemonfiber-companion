<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What the operator calls one of their machines.
 *
 * A type rather than a string because it crosses module boundaries, and the
 * mistake a string permits is the one `D2` is written about: a stack's name, a
 * service's name and a form's name are all strings, and passing one where
 * another belongs compiles and ships. It is also the only thing on a stack an
 * operator chose, which makes it the one value here that cannot be derived
 * from anything else if it goes missing.
 *
 * **Refused rather than filled in with an address.** That is the tempting
 * default and it is wrong twice over: `N1-R15` keeps a stack address off every
 * screen, and an operator with two stacks needs to tell them apart by something
 * they picked — "192.168.1.42" and "192.168.1.43" are not two names.
 *
 * Trimmed on the way in, which is the one edit the type makes. Somebody typing
 * a name into a phone produces a trailing space often enough that treating
 * `"The loft "` and `"The loft"` as two names would be a bug reported as the
 * app having lost a stack.
 */
final readonly class StackName
{
    private function __construct(private string $name) {}

    /** The one place a string becomes a name. */
    public static function of(string $name): self
    {
        $called = trim($name);

        if ($called === '') {
            throw StackIsNotNamed::afterPairing();
        }

        return new self($called);
    }

    public function shown(): string
    {
        return $this->name;
    }
}
