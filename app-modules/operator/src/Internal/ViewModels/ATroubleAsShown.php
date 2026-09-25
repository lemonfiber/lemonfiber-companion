<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One symptom and what is likely behind it, flattened for a template.
 */
final readonly class ATroubleAsShown
{
    /**
     * @param string              $symptom what somebody says is happening
     * @param list<ACauseAsShown> $causes  what is likely behind it, most likely first
     */
    public function __construct(
        public string $symptom,
        public array $causes,
    ) {}
}
