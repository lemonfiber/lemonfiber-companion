<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One container the machine is running that this stack never declared.
 *
 * It carries a name, what it is doing, and what the machine says it is — and
 * nothing else. Each has to be named, and what it is running
 * to be stated, and forbids offering a verb against one; a row with no
 * identifier a verb could take is how the second half stays true without a
 * template having to remember it.
 *
 * `runs` is a key rather than a sentence, because the nine words a container's
 * state can be are the same nine a service's can, and they are already written
 * once in the catalogue both read from.
 */
final readonly class WhatOneOtherContainerSays
{
    /**
     * @param string $named    what the container engine calls it
     * @param string $describes what it does for the operator, in the machine's words
     * @param string $runs     the key for what it is doing
     */
    public function __construct(
        public string $named,
        public string $describes,
        public string $runs,
    ) {}
}
