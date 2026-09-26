<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * One project already on a machine that is not lemonfiber's, and each of its services.
 *
 * @implements IteratorAggregate<int, AServiceStanding>
 */
final readonly class AProjectStanding implements Countable, IteratorAggregate
{
    /** @param list<AServiceStanding> $services */
    private function __construct(
        private string $project,
        private array $services,
    ) {}

    /** A project by the name it was started under, with its services in the stack's order; a blank name is refused. */
    public static function named(string $project, AServiceStanding ...$services): self
    {
        if (trim($project) === '') {
            throw TheSurveySaysNothing::about('project');
        }

        return new self($project, array_values($services));
    }

    /** The name it was started under. */
    public function project(): string
    {
        return $this->project;
    }

    /** @return Traversable<int, AServiceStanding> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->services);
    }

    public function count(): int
    {
        return count($this->services);
    }
}
