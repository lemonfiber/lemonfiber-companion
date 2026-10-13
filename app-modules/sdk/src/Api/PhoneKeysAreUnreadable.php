<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\WhereAPhoneKeyLives;

use function sprintf;

/**
 * The `device-keys` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated. Refused rather
 * than salvaged: a phone key dropped for being unreadable is one the screen says
 * the stack does not list, and nobody revokes a key they cannot see.
 */
final class PhoneKeysAreUnreadable extends InvalidArgumentException
{
    /** A field of the envelope itself is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf('The device-keys envelope has no readable `%s`, so it did not come from a lemonfiber this app can read.', $field->value));
    }

    /** One phone key's field is absent, blank, or not what the contract says it is. */
    public static function entry(NamesAWireField $field, int $position): self
    {
        return new self(sprintf('Phone key %d in the device-keys envelope has no readable `%s`.', $position, $field->value));
    }

    /** A phone key lives somewhere this app has no case for. */
    public static function backing(string $said, int $position): self
    {
        return new self(sprintf(
            'Phone key %d in the device-keys envelope lives in `%s`, and this app knows only %s.',
            $position,
            $said,
            implode(', ', array_map(static fn(WhereAPhoneKeyLives $where): string => $where->value, WhereAPhoneKeyLives::cases())),
        ));
    }
}
