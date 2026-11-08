<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function mb_strlen;
use function str_starts_with;

/**
 * The public half of the WebPush key pair this phone made for one stack, which the stack encrypts every push to.
 *
 * The private half never leaves the phone's keystore.
 */
final readonly class AWakingKey
{
    /** How many bytes an uncompressed P-256 point is: the leading 0x04 and two coordinates of 32. */
    public const int BYTES = 65;

    /** The byte an uncompressed point begins with. */
    private const string UNCOMPRESSED = "\x04";

    private function __construct(private string $bytes) {}

    /** The point `$bytes`, refused where it is not an uncompressed P-256 point. */
    public static function of(string $bytes): self
    {
        $length = mb_strlen($bytes, '8bit');

        if ($length !== self::BYTES || ! str_starts_with($bytes, self::UNCOMPRESSED)) {
            throw AWakeIsUnfit::aKey($length);
        }

        return new self($bytes);
    }

    /** The point, as bytes. */
    public function bytes(): string
    {
        return $this->bytes;
    }
}
