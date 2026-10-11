<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function hash;
use function sprintf;

/**
 * The opaque name a picture is held under on the device, and the source an image element draws it from.
 *
 * Derived from the stack, which picture and the title's id, so the same
 * picture is held once and nothing about the title can be read back out of
 * the name.
 */
final readonly class ArtworkKey
{
    /** What every source starts with. */
    public const string SCHEME = 'lemonfiber-art:';

    private function __construct(private string $said) {}

    /** The key for one picture of one title on one stack. */
    public static function of(StackId $stack, WhichPicture $which, string $id): self
    {
        return new self(sprintf('%s%s', self::SCHEME, hash('sha256', sprintf('%s|%s|%s', $stack->stored(), $which->value, $id))));
    }

    /** The key as an image element's source. */
    public function said(): string
    {
        return $this->said;
    }
}
