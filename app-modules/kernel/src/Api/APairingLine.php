<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The one line another phone pairs with, exactly as the stack wrote it.
 *
 * **Never built here.** The stack writes the line from its pairing material, and
 * this holds that text and nothing derived from it: a line this app put
 * together would be material nobody's machine issued. It is what a code of it
 * carries, and what a person types instead where a camera is no use.
 *
 * **It carries no credential**, and that is the stack's promise rather than this
 * type's. What it does carry is where a machine is and which certificate it
 * presents, which is why it never leaves this process: `serialize()` is refused
 * and a dump says what it is and not what it says.
 */
final readonly class APairingLine
{
    private function __construct(private string $line) {}

    /**
     * What a dump shows, which is that it is a pairing line and nothing it says.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['line' => '(a pairing line, hidden)'];
    }

    /**
     * What `serialize()` writes, which is nothing: the line is held on the screen
     * that asked for it and nowhere else.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::aPairingLine();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::aPairingLine();
    }

    /** The line the stack wrote; a blank one is refused, since a code of nothing pairs nothing. */
    public static function asWritten(string $line): self
    {
        if (trim($line) === '') {
            throw PairingIsNotReadable::becauseTheLineIsBlank();
        }

        return new self($line);
    }

    /** The line, exactly as the stack wrote it, which is what a code of it carries. */
    public function carried(): string
    {
        return $this->line;
    }
}
