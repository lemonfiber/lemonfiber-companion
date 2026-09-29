<?php

declare(strict_types=1);

namespace Dx\Mutation;

/**
 * A tree `phpunit.xml` measures, with what the manifest nearest it declares.
 *
 * `floor` is null where that manifest declares no mutation floor, which the gate
 * refuses by name rather than defaulting: a tree that inherits a floor is
 * exempt from the decision rather than held to it.
 */
final readonly class ATree
{
    public function __construct(
        public string $path,
        public string $manifest,
        public ?int $floor,
        public string $whyZero,
    ) {}
}
