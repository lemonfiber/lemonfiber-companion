<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

/** Which screen the navigation stack builds, once the lock has been asked. */
final readonly class ScreenToBuild
{
    private function __construct(public string $class) {}

    public static function named(string $class): self
    {
        return new self($class);
    }
}
