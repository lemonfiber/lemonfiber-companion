<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * Every file one install wrote, or one removal took back, in the stack's order.
 *
 * A typed collection rather than an array (`D1`). A blank file is refused here
 * rather than drawn: a line saying something was written without saying what
 * is a line an operator cannot check.
 *
 * **Empty is an answer.** Removing what was never hosted takes nothing back,
 * and that is said on a screen rather than read as a failure.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class TheFilesTouched implements Countable, IteratorAggregate
{
    /** @param list<string> $files */
    private function __construct(private array $files) {}

    /**
     * These files, less the space around each, in the order given.
     *
     * Collected into a fresh list, so files that arrived keyed by name are
     * still read by position.
     */
    public static function these(string ...$files): self
    {
        $named = [];

        foreach ($files as $file) {
            $shown = trim($file);

            if ($shown === '') {
                throw HandoverSaysNothing::where('which file it touched');
            }

            $named[] = $shown;
        }

        return new self($named);
    }

    public function count(): int
    {
        return count($this->files);
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->files);
    }
}
