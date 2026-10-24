<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One step of a recipe that ran, and what it came to, flattened for a template. */
final readonly class AStepThatRanAsShown
{
    /**
     * @param string $step     the step's id
     * @param string $to       where it calls, by the manifest's name
     * @param string $cameSaid the catalogue key for what it came to
     * @param string $why      why, in the stack's words, or empty
     */
    public function __construct(
        public string $step,
        public string $to,
        public string $cameSaid,
        public string $why,
    ) {}
}
