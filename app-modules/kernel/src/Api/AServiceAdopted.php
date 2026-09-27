<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One service adopting would open with a newer version: the two versions, which is the later, and what the survey said of it.
 *
 * What adopting it means for its data, whether it wants a copy first and
 * whether it is refused are {@see WhatAdoptingOneWouldDo}, the same shape the
 * survey reads; the versions are what adopting adds to it.
 */
final readonly class AServiceAdopted
{
    private function __construct(
        private WhatAdoptingOneWouldDo $what,
        private string $existing,
        private string $ours,
        private string $verdict,
    ) {}

    /** What the stack said of one service; the two versions and the verdict are required. */
    public static function said(WhatAdoptingOneWouldDo $what, string $existing, string $ours, string $verdict): self
    {
        foreach (['existing' => $existing, 'ours' => $ours, 'verdict' => $verdict] as $field => $said) {
            if (trim($said) === '') {
                throw TheMoveSaysNothing::about($field);
            }
        }

        return new self($what, $existing, $ours, $verdict);
    }

    /** The service, what adopting it means for its data, and whether it wants a copy first or is refused. */
    public function what(): WhatAdoptingOneWouldDo
    {
        return $this->what;
    }

    /** The version standing here now. */
    public function existing(): string
    {
        return $this->existing;
    }

    /** The version lemonfiber pins. */
    public function ours(): string
    {
        return $this->ours;
    }

    /** Which of the two is the later, in the stack's one word. */
    public function verdict(): string
    {
        return $this->verdict;
    }
}
