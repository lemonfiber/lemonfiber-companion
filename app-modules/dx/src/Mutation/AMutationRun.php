<?php

declare(strict_types=1);

namespace Dx\Mutation;

/**
 * One invocation of Pest's mutation plugin.
 *
 * `group` is empty where the whole suite judges the paths, and a run the whole
 * suite judges is the only kind that takes the coverage map the tests job wrote.
 * `leftOut` names the paths inside these trees a group judges in a run of its
 * own.
 */
final readonly class AMutationRun
{
    /**
     * @param list<string> $paths
     * @param list<string> $leftOut
     */
    public function __construct(
        public int $floor,
        public array $paths,
        public string $group,
        public array $leftOut,
    ) {}

    public function isJudgedByTheSuite(): bool
    {
        return $this->group === '';
    }
}
