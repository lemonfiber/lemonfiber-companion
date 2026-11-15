<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A download the queue's healing names: the service holding it, its release, and the title it is for.
 */
final readonly class ADownloadInTheQueue
{
    private function __construct(private ServiceId $service, private string $release, private string $title) {}

    /** A release, held by the curator it was queued with or by the client where no curator holds it, for this title or none. */
    public static function heldBy(ServiceId $service, string $release, string $title): self
    {
        return new self($service, $release, $title);
    }

    /** The curator it was queued with, or the client holding it where none was. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /** The release, by its name in the client. */
    public function release(): string
    {
        return $this->release;
    }

    /** The title it is for, empty where no curator holds it. */
    public function title(): string
    {
        return $this->title;
    }
}
