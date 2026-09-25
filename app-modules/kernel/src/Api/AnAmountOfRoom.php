<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * A figure in bytes that a volume's reading may or may not have.
 *
 * **Not read is not nought.** A volume the stack could not attribute to a
 * mount has no figure for what is free, and drawing that as nothing free
 * would send somebody to delete files off a drive that is only unplugged.
 */
final readonly class AnAmountOfRoom
{
    private function __construct(private ?int $bytes) {}

    /** A figure the stack read, refused below nothing. */
    public static function of(int $bytes, string $field): self
    {
        if ($bytes < 0) {
            throw RoomSaysNothing::negative($field, $bytes);
        }

        return new self($bytes);
    }

    /** No figure: the stack could not read one. */
    public static function unread(): self
    {
        return new self(null);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Both arms required, for {@see WhatWasFoundKept::either()}'s reason.
     *
     * @template TKnown of object
     * @template TUnread of object
     *
     * @param Closure(int): TKnown $known
     * @param Closure(): TUnread   $unread
     *
     * @return TKnown|TUnread
     */
    public function either(Closure $known, Closure $unread): object
    {
        return $this->bytes === null ? $unread() : $known($this->bytes);
    }
}
