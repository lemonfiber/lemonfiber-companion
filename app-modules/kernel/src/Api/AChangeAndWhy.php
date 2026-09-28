<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One change a report says something further about, and what it says.
 *
 * The shape the stack gives both what putting a run back left standing and
 * what it noted beyond the changes themselves. Which of the two a row is
 * belongs to the list it is in, so the list is named and the row is not.
 * Neither half may be blank: a change left with no reason is the half-undone
 * machine nobody has been told about.
 */
final readonly class AChangeAndWhy
{
    private function __construct(private string $target, private string $because) {}

    /** What the change was against, and what is said of it. */
    public static function said(string $target, string $because): self
    {
        $against = trim($target);
        $why = trim($because);

        if ($against === '') {
            throw UndoSaysNothing::about('target');
        }

        if ($why === '') {
            throw UndoSaysNothing::about('because');
        }

        return new self($against, $why);
    }

    /** What the change was against: a service, or lemonfiber's own environment file. */
    public function target(): string
    {
        return $this->target;
    }

    /** What is said of it, in the operator's terms. */
    public function because(): string
    {
        return $this->because;
    }
}
