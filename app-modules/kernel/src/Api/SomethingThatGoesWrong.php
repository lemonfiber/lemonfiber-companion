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
 * Something somebody reports, in their words, and what is likely behind it, most likely first.
 *
 * Keyed by the symptom, because the person asking has the symptom and cannot
 * yet say which cause it is.
 *
 * @implements IteratorAggregate<int, APossibleCause>
 */
final readonly class SomethingThatGoesWrong implements Countable, IteratorAggregate
{
    /** @param list<APossibleCause> $causes */
    private function __construct(private string $symptom, private array $causes) {}

    /** One symptom and its causes, in the stack's order; a blank symptom is refused. */
    public static function said(string $symptom, APossibleCause ...$causes): self
    {
        if (trim($symptom) === '') {
            throw AdviceSaysNothing::about('symptom');
        }

        return new self($symptom, array_values($causes));
    }

    /** What somebody says is happening. */
    public function symptom(): string
    {
        return $this->symptom;
    }

    /** @return Traversable<int, APossibleCause> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->causes);
    }

    public function count(): int
    {
        return count($this->causes);
    }
}
