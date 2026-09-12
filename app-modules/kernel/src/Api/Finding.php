<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One thing a check established, and how it turned out.
 *
 * The four fields every finding on the wire carries: which check, which
 * family it belongs to, a one-line summary of what was checked, and the
 * conclusion.
 *
 * **What a warning or a failure said is not here yet, and that is deliberate.**
 * The server carries a `Problem` on those two conclusions, and this side will
 * carry it in the same shape the kernel already publishes. What decides how it
 * is read — a fold over five cases, an accessor, something else — is a
 * question a screen answers, and there is no screen. Carrying a payload
 * nothing can read would be worse than not carrying it: it would be a design
 * fixed before the thing it is for existed, and the first screen would find it
 * wrong and have to work around it.
 *
 * What is here is what the module already decides with: the ordering, which is
 * `N2`'s "findings worst-first", and the counting a screen does before it
 * knows what to draw.
 */
final readonly class Finding
{
    private function __construct(
        private Check $check,
        private Category $category,
        private string $title,
        private Conclusion $conclusion,
    ) {}

    /**
     * The one place a report's row becomes a finding.
     *
     * A named constructor is where a primitive is permitted to cross into a
     * module (D2), and it is where the title is checked: a row with a blank
     * title renders as an empty line in a list the operator is scanning for
     * the thing that is wrong.
     */
    public static function of(Check $check, Category $category, string $title, Conclusion $conclusion): self
    {
        $trimmed = trim($title);

        if ($trimmed === '') {
            throw FindingHasNoTitle::about($check);
        }

        return new self($check, $category, $trimmed, $conclusion);
    }

    public function check(): Check
    {
        return $this->check;
    }

    public function category(): Category
    {
        return $this->category;
    }

    /** The one-line summary of what was checked. */
    public function title(): string
    {
        return $this->title;
    }

    public function conclusion(): Conclusion
    {
        return $this->conclusion;
    }
}
