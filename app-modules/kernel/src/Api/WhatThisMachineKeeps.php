<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Everything the stack keeps on this machine, and what sits beside it.
 *
 * Three lists that answer three questions: where it all lives, what each thing
 * is and why it is kept, and what is here that is not the stack's at all. They
 * are held apart rather than merged, because the third is the one somebody
 * looks for — their library — and finding it among the first two would read
 * as the stack claiming it.
 */
final readonly class WhatThisMachineKeeps
{
    private function __construct(
        private TheRoots $roots,
        private WhatIsKept $kept,
        private WhatIsBeside $beside,
    ) {}

    public static function of(TheRoots $roots, WhatIsKept $kept, WhatIsBeside $beside): self
    {
        return new self($roots, $kept, $beside);
    }

    /** The directories everything kept sits under. */
    public function roots(): TheRoots
    {
        return $this->roots;
    }

    /** Each thing kept. */
    public function kept(): WhatIsKept
    {
        return $this->kept;
    }

    /** What is here and is not the stack's to keep or remove. */
    public function beside(): WhatIsBeside
    {
        return $this->beside;
    }
}
