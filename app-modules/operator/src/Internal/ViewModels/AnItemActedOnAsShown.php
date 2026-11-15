<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One download a healing run acted on, as the queue screen draws it.
 */
final readonly class AnItemActedOnAsShown
{
    /**
     * @param AQueuedDownloadAsShown  $download the download
     * @param string            $why      why it was acted on, in the stack's words
     * @param list<AnActAsShown> $acts     each act, in the order it was taken
     */
    public function __construct(public AQueuedDownloadAsShown $download, public string $why, public array $acts) {}
}
