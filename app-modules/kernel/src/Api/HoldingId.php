<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What the core calls one thing a member may watch.
 *
 * A value object over the wire's string, for {@see ServiceId}'s reason: the set
 * is whatever a household happens to hold, so an enum would be a closed list of
 * what this release knew about.
 *
 * **It is an identifier and nothing else.** It is not an address and cannot be
 * turned into one here. What it takes to actually play a holding is the core's
 * to hand over — an app composing a location out of this and a server's name
 * would be holding a second copy of how the library works, and would be wrong
 * the first time the route home is not the route it assumed.
 */
final readonly class HoldingId
{
    private function __construct(private string $named) {}

    /** One holding, named as the core names it. */
    public static function called(string $holding): self
    {
        $named = trim($holding);

        if ($named === '') {
            throw HoldingIsUnnamed::whereOneWasExpected();
        }

        return new self($named);
    }

    /** The identifier, exactly as the core spelled it. */
    public function named(): string
    {
        return $this->named;
    }
}
