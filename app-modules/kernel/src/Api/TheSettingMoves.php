<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_slice;
use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every setting one change moves, in name order as the stack lists them; a change moves at least one.
 *
 * @implements IteratorAggregate<int, ASettingMove>
 */
final readonly class TheSettingMoves implements IteratorAggregate
{
    /** @param non-empty-list<ASettingMove> $moves */
    private function __construct(private array $moves, private ASettingChange $change) {}

    /** These moves; none is refused, as a change that moves nothing is not one, and so is a list that both gives values and puts settings back. */
    public static function these(ASettingMove ...$moves): self
    {
        $listed = array_values($moves);

        if ($listed === []) {
            throw SettingSaysNothing::about('settings');
        }

        $change = self::begun($listed[0]);

        foreach (array_slice($listed, 1) as $move) {
            $change = $change->andAlso(self::begun($move));
        }

        return new self($listed, $change);
    }

    /** The change they make: each value given, or each setting put back. */
    public function change(): ASettingChange
    {
        return $this->change;
    }

    /** @return Traversable<int, ASettingMove> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->moves);
    }

    private static function begun(ASettingMove $move): ASettingChange
    {
        return $move->value()->isHeld()
            ? ASettingChange::toHold($move->setting(), $move->value()->said())
            : ASettingChange::backToDefault($move->setting());
    }
}
