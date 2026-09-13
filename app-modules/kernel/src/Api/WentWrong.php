<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The three things the core says when a check did not pass (`N2-R3`).
 *
 * Together rather than as three fields on {@see WhatTheCheckSaid}, because they
 * arrive together and are absent together. The version that kept them
 * separately had to give the passing arm a blank meaning to hold the shape —
 * a value nothing reads, which no test can tell from any other value, and which
 * a reader has to work out is deliberate.
 *
 * Held here, the passing arm carries nothing at all and there is no placeholder
 * to explain.
 */
final readonly class WentWrong
{
    private function __construct(
        private Code $code,
        private string $meaning,
        private Remedies $remedies,
    ) {}

    /**
     * The meaning is refused when blank, for the reason {@see Finding} refuses a
     * blank title: a red row with no sentence is one the operator cannot act on
     * and cannot search for, and a core producing one has a fault worth seeing
     * where the payload is read rather than on somebody's screen.
     */
    public static function of(Code $code, string $meaning, Remedies $remedies): self
    {
        $said = trim($meaning);

        if ($said === '') {
            throw CheckSaidNothing::under($code);
        }

        return new self($code, $said, $remedies);
    }

    /** The code an operator can search for, and quote to somebody. */
    public function code(): Code
    {
        return $this->code;
    }

    /** What it means, in the core's words rather than the app's. */
    public function meaning(): string
    {
        return $this->meaning;
    }

    /** What the core offered to do about it. */
    public function remedies(): Remedies
    {
        return $this->remedies;
    }
}
