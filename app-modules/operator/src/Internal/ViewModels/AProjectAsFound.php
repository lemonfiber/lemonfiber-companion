<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One project already on a machine, and each of its services, flattened for a template. */
final readonly class AProjectAsFound
{
    /**
     * @param string                $project  the name it was started under
     * @param list<AServiceAsFound> $services every service in it, in the stack's order
     */
    public function __construct(
        public string $project,
        public array $services,
    ) {}
}
