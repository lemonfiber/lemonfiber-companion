<?php

declare(strict_types=1);

namespace Dx\Mutation;

/**
 * A tree, or a path inside one, mutated against the `holds:` group that
 * declares it rather than against the whole suite.
 */
final readonly class AHeldPath
{
    public function __construct(
        public string $path,
        public string $group,
        public int $floor,
    ) {}
}
