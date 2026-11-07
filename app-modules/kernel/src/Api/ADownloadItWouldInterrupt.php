<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One download still coming down that a change would interrupt, and how far along it is.
 */
final readonly class ADownloadItWouldInterrupt
{
    private function __construct(
        public string $name,
        public HowADownloadComes $by,
        public int $progress,
    ) {}

    public static function of(string $name, HowADownloadComes $by, int $progress): self
    {
        if (trim($name) === '') {
            throw AFindingSaysNothing::about('name');
        }

        return new self($name, $by, HowFarADownloadIs::at($progress, AFindingSaysNothing::progress($progress))->share);
    }
}
