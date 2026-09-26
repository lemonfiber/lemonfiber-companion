<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One file inside a support bundle: the name it carries, and what it holds.
 *
 * The body is what the stack answered with, already redacted, and it is
 * carried as it arrived. Nothing here shortens, annotates or adds to it: the
 * operator reads exactly what whoever they hand the bundle to will read.
 */
final readonly class APieceOfABundle
{
    private function __construct(private string $name, private string $body) {}

    /** The file called this, holding this. */
    public static function of(string $name, string $body): self
    {
        return new self($name, $body);
    }

    /** What the file is called inside the bundle. */
    public function name(): string
    {
        return $this->name;
    }

    /** What it holds, as the stack redacted it. */
    public function body(): string
    {
        return $this->body;
    }
}
