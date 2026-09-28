<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A stack's refusal in its own words, flattened for a template. */
final readonly class ARefusalAsShown
{
    /**
     * @param string $said    the stack's one sentence
     * @param string $meaning what the sentence means, blank where the stack said no more
     * @param string $named   what the refusal named, blank where it named nothing
     */
    public function __construct(
        public string $said,
        public string $meaning,
        public string $named,
    ) {}
}
