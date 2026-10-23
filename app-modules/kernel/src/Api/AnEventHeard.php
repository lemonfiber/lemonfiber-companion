<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The identifier of one event a stack's stream carried, as the stack wrote it.
 *
 * Opaque: a stream opened later names it to ask for what came after, and
 * nothing here reads anything into it.
 */
final readonly class AnEventHeard
{
    private function __construct(private string $event) {}

    public static function named(string $event): self
    {
        if ($event === '') {
            throw EventHasNoName::onTheStream();
        }

        return new self($event);
    }

    /** The identifier, for naming it to the stack. */
    public function shown(): string
    {
        return $this->event;
    }
}
