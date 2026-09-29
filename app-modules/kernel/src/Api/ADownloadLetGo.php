<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What became of a download the client was asked to let go, as the stack reported it.
 *
 * Which one, what it occupied, and whether the run was a rehearsal. A
 * rehearsal asks the client for nothing, so a rehearsed report is of room that
 * is still spent.
 */
final readonly class ADownloadLetGo
{
    private function __construct(
        private string $name,
        private int $bytes,
        private WhetherItWasRehearsed $rehearsed,
    ) {}

    /** The stack's report of one download let go, or of letting it go rehearsed. */
    public static function reported(string $name, int $bytes, WhetherItWasRehearsed $rehearsed): self
    {
        if (trim($name) === '') {
            throw RoomSaysNothing::about('name');
        }

        if ($bytes < 0) {
            throw RoomSaysNothing::negative('bytes', $bytes);
        }

        return new self($name, $bytes, $rehearsed);
    }

    /** What the client is no longer holding, or would not be. */
    public function name(): string
    {
        return $this->name;
    }

    /** What it occupied, in bytes, as the client reported it. */
    public function bytes(): int
    {
        return $this->bytes;
    }

    /** Whether it was a rehearsal, which let nothing go. */
    public function wasRehearsed(): WhetherItWasRehearsed
    {
        return $this->rehearsed;
    }
}
