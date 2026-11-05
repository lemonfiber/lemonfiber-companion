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

        if (! HowFarADownloadIs::couldBe($progress)) {
            throw AFindingSaysNothing::progress($progress);
        }

        return new self($name, $by, $progress);
    }
}
