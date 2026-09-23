<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One of the core's capabilities, named as the core names it.
 *
 * The noun a wiring is about — *a download client*, *an indexer* — as distinct
 * from the service that ends up filling it. The two are separate words on the
 * wire and separate types here, because the whole of `N5` is about the cases
 * where they do not line up one to one: two services claiming the same
 * capability, or a capability nothing claims at all.
 *
 * **Not {@see Capabilities}, which is a different noun wearing the same word.**
 * That type is what one *stack* says it can do — whether this build supports
 * backups — and is read to decide which buttons exist. This is what a *service*
 * fills inside a stack. Nothing converts between them and nothing should: a
 * screen that confused the two would offer an operator a choice between two
 * download clients on the grounds that the stack supports backups.
 *
 * A blank name is refused rather than carried, which is {@see ServiceId}'s
 * argument: a name nobody can read cannot be matched against the stack, and
 * here it is also the subject of the only question this surface asks.
 */
final readonly class Capability
{
    private function __construct(private string $named) {}

    /** One of the capabilities, named as the core names it. */
    public static function called(string $capability): self
    {
        $named = trim($capability);

        if ($named === '') {
            throw CapabilityIsUnnamed::whereOneWasExpected();
        }

        return new self($named);
    }

    /** The name, for showing and for asking with — they are the same string. */
    public function named(): string
    {
        return $this->named;
    }

    /**
     * Whether this is the same capability as another.
     *
     * By the name, because the name is the whole of it. {@see ServiceId} makes
     * the same comparison for the same reason.
     */
    public function isTheSameAs(self $other): bool
    {
        return $this->named === $other->named;
    }
}
