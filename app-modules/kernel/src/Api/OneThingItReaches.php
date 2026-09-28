<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One line of what taking lemonfiber off reaches: going, or kept with the reason.
 *
 * **Kept is not hidden.** An image another project stands on, or a path the
 * stack could not confirm, stays on the list with why it stays, apart from
 * what goes; a list that dropped it would read as a machine with less on it.
 *
 * **A credential is marked, never shown.** Whether the line holds one is all
 * that is carried, so what destroying it destroys can be said without a value.
 */
final readonly class OneThingItReaches
{
    private function __construct(
        private string $name,
        private WhatSortItIs $sort,
        private string $what,
        private bool $secret,
        private AnAmountOfRoom $size,
        private string $keptBecause,
    ) {}

    /** A line that is going; a blank name or description is refused. */
    public static function going(string $name, WhatSortItIs $sort, string $what, bool $holdsACredential, AnAmountOfRoom $size): self
    {
        return new self(self::said($name, 'name'), $sort, self::said($what, 'what'), $holdsACredential, $size, '');
    }

    /** A line the stack keeps rather than removes, and why; a blank reason is refused. */
    public static function kept(string $name, WhatSortItIs $sort, string $what, bool $holdsACredential, AnAmountOfRoom $size, string $because): self
    {
        return new self(self::said($name, 'name'), $sort, self::said($what, 'what'), $holdsACredential, $size, self::said($because, 'kept'));
    }

    /** What it is called: a container name, an image reference, or a full path. */
    public function name(): string
    {
        return $this->name;
    }

    /** Which sort of thing it is. */
    public function sort(): WhatSortItIs
    {
        return $this->sort;
    }

    /** What it is, in the operator's words. */
    public function what(): string
    {
        return $this->what;
    }

    /** Whether it holds a credential, which removing it destroys. */
    public function holdsACredential(): bool
    {
        return $this->secret;
    }

    /** What it occupies, where the stack could say. */
    public function size(): AnAmountOfRoom
    {
        return $this->size;
    }

    /** Whether the stack keeps it rather than removes it. */
    public function isKept(): bool
    {
        return $this->keptBecause !== '';
    }

    /** Why it is kept, in the stack's words, or nothing where it is going. */
    public function whyItIsKept(): string
    {
        return $this->keptBecause;
    }

    /** A word the line owes, refused blank. */
    private static function said(string $word, string $field): string
    {
        if (trim($word) === '') {
            throw UninstallSaysNothing::about($field);
        }

        return $word;
    }
}
