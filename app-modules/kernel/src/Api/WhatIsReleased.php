<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The newest version released.
 *
 * Empty where the stack said nothing: the check could not read a version.
 */
final readonly class WhatIsReleased
{
    private function __construct(private string $version) {}

    /** What the stack said was released; a blank version is refused, an empty one is not said. */
    public static function said(string $version): self
    {
        if ($version !== '' && trim($version) === '') {
            throw ItselfSaysNothing::about('offered');
        }

        return new self($version);
    }

    /** Nothing released that the stack could name. */
    public static function nothing(): self
    {
        return new self('');
    }

    /** The newest version released, or empty. */
    public function version(): string
    {
        return $this->version;
    }
}
