<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * The names a certificate covers, as many as the stack wrote out, and how many more it left out.
 *
 * @implements IteratorAggregate<int, ACertificateName>
 */
final readonly class TheNamesItCovers implements IteratorAggregate
{
    /** @param list<ACertificateName> $names */
    private function __construct(private array $names, private int $more) {}

    /** These names, and that many more left out. */
    public static function these(int $more, ACertificateName ...$names): self
    {
        if ($more < 0) {
            throw CertificateSaysTooMuch::belowNone('names_more', $more);
        }

        return new self(array_values($names), $more);
    }

    /** @return Traversable<int, ACertificateName> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->names);
    }


    /** How many names the stack left out. */
    public function more(): int
    {
        return $this->more;
    }
}
