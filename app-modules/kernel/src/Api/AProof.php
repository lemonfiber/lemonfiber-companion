<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** One proof that has to hold before a plugin is installed: what it establishes, what it asks, and what asking it came to. */
final readonly class AProof
{
    private function __construct(
        private string $proof,
        private string $establishes,
        private string $asks,
        private string $why,
        private WhatAProofCameTo $cameTo,
    ) {}

    /** The proof; a blank id, claim or question is refused. */
    public static function of(string $proof, string $establishes, string $asks, string $why, WhatAProofCameTo $cameTo): self
    {
        foreach (['proof' => $proof, 'establishes' => $establishes, 'asks' => $asks] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($proof, $establishes, $asks, trim($why), $cameTo);
    }

    /** Its id. */
    public function proof(): string
    {
        return $this->proof;
    }

    /** What it establishes, in one line. */
    public function establishes(): string
    {
        return $this->establishes;
    }

    /** What it asks, as the method and path it is asked at. */
    public function asks(): string
    {
        return $this->asks;
    }

    /** Why it is worth asserting, or empty. */
    public function why(): string
    {
        return $this->why;
    }

    /** What asking it came to. */
    public function cameTo(): WhatAProofCameTo
    {
        return $this->cameTo;
    }
}
