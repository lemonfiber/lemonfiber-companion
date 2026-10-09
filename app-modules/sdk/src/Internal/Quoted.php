<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Modules\Kernel\Api\AnOffer;

/**
 * An offer as a yes carries it on the wire: its name, or nothing where the stack named none.
 */
final readonly class Quoted
{
    private function __construct(private ?string $word) {}

    public static function offer(AnOffer $offer): ?string
    {
        return $offer->either(
            named: static fn(string $named): self => new self($named),
            none: static fn(): self => new self(null),
        )->word;
    }
}
