<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One line a walkthrough said, flattened for a template and unaltered. */
final readonly class AWalkthroughLineAsShown
{
    /**
     * @param string      $step   the step as the stack names it, which the template hands to the glossary
     * @param string      $said   what it was doing, as the stack said it
     * @param string      $detail what was particular about it, or empty where the stack had nothing to add
     */
    public function __construct(
        public string $step,
        public string $said,
        public string $detail,
    ) {}
}
