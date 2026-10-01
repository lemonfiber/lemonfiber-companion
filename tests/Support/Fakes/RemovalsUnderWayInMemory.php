<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\RemovalsUnderWay;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StacksBeingRemoved;

/**
 * The stacks being removed, in memory, held by `RemovalsUnderWayContractTest`
 * to what the platform store promises.
 *
 * `refusing()` is the store that cannot be written, which is the one case a
 * removal does not begin.
 */
final class RemovalsUnderWayInMemory implements RemovalsUnderWay
{
    private StacksBeingRemoved $being;

    private function __construct(private readonly bool $writes)
    {
        $this->being = StacksBeingRemoved::none();
    }

    public static function working(): self
    {
        return new self(writes: true);
    }

    public static function refusing(): self
    {
        return new self(writes: false);
    }

    public function begin(StackId $stack): bool
    {
        if ($this->writes) {
            $this->being = $this->being->with($stack);
        }

        return $this->writes;
    }

    public function underWay(): StacksBeingRemoved
    {
        return $this->being;
    }

    public function finished(StackId $stack): bool
    {
        if ($this->writes) {
            $this->being = $this->being->without($stack);
        }

        return $this->writes;
    }
}
