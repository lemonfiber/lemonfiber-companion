<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One rate a download client gave for one direction, in bytes a second.
 */
final readonly class ARateGiven
{
    private function __construct(private WhichRate $which, private int $bytesASecond) {}

    /** The rate, refused below nothing. */
    public static function of(WhichRate $which, int $bytesASecond): self
    {
        if ($bytesASecond < 0) {
            throw LineSaysNothing::negative($which->value, $bytesASecond);
        }

        return new self($which, $bytesASecond);
    }

    /** Which rate this is. */
    public function which(): WhichRate
    {
        return $this->which;
    }

    /** The rate, in bytes a second. */
    public function bytesASecond(): int
    {
        return $this->bytesASecond;
    }
}
