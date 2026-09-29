<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One service as the catalogue declares it, flattened for a template.
 *
 * Every field is always set, because the value it comes from refuses to be
 * built without it — what the house goes without included.
 */
final readonly class AServiceAsCatalogued
{
    /**
     * @param string $name        what it is called in front of an operator
     * @param string $describes   what it does for the house
     * @param string $withoutIt   what the house goes without while it is down
     * @param string $mattersSaid the key for how much that matters
     */
    public function __construct(
        public string $name,
        public string $describes,
        public string $withoutIt,
        public string $mattersSaid,
    ) {}
}
