<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Where a support bundle would go, or went, on the stack's machine.
 *
 * Three arms rather than a nullable path. A description says where the file
 * would be written; a written bundle says where it is; and a machine that
 * would not say where lemonfiber keeps its own files describes a bundle with
 * nowhere to name, which is said as that rather than as a blank.
 */
final readonly class WhereABundleIs
{
    private function __construct(private ?string $path, private bool $written) {}

    /** Not written, and this is where it would be. */
    public static function wouldGo(string $path): self
    {
        return new self($path, written: false);
    }

    /** Written, and this is where it is. */
    public static function writtenAt(string $path): self
    {
        return new self($path, written: true);
    }

    /** Not written, and the stack did not say where it would be. */
    public static function unsaid(): self
    {
        return new self(null, written: false);
    }

    /** Whether the bundle exists on the machine. */
    public function isWritten(): bool
    {
        return $this->written;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(string): T $wouldGo
     * @param Closure(string): T $written
     * @param Closure(): T       $unsaid
     *
     * @return T
     */
    public function either(Closure $wouldGo, Closure $written, Closure $unsaid): object
    {
        return match (true) {
            $this->path === null => $unsaid(),
            $this->written => $written($this->path),
            default => $wouldGo($this->path),
        };
    }
}
