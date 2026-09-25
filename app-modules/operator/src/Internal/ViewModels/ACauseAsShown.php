<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One thing that could be behind a symptom, flattened for a template.
 */
final readonly class ACauseAsShown
{
    /**
     * @param string $because what is wrong
     * @param string $tell    how to tell it from the others
     * @param string $fix     what to do about it
     */
    public function __construct(
        public string $because,
        public string $tell,
        public string $fix,
    ) {}
}
