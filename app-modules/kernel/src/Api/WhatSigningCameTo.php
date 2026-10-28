<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** The signature the phone's key made, or why it made none. */
final readonly class WhatSigningCameTo
{
    /** @param string|WhyTheKeySignedNothing $cameTo the signature, or why there is none */
    private function __construct(private string|WhyTheKeySignedNothing $cameTo) {}

    /**
     * Signed.
     *
     * @param string $signature ECDSA over SHA-256, in DER
     */
    public static function signed(string $signature): self
    {
        return new self($signature);
    }

    /** Nothing was signed, for this reason. */
    public static function not(WhyTheKeySignedNothing $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TSigned of object
     * @template TNot of object
     *
     * @param Closure(string): TSigned                 $signed
     * @param Closure(WhyTheKeySignedNothing): TNot $not
     *
     * @return TSigned|TNot
     */
    public function either(Closure $signed, Closure $not): object
    {
        return $this->cameTo instanceof WhyTheKeySignedNothing ? $not($this->cameTo) : $signed($this->cameTo);
    }
}
