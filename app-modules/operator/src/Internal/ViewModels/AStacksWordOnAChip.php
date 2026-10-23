<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One word the stack offers to choose from, as the chip that offers it.
 *
 * Drawn as the stack spells it and never looked up in the catalogue: it is
 * the stack's word, and a catalogue key that happened to match it would put
 * this app's words in its place.
 */
final readonly class AStacksWordOnAChip
{
    /**
     * @param string $word   the word, as the stack offers it and as tapping it hands the screen
     * @param bool   $chosen whether it is the one chosen here now
     */
    public function __construct(
        public string $word,
        public bool $chosen,
    ) {}
}
