<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One thing an operator may do about a setup already here, as the stack offers it.
 *
 * **A mode that disturbs what is running is never offered already chosen.**
 * The stack preselects only adopting, and replacing — the one that stops
 * somebody's working stack — has to be reached for. This holds that even
 * against a stack that marked one: a preselection is withheld, never added.
 */
final readonly class AMode
{
    private function __construct(
        private string $mode,
        private string $what,
        private bool $disturbs,
        private bool $preselected,
    ) {}

    /** What the stack said of one mode; its word and what it would come to are required. */
    public static function offered(string $mode, string $what, bool $disturbs, bool $preselected): self
    {
        foreach (['mode' => $mode, 'what' => $what] as $field => $said) {
            if (trim($said) === '') {
                throw TheSurveySaysNothing::about($field);
            }
        }

        return new self($mode, $what, $disturbs, $preselected);
    }

    /** The word an operator types for it. */
    public function mode(): string
    {
        return $this->mode;
    }

    /** What choosing it would come to, in the stack's words. */
    public function what(): string
    {
        return $this->what;
    }

    /** Whether carrying it out stops or alters what is already running. */
    public function disturbs(): bool
    {
        return $this->disturbs;
    }

    /** Whether it is offered already chosen: only where the stack chose it and it disturbs nothing. */
    public function isPreselected(): bool
    {
        return $this->preselected && ! $this->disturbs;
    }
}
