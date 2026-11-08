<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/** Something waking this phone through a distributor is made of, refused where it is built. */
final class AWakeIsUnfit extends InvalidArgumentException
{
    public static function aPackage(): self
    {
        return new self('A distributor is named by the package Android knows it by, and this one is blank.');
    }

    public static function anEndpoint(): self
    {
        return new self('An endpoint is the address a distributor gave, and this one is blank.');
    }

    public static function aKey(int $bytes): self
    {
        return new self(sprintf('A WebPush key is an uncompressed P-256 point of %d bytes beginning 0x04, and this one is %d bytes or begins otherwise.', AWakingKey::BYTES, $bytes));
    }

    public static function aSecret(int $bytes): self
    {
        return new self(sprintf('A WebPush secret is %d bytes, and this one is %d.', AWakingSecret::length(), $bytes));
    }

    public static function aName(): self
    {
        return new self('A stack names an endpoint with lower-case letters, digits, dots, dashes and underscores, beginning with a letter or a digit, at most 64 of them, and this name is not one.');
    }
}
