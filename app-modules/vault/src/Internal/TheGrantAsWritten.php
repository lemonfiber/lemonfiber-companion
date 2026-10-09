<?php

declare(strict_types=1);

namespace Modules\Vault\Internal;

use function array_key_exists;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\DeviceIdIsUnfit;
use Modules\Kernel\Api\GrantIsUnfit;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\InstantIsBeforeTheEpoch;
use Modules\Kernel\Api\TheGrantHeld;
use Modules\Kernel\Api\TheGrantIsFor;
use Modules\Kernel\Api\ThisDevice;
use Modules\Kernel\Api\Whose;

/**
 * A grant as the secure store holds it: in a shape, with when it lapses and
 * whom it was answered for.
 *
 * A value in a shape this build does not write, one whose grant the door
 * would not accept, or one kept for another member or under another device id
 * reads as no grant: the cost is one more ask of the core.
 */
final readonly class TheGrantAsWritten
{
    /** The shape this build writes, and the only one it reads. */
    private const int SHAPE = 1;

    /** The field that holds the grant itself. */
    private const string TOKEN = 'for_the_door';

    /** The field that holds when it lapses. */
    private const string LAPSES_AT = 'lapses_at';

    /** The field that holds the member it was answered for. */
    private const string MEMBER = 'played_by';

    /** The field that holds the device id it was answered for. */
    private const string DEVICE = 'played_on';

    /** The value a grant is kept as. */
    public static function written(TheGrantIsFor $for, AGrant $grant): string
    {
        return (string) json_encode(KeptInAShape::written(self::SHAPE, [
            self::TOKEN => $grant->forTheDoor(),
            self::LAPSES_AT => $grant->lapsesAt()->epochSeconds(),
            self::MEMBER => $for->memberForTheStore(),
            self::DEVICE => $for->deviceForTheStore(),
        ]));
    }

    /** The grant a kept value holds for whoever is asking, where it is one this build wrote, the door accepts, and was kept for them. */
    public static function read(string $written, TheGrantIsFor $asking): TheGrantHeld
    {
        $found = json_decode($written, associative: true);
        $fields = KeptInAShape::isIn($found, self::SHAPE) ? self::fieldsOf($found) : null;

        if ($fields === null) {
            return TheGrantHeld::none();
        }

        [$token, $lapsesAt, $member, $device] = $fields;

        try {
            return TheGrantIsFor::of(Whose::member($member), ThisDevice::named($device))->is($asking)
                ? TheGrantHeld::held(AGrant::of($token, Instant::atEpochSeconds($lapsesAt)))
                : TheGrantHeld::none();
        } catch (GrantIsUnfit|InstantIsBeforeTheEpoch|DeviceIdIsUnfit) {
            return TheGrantHeld::none();
        }
    }

    /**
     * The four fields of a value in this build's shape, where all are there and of their kind.
     *
     * @param array<mixed> $found
     * @return array{string, int, string, string}|null the token, when it lapses, the member and the device
     */
    private static function fieldsOf(array $found): ?array
    {
        foreach ([self::TOKEN, self::LAPSES_AT, self::MEMBER, self::DEVICE] as $field) {
            if (! array_key_exists($field, $found)) {
                return null;
            }
        }

        [$token, $lapsesAt, $member, $device] = [$found[self::TOKEN], $found[self::LAPSES_AT], $found[self::MEMBER], $found[self::DEVICE]];

        return is_string($token) && is_int($lapsesAt) && is_string($member) && is_string($device) ? [$token, $lapsesAt, $member, $device] : null;
    }
}
