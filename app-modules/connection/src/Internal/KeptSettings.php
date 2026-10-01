<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use Closure;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;

/** What the store answered about this module's settings: a sealed value in its shape, or none. */
final readonly class KeptSettings
{
    private function __construct(private ?SealedPayload $payload, private ?Shape $shape) {}

    public static function found(SealedPayload $payload, Shape $shape): self
    {
        return new self($payload, $shape);
    }

    /** Nothing kept, or nothing this build can read, which is the same to a reader. */
    public static function none(): self
    {
        return new self(null, null);
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
