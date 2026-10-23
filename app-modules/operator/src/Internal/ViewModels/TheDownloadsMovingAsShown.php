<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\TheHealthSummary;

/** The downloads a summary said are moving, by name, flattened for a template. */
final readonly class TheDownloadsMovingAsShown
{
    /** @param list<string> $names each download moving, in the stack's order */
    private function __construct(public array $names) {}

    /** None named. */
    public static function none(): self
    {
        return new self([]);
    }

    /** The downloads this summary said are moving. */
    public static function in(TheHealthSummary $summary): self
    {
        $names = [];

        foreach ($summary->downloadsMoving() as $name) {
            $names[] = $name;
        }

        return new self($names);
    }
}
