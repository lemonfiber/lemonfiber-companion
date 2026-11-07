<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** One change an install makes to the machine: the full path, and what it puts there. */
final readonly class APluginChange
{
    private function __construct(private string $path, private WhatAChangePuts $puts, private ThePlaceholders $placeholders) {}

    /** The change; a blank path is refused. */
    public static function at(string $path, WhatAChangePuts $puts): self
    {
        if (trim($path) === '') {
            throw PluginSaysNothing::about('path');
        }

        return new self($path, $puts, ThePlaceholders::these());
    }

    /** The same change, whose starting file names secrets by these placeholders. */
    public function naming(ThePlaceholders $placeholders): self
    {
        return new self($this->path, $this->puts, $placeholders);
    }

    /** Where, in full. */
    public function path(): string
    {
        return $this->path;
    }

    /** What it puts there. */
    public function puts(): WhatAChangePuts
    {
        return $this->puts;
    }

    /** Each placeholder its starting file names a secret by, none for anything else. */
    public function placeholders(): ThePlaceholders
    {
        return $this->placeholders;
    }
}
