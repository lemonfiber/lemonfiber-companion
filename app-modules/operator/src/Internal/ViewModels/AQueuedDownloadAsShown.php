<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * A download the queue's healing names, as the queue screen draws it.
 */
final readonly class AQueuedDownloadAsShown
{
    /**
     * @param string $name    the title it is for, or its release where no curator holds it
     * @param string $release its release, where the title names it, or empty
     * @param string $service the curator it was queued with, or the client holding it
     */
    public function __construct(public string $name, public string $release, public string $service) {}
}
