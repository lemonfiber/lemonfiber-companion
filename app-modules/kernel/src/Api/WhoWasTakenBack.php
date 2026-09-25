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
 * Invitations nobody claimed in time, taken back on the way past, by the name each was for.
 *
 * Carried on the answer they arrived with rather than dropped: an operator who
 * invited somebody last week and heard nothing learns here that the account is
 * gone. On a rehearsal these are the ones that would be taken back.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhoWasTakenBack implements Countable, IteratorAggregate
{
    /** @param list<string> $names */
    private function __construct(private array $names) {}

    /** These, in the stack's order; a blank name is refused. */
    public static function of(string ...$names): self
    {
        foreach ($names as $name) {
            if (trim($name) === '') {
                throw InvitationSaysNothing::about('withdrawn');
            }
        }

        return new self(array_values($names));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->names);
    }

    public function count(): int
    {
        return count($this->names);
    }
}
