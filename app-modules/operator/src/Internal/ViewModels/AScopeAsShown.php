<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * How much a copy covers, flattened for a template.
 *
 * A phrase a sentence is built around rather than a sentence of its own, so
 * *copied*, *would copy* and *about to copy* all state the scope the same way.
 */
final readonly class AScopeAsShown
{
    /**
     * @param string                $said  the catalogue key for the phrase naming the scope
     * @param array<string, string> $with  what the phrase is filled with: the service or the project, and nothing for the whole stack
     * @param list<string>          $trees where each tree of an existing setup was read from, and none for the other two scopes
     */
    public function __construct(
        public string $said,
        public array $with,
        public array $trees,
    ) {}
}
