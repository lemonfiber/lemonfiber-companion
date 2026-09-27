<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What standing lemonfiber beside an existing setup came to, or would come to.
 *
 * Where each service listens instead, and the Compose file that says so once
 * it is written. A `$written` of `''` is no file written.
 */
final readonly class TheStandingBeside
{
    private function __construct(private ThePortsMoved $ports, private string $written) {}

    /** What the stack said, as it said it. */
    public static function of(ThePortsMoved $ports, string $written): self
    {
        return new self($ports, $written);
    }

    /** Where each service would listen instead, lowest original port first. */
    public function ports(): ThePortsMoved
    {
        return $this->ports;
    }

    /** Where the Compose file saying so was written, or `''` where none was. */
    public function written(): string
    {
        return $this->written;
    }
}
