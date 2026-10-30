<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_all;
use function array_key_exists;
use function array_replace;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

use function trim;

/**
 * A change to one or more settings the operator asked for in one act: values of theirs, or back to their defaults.
 *
 * The stack takes the settings an act moves together and holds them to their
 * choices together, so settings that depend on each other are moved in one
 * change. One act either gives values or puts settings back, never both.
 *
 * Iterated as each setting it moves, by its name, with the value it is to
 * hold, nothing where it goes back to its default.
 *
 * @implements IteratorAggregate<string, ASettingValue>
 */
final readonly class ASettingChange implements IteratorAggregate
{
    /** @param array<string, ASettingValue> $moves each setting it moves, by its name, with what it is to hold */
    private function __construct(private ChangingASetting $how, private array $moves) {}

    /** Give the setting this value, exactly as typed; a blank setting is refused. */
    public static function toHold(string $setting, string $value): self
    {
        return new self(ChangingASetting::Set, [self::named($setting) => ASettingValue::value($value)]);
    }

    /** Put the setting back to its default; a blank setting is refused. */
    public static function backToDefault(string $setting): self
    {
        return new self(ChangingASetting::Clear, [self::named($setting) => ASettingValue::nothing()]);
    }

    /** The same act, moving the other's settings too, the other's word standing on a setting both move; refused where one gives values and the other puts settings back. */
    public function andAlso(self $other): self
    {
        if ($other->how !== $this->how) {
            throw $this->how === ChangingASetting::Set ? ASettingChangeIsOneAct::alreadyGivingValues() : ASettingChangeIsOneAct::alreadyPuttingBack();
        }

        return new self($this->how, array_replace($this->moves, $other->moves));
    }

    /** Which of the two acts it is. */
    public function how(): ChangingASetting
    {
        return $this->how;
    }

    /** Whether it is the same act on the same settings with the same values, in whatever order they were asked. */
    public function isTheSameAs(self $other): bool
    {
        $theirs = $other->moves;

        return $this->how === $other->how
            && count($this->moves) === count($theirs)
            && array_all($this->moves, static fn(ASettingValue $value, string $setting): bool => array_key_exists($setting, $theirs) && $theirs[$setting]->said() === $value->said());
    }

    /** @return Traversable<string, ASettingValue> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->moves);
    }

    private static function named(string $setting): string
    {
        if (trim($setting) === '') {
            throw SettingSaysNothing::about('setting');
        }

        return trim($setting);
    }
}
