<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One completed download on the machine, flattened for a template.
 *
 * Where it stands is on every row, and what removing it costs is on the row
 * where the stack says it costs anything.
 */
final readonly class ADownloadAsShown
{
    /**
     * @param string $name         what the client and the services call it
     * @param string $standingSaid the catalogue key for where it stands
     * @param string $ratioSaid    the catalogue key for its ratio, or empty where it is not seeding
     * @param string $ratio        the ratio as a person reads it, or empty
     * @param string $consequence  what the stack says removing it costs, or empty
     */
    public function __construct(
        public string $name,
        public ASizeAsShown $size,
        public string $standingSaid,
        public string $ratioSaid,
        public string $ratio,
        public string $consequence,
    ) {}
}
