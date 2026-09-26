<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * When a support bundle was taken, and from which versions.
 *
 * Each as the stack wrote it, so a bundle shared again a week later reads as a
 * week old and from the release it came from.
 */
final readonly class WhenABundleWasTaken
{
    private function __construct(
        private string $at,
        private string $lemonfiber,
        private string $stack,
    ) {}

    /** Taken at that moment, from those versions. */
    public static function at(string $at, string $lemonfiber, string $stack): self
    {
        return new self($at, $lemonfiber, $stack);
    }

    /** The moment it was taken, as the stack wrote it. */
    public function moment(): string
    {
        return $this->at;
    }

    /** The version of lemonfiber that took it. */
    public function lemonfiber(): string
    {
        return $this->lemonfiber;
    }

    /** The version of the stack it was taken from. */
    public function stack(): string
    {
        return $this->stack;
    }
}
