<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One of the things running on a stack, named the way the stack names it.
 *
 * A value object over the wire's string for {@see Ability}'s reason and not
 * {@see Category}'s: the set is declared by the machine at runtime, and an
 * operator with nineteen services has nineteen names this build has never heard
 * of. An enum here would be a closed list of what this release knew about, and
 * a service it had not heard of would arrive as a `tryFrom` returning null —
 * which reads as *this stack has no such service* and means *this app does not
 * recognise it*. `ARCH-R79` separates those by name.
 *
 * **The name is the identifier, not a label.** It is what a log window is asked
 * for by, what a finding is matched against and what an operator types into a
 * terminal when they give up on the app — so it is carried exactly as the stack
 * spelled it, less the whitespace around it, and never title-cased for display.
 * A service called `gluetun` shown as *Gluetun* is a name that does not work
 * anywhere else.
 *
 * Blank is refused here rather than at each call site, as {@see Check} does it:
 * a name nobody can read is a name that cannot be matched against the stack,
 * and an engine producing one has a fault.
 */
final readonly class ServiceId
{
    private function __construct(private string $named) {}

    /** One of the services, named as the stack names it. */
    public static function called(string $service): self
    {
        $named = trim($service);

        if ($named === '') {
            throw ServiceIsUnnamed::whereOneWasExpected();
        }

        return new self($named);
    }

    /** The name, for showing and for asking with — they are the same string. */
    public function named(): string
    {
        return $this->named;
    }

    /**
     * Whether this is the same service as another.
     *
     * Here rather than compared at call sites, because *the same service* is
     * one decision and a screen matching a log window against the finding that
     * sent it there must not answer it differently from the next screen.
     */
    public function isTheSameAs(self $other): bool
    {
        return $this->named === $other->named;
    }
}
