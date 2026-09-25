<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One copy of the stack, by the name it was written under.
 *
 * A name rather than a path: the stack resolves it beneath the directory it
 * keeps copies in and nowhere else, so a name is the one thing a phone can
 * ask to have put back. Carried exactly as the stack listed it.
 */
final readonly class ACopy
{
    private function __construct(private string $name) {}

    /** A copy by the name the stack listed it under. */
    public static function named(string $name): self
    {
        if (trim($name) === '') {
            throw KeepingSaysNothing::about('archive');
        }

        return new self($name);
    }

    /** The name, for showing and for asking with. */
    public function name(): string
    {
        return $this->name;
    }
}
