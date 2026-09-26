<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Where a copy's data was, and where putting it back puts it instead.
 *
 * A copy taken against one data root and put back onto a machine with another
 * lands its data somewhere it did not come from. Both halves are carried as
 * the stack wrote them, because an operator who is not told where it went
 * believes the restore failed.
 */
final readonly class ARelocation
{
    private function __construct(
        private string $was,
        private string $now,
    ) {}

    /** The data root the copy was taken against, and the one it goes to now. */
    public static function from(string $was, string $now): self
    {
        if (trim($was) === '') {
            throw KeepingSaysNothing::about('was');
        }

        if (trim($now) === '') {
            throw KeepingSaysNothing::about('now');
        }

        return new self($was, $now);
    }

    /** Where the data was when the copy was taken. */
    public function was(): string
    {
        return $this->was;
    }

    /** Where it goes on this machine. */
    public function now(): string
    {
        return $this->now;
    }
}
