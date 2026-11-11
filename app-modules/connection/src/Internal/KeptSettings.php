<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use Closure;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;

/** What the store answered about this module's settings: a sealed value in its shape, none, or a row this build cannot read. */
final readonly class KeptSettings
{
    private function __construct(private ?SealedPayload $payload, private ?Shape $shape, private bool $rowIsThere) {}

    public static function found(SealedPayload $payload, Shape $shape): self
    {
        return new self($payload, $shape, rowIsThere: true);
    }

    /** Nothing kept. */
    public static function none(): self
    {
        return new self(null, null, rowIsThere: false);
    }

    /** A row is kept that this build cannot read, which a reader takes as none and a removal as something to forget. */
    public static function thatThisBuildCannotRead(): self
    {
        return new self(null, null, rowIsThere: true);
    }

    /** Whether a row is kept, whether or not this build can read it. */
    public function holdsSomething(): bool
    {
        return $this->rowIsThere;
    }

    /**
     * @template TFound of object
     * @template TNone of object
     *
     * @param Closure(SealedPayload, Shape): TFound $found
     * @param Closure(): TNone                     $none
     *
     * @return TFound|TNone
     */
    public function either(Closure $found, Closure $none): object
    {
        return $this->payload instanceof SealedPayload && $this->shape instanceof Shape
            ? $found($this->payload, $this->shape)
            : $none();
    }
}
