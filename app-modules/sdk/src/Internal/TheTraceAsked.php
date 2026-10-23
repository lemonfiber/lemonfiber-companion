<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Modules\Kernel\Api\WhatToFollow;
use Modules\Sdk\Api\Fields\TraceField;
use Modules\Sdk\Api\WireField;

/**
 * What a trace or a search after it is asked about: the term, and the season where it is narrowed to one.
 *
 * One place for both, so a search after a trace asks about the season the
 * trace was read with.
 */
final readonly class TheTraceAsked
{
    /** @param int<0, max>|null $season */
    private function __construct(public string $term, public ?int $season) {}

    public static function for(WhatToFollow $following): self
    {
        return $following->either(
            /** @param int<0, max> $season */
            narrowed: static fn(int $season): self => new self($following->term(), $season),
            whole: static fn(): self => new self($following->term(), null),
        );
    }

    /**
     * The trace read's query.
     *
     * @return array<string, int|string>
     */
    public function query(): array
    {
        return $this->season === null
            ? [TraceField::Term->value => $this->term]
            : [TraceField::Term->value => $this->term, WireField::Season->value => $this->season];
    }
}
