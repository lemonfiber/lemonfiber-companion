<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;
use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * What adopting the setup already here came to, or would come to.
 *
 * Every service whose database a newer version would open, the paths its data
 * lives in, and — once it went through — where the copy of those paths was
 * written. A project of `''` is the stack naming none.
 *
 * **Whether it wants a copy first is asked of it, not worked out on a
 * screen.** The stack takes over a database a newer version will upgrade, and
 * that cannot be undone; {@see self::wantsACopyFirst()} is what a screen says
 * before the yes.
 *
 * @implements IteratorAggregate<int, AServiceAdopted>
 */
final readonly class TheAdoption implements Countable, IteratorAggregate
{
    /** @param list<AServiceAdopted> $upgrades */
    private function __construct(
        private string $project,
        private WhatWasNamed $backUp,
        private string $backedUp,
        private array $upgrades,
    ) {}

    /**
     * What the stack said; `$backedUp` is `''` where no copy was written.
     *
     * The upgrades are reindexed for a named spread's keys.
     */
    public static function of(string $project, WhatWasNamed $backUp, string $backedUp, AServiceAdopted ...$upgrades): self
    {
        return new self($project, $backUp, $backedUp, array_values($upgrades));
    }

    /** The project lemonfiber would manage, or `''` where the stack named none. */
    public function project(): string
    {
        return $this->project;
    }

    /** The host paths those services keep their data in, which is what a copy is taken of. */
    public function backUp(): WhatWasNamed
    {
        return $this->backUp;
    }

    /** Where the copy of those paths was written, or `''` where none was. */
    public function backedUp(): string
    {
        return $this->backedUp;
    }

    /** Whether any service's database must be copied before lemonfiber opens it. */
    public function wantsACopyFirst(): bool
    {
        return array_any($this->upgrades, static fn(AServiceAdopted $upgrade): bool => $upgrade->what()->wantsACopyFirst());
    }

    /** @return Traversable<int, AServiceAdopted> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->upgrades);
    }

    public function count(): int
    {
        return count($this->upgrades);
    }
}
