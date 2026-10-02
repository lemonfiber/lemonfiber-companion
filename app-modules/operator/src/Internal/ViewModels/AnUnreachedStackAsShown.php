<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A stack What's new could not read, with when it was last read where the phone knows. */
final readonly class AnUnreachedStackAsShown
{
    /**
     * @param string $stack the stack's name
     * @param string $ago   the catalogue key for how long ago it was last read, or empty where the phone does not know
     * @param int    $count the count that key is said for
     */
    public function __construct(
        public string $stack,
        public string $ago,
        public int $count,
    ) {}
}
