<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The newest version released, and what its release notes say it changed.
 *
 * Both are empty where the stack said nothing: the check could not read a
 * version, or the release carried no notes.
 */
final readonly class WhatIsReleased
{
    private function __construct(private string $version, private string $changed) {}

    /** What the stack said was released; a blank sentence is refused, an empty one is not said. */
    public static function said(string $version, string $changed): self
    {
        foreach (['offered' => $version, 'changed' => $changed] as $field => $said) {
            if ($said !== '' && trim($said) === '') {
                throw ItselfSaysNothing::about($field);
            }
        }

        return new self($version, $changed);
    }

    /** Nothing released that the stack could name. */
    public static function nothing(): self
    {
        return new self('', '');
    }

    /** The newest version released, or empty. */
    public function version(): string
    {
        return $this->version;
    }

    /** What that version says it changed, as its release page words it, or empty. */
    public function changed(): string
    {
        return $this->changed;
    }
}
