<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;

/**
 * The phone's settings as the store holds them, read and written whole.
 *
 * One sealed row for every setting, so changing one is reading the row,
 * changing it and writing it back: a setting kept on its own would be a row a
 * store could name.
 */
final readonly class TheSettingsHeld
{
    public function __construct(private Sealed $seal, private SettingsKept $kept, private Clock $clock) {}

    /** The settings kept, each at its standard where it was never chosen or cannot be read. */
    public function current(): ThePhonesSettings
    {
        return $this->kept->kept()->either(
            found: fn(SealedPayload $payload, Shape $shape): ThePhonesSettings => $this->seal->open($payload)->either(
                opened: static fn(Unsealed $value): ThePhonesSettings => TheSettingsAsKept::read($shape, $value),
                unreadable: static fn(): ThePhonesSettings => ThePhonesSettings::standard(),
            ),
            none: static fn(): ThePhonesSettings => ThePhonesSettings::standard(),
        );
    }

    /** Keep these settings in place of the ones before, sealed; a phone that can seal nothing keeps nothing. */
    public function keep(ThePhonesSettings $settings): Noted
    {
        return $this->seal->seal(TheSettingsAsKept::written($settings))->either(
            sealed: fn(SealedPayload $payload): Noted => $this->kept->keep($payload, Shape::current(), $this->clock->now()),
            refused: static fn(): Noted => Noted::notKept(),
        );
    }
}
