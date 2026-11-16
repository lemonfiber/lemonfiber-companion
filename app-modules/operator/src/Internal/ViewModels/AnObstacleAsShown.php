<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Something the core named in the way of a reading, flattened for a template. */
final readonly class AnObstacleAsShown
{
    /**
     * @param string $said   the core's one sentence
     * @param string $remedy what the core says to do about it
     * @param string $code   the code that names it
     */
    public function __construct(
        public string $said,
        public string $remedy,
        public string $code,
    ) {}
}
