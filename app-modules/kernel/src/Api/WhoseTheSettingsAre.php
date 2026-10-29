<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Whose a set of settings and secrets are, each owner once.
 *
 * @implements IteratorAggregate<int, WhoseASettingIs>
 */
final readonly class WhoseTheSettingsAre implements IteratorAggregate
{
    /** @param list<WhoseASettingIs> $owners */
    private function __construct(private array $owners) {}

    /** These owners, in the order given. */
    public static function these(WhoseASettingIs ...$owners): self
    {
        return new self(array_values($owners));
    }

    /** @return Traversable<int, WhoseASettingIs> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->owners);
    }
}
