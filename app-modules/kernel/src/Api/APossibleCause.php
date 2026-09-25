<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One thing that could be behind a symptom: what is wrong, how to tell it is this one, and what to do.
 */
final readonly class APossibleCause
{
    private function __construct(private string $because, private string $tell, private string $fix) {}

    /** What the stack said of one cause; every word is required. */
    public static function said(string $because, string $tell, string $fix): self
    {
        foreach (['because' => $because, 'tell' => $tell, 'fix' => $fix] as $field => $said) {
            if (trim($said) === '') {
                throw AdviceSaysNothing::about($field);
            }
        }

        return new self($because, $tell, $fix);
    }

    /** What is wrong. */
    public function because(): string
    {
        return $this->because;
    }

    /** How to tell this cause from the others under the same symptom. */
    public function tell(): string
    {
        return $this->tell;
    }

    /** What to do about it. */
    public function fix(): string
    {
        return $this->fix;
    }
}
