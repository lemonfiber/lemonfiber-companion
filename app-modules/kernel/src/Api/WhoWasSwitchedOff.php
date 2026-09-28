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
 * Resets that lapsed, switched off on the way past, by the name each account has.
 *
 * The other half of what the stack withdrew on the way past, beside
 * {@see WhoWasTakenBack}. An offer nobody ever took up is removed. A reset for
 * somebody who has been in before is switched off and kept, with everything
 * they watched, and offering it again or reissuing it switches it back on. So
 * the two are told apart: one account is gone and the other is waiting. On a
 * rehearsal these are the ones that would be switched off.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhoWasSwitchedOff implements Countable, IteratorAggregate
{
    /** @param list<string> $names */
    private function __construct(private array $names) {}

    /** These, in the stack's order; a blank name is refused. */
    public static function of(string ...$names): self
    {
        foreach ($names as $name) {
            if (trim($name) === '') {
                throw InvitationSaysNothing::about('suspended');
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
