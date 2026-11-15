<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Closure;

/** The UnifiedPush distributors the phone listed, each package with what it is called, or that it cannot be woken through one. */
final readonly class DistributorsListed
{
    /** @param array<string, string>|null $installed what each installed package is called, or null where the phone cannot be woken this way */
    private function __construct(private ?array $installed) {}

    /** @param array<string, string> $installed what each installed package is called */
    public static function installed(array $installed): self
    {
        return new self($installed);
    }

    public static function notHere(): self
    {
        return new self(null);
    }

    /**
     * @template TInstalled
     * @template TNotHere
     *
     * @param  Closure(array<string, string>): TInstalled $installed
     * @param  Closure(): TNotHere                        $notHere
     * @return TInstalled|TNotHere
     */
    public function either(Closure $installed, Closure $notHere): mixed
    {
        return $this->installed === null ? $notHere() : $installed($this->installed);
    }
}
