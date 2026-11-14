<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What a stack calls one thing somebody in the house asked for.
 *
 * The reference the household list names it by: the request service's number
 * (`42`), or `music-3` or `books-1` for an ask the core keeps itself. It is
 * carried as the stack wrote it and never taken apart, because what it is made
 * of is the stack's to decide. A value object over it for {@see ServiceId}'s
 * reason: a string travelling through three signatures is a string any of them
 * could have swapped for another.
 *
 * **A blank reference, or one with space around it, is refused here rather
 * than at each caller**, since the decision is one where acting on the wrong
 * subject is the whole harm.
 */
final readonly class RequestId
{
    private function __construct(private string $reference) {}

    /** One request, by the reference its stack gives it. */
    public static function named(string $reference): self
    {
        if ($reference === '' || trim($reference) !== $reference) {
            throw RequestIsUnnamed::as($reference);
        }

        return new self($reference);
    }

    /** The reference, for asking with, which is the only thing it is for. */
    public function reference(): string
    {
        return $this->reference;
    }

    /** Whether this is the same request as that one. */
    public function is(self $other): bool
    {
        return $this->reference === $other->reference;
    }
}
