<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of opening a sealed payload: the value, or nothing that can be read.
 *
 * **One kind of unreadable.** A payload that was tampered with, one sealed
 * under another key, one sealed under a key that has since been made afresh and
 * a string that was never a payload are the same answer to whoever asked: what
 * was kept is gone, and it is read again from the stack. Telling them apart
 * would hand an owner distinctions it has no different thing to do about.
 */
final readonly class Unsealing
{
    private function __construct(private ?Unsealed $value) {}

    /** The payload opened, and this is what was sealed. */
    public static function opened(Unsealed $value): self
    {
        return new self($value);
    }

    /** The payload does not open under the key this phone holds. */
    public static function unreadable(): self
    {
        return new self(null);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TOpened of object
     * @template TUnreadable of object
     *
     * @param Closure(Unsealed): TOpened $opened
     * @param Closure(): TUnreadable     $unreadable
     *
     * @return TOpened|TUnreadable
     */
    public function either(Closure $opened, Closure $unreadable): object
    {
        return $this->value instanceof Unsealed ? $opened($this->value) : $unreadable();
    }
}
