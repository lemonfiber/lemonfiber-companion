<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One library path a service files into, and whether moving the data location carries it.
 */
final readonly class ALibraryAMoveReaches
{
    private function __construct(
        public string $service,
        public string $path,
        public string $because,
        public bool $carried,
    ) {}

    public static function of(string $service, string $path, string $because, bool $carried): self
    {
        foreach (['service' => $service, 'path' => $path, 'because' => $because] as $part => $text) {
            if (trim($text) === '') {
                throw AFindingSaysNothing::about($part);
            }
        }

        return new self(trim($service), $path, trim($because), $carried);
    }
}
