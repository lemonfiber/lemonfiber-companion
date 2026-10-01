<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;

/**
 * Where each stack's last stream left off, by the stack's stored identifier.
 *
 * A stream let go of hands the identifier of the last event it carried to the
 * one opened in its place, which asks the stack for what came after it. A
 * stream whose events carried no identifier leaves nothing to resume after,
 * and what was kept for that stack before stays.
 */
final readonly class WhereTheStreamsLeftOff
{
    /** @param array<string, string> $after the last event each stack's stream carried */
    private function __construct(private array $after) {}

    /** No stream let go of yet, so every stream opens from the start. */
    public static function nowhere(): self
    {
        return new self([]);
    }

    /** The event this stack's next stream resumes after, or none where it opens from the start. */
    public function forTheStack(string $which): ?string
    {
        if (! array_key_exists($which, $this->after)) {
            return null;
        }

        return $this->after[$which];
    }

    /** These, with where this stack's stream left off as it is let go of. */
    public function keeping(string $which, ?AStreamHeldOpen $held): self
    {
        $leftOffAt = $held?->leftOffAt();

        return $leftOffAt === null ? $this : new self([...$this->after, $which => $leftOffAt]);
    }
}
