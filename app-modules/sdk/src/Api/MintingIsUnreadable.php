<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Lemonfiber\Sdk\Generated\Kind;
use Modules\Kernel\Api\WhereAPhoneKeyLives;

use function sprintf;

/**
 * A `device-key`, `device-challenge` or `key-claim` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated. Refused rather than salvaged: a
 * challenge or a link read wrongly is a mint the operator cannot finish.
 */
final class MintingIsUnreadable extends InvalidArgumentException
{
    /** A field is absent, blank, or not what the contract says it is. */
    public static function missing(Kind $envelope, NamesAWireField $field): self
    {
        return new self(sprintf('The %s envelope has no readable `%s`, so it did not come from a lemonfiber this app can read.', $envelope->value, $field->value));
    }

    /** A phone key just registered lives somewhere this app has no case for. */
    public static function backing(string $said): self
    {
        return new self(sprintf(
            'The device-key envelope says the key lives in `%s`, and this app knows only %s.',
            $said,
            implode(', ', array_map(static fn(WhereAPhoneKeyLives $where): string => $where->value, WhereAPhoneKeyLives::cases())),
        ));
    }
}
