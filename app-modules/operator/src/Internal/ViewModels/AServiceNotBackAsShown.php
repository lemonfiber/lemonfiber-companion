<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One service a verb did not bring back, flattened to a row. */
final readonly class AServiceNotBackAsShown
{
    /**
     * @param string $name     the service, as the stack names it
     * @param string $runsSaid the catalogue key for where it stood when the stack stopped waiting
     */
    public function __construct(
        public string $name,
        public string $runsSaid,
    ) {}
}
