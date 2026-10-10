<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_filter;
use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Answers the installed plugins' adapters gave outside their contracts, as the listing of what is installed says.
 *
 * Only that listing carries them; an answer about an install, an update or a
 * removal says nothing of them.
 *
 * @implements IteratorAggregate<int, AnAnswerOutOfContract>
 */
final readonly class TheAnswersOutOfContract implements IteratorAggregate
{
    /** @param list<AnAnswerOutOfContract> $answers */
    private function __construct(private array $answers) {}

    /** These, in the stack's order. */
    public static function these(AnAnswerOutOfContract ...$answers): self
    {
        return new self(array_values($answers));
    }

    /** The answers that plugin's adapters gave, in the stack's order. */
    public function of(APlugin $plugin): self
    {
        return new self(array_values(array_filter($this->answers, static fn(AnAnswerOutOfContract $answer): bool => $answer->isOf($plugin))));
    }

    /** @return Traversable<int, AnAnswerOutOfContract> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->answers);
    }
}
