<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One download an action that stops services would interrupt, as the row that draws it.
 */
final readonly class ADownloadAStopInterruptsAsShown
{
    /**
     * @param string $name     what it is, as the client names it
     * @param int    $progress how far along, from none to a hundred
     * @param string $by       the key naming the kind of client holding it
     */
    public function __construct(
        public string $name,
        public int $progress,
        public string $by,
    ) {}
}
