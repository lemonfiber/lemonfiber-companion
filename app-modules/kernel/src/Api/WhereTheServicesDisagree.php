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
 * Where two services' views of a traced item contradict, each in the stack's plain words.
 *
 * Not where the item got to: a media server holding what no service is
 * watching for, and the like, surfaced rather than reconciled.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhereTheServicesDisagree implements Countable, IteratorAggregate
{
    /** @param list<string> $findings */
    private function __construct(private array $findings) {}

    /** In the stack's order; a blank one is refused. */
    public static function of(string ...$findings): self
    {
        foreach ($findings as $finding) {
            if (trim($finding) === '') {
                throw TheTraceSaysNothing::about('findings');
            }
        }

        return new self(array_values($findings));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->findings);
    }

    public function count(): int
    {
        return count($this->findings);
    }
}
