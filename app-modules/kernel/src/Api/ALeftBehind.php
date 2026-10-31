<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One kind of thing the part a substitution stops holds that its replacement is not carried, in the stack's words.
 *
 * Read from the contracts the stopped part fills, never from a list kept per
 * part: the capability whose contract declares it, and the words a rehearsal
 * names it in.
 */
final readonly class ALeftBehind
{
    private function __construct(private Capability $capability, private string $said) {}

    /** What the stack said stays behind, and for which capability; blank words are refused. */
    public static function of(Capability $capability, string $said): self
    {
        if (trim($said) === '') {
            throw FillSaysNothing::about('said');
        }

        return new self($capability, trim($said));
    }

    /** The capability whose contract declares it held. */
    public function capability(): Capability
    {
        return $this->capability;
    }

    /** What stays behind, in the stack's words. */
    public function said(): string
    {
        return $this->said;
    }
}
